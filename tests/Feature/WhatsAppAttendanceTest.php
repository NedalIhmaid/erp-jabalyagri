<?php

namespace Tests\Feature;

use App\Enums\AttendanceType;
use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Filament\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\HrRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private const INBOUND_URL = 'https://erp.example.com/whatsapp/twilio/inbound';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.provider' => 'twilio',
            'services.whatsapp.mode' => 'template',
            'services.whatsapp.default_country_code' => '962',
            'services.whatsapp.retries' => 1,
            'services.whatsapp.twilio.account_sid' => 'AC123',
            'services.whatsapp.twilio.auth_token' => 'twilio-secret',
            'services.whatsapp.twilio.from' => '+962797850865',
            'services.whatsapp.twilio.status_callback' => 'https://erp.example.com/whatsapp/twilio/status',
            'services.whatsapp.twilio.inbound_url' => self::INBOUND_URL,
            'services.whatsapp.twilio.content_sids.attendance_check_in_reminder' => 'HXcheckin',
            'services.whatsapp.twilio.content_sids.attendance_check_out_reminder' => 'HXcheckout',
            'services.whatsapp.attendance.timezone' => 'Asia/Amman',
            'services.whatsapp.attendance.excluded_roles' => ['general_manager'],
        ]);

        // 08:10 in Amman.
        Carbon::setTestNow('2026-10-19 05:10:00');

        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM-out', 'status' => 'queued'], 201),
        ]);
    }

    private function employee(string $phone = '0790162671', array $attributes = []): User
    {
        return User::factory()->create(['phone' => $phone, 'is_active' => true, 'locale' => 'ar'] + $attributes);
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function postInbound(array $fields): void
    {
        $this->post('/whatsapp/twilio/inbound', $fields, ['X-Twilio-Signature' => $this->sign($fields)])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * @return array<string, string>
     */
    private function locationFields(string $sid, string $from = 'whatsapp:+962790162671'): array
    {
        return [
            'MessageSid' => $sid,
            'From' => $from,
            'To' => 'whatsapp:+962797850865',
            'Latitude' => '31.9539494',
            'Longitude' => '35.910635',
            'Address' => 'Amman, Jordan',
            'Label' => 'Head office',
        ];
    }

    public function test_the_first_location_of_the_day_is_stored_as_a_check_in_and_confirmed(): void
    {
        $employee = $this->employee();

        $this->postInbound($this->locationFields('SM1'));

        $record = AttendanceRecord::sole();

        $this->assertSame($employee->id, $record->user_id);
        $this->assertSame(AttendanceType::CheckIn, $record->type);
        $this->assertSame('31.9539494', $record->latitude);
        $this->assertSame('Head office', $record->label);
        $this->assertTrue($employee->fresh()->hasOpenWhatsAppSession());

        Http::assertSent(fn (Request $request): bool => $request->data()['To'] === 'whatsapp:+962790162671'
            && str_contains($request->data()['Body'], '08:10')
            && ! array_key_exists('ContentSid', $request->data()));
    }

    public function test_a_later_location_on_the_same_day_is_a_check_out(): void
    {
        $this->employee();

        $this->postInbound($this->locationFields('SM1'));
        Carbon::setTestNow('2026-10-19 13:35:00');
        $this->postInbound($this->locationFields('SM2'));

        $this->assertSame(
            [AttendanceType::CheckIn, AttendanceType::CheckOut],
            AttendanceRecord::orderBy('id')->pluck('type')->all(),
        );
    }

    public function test_a_retried_webhook_is_stored_once(): void
    {
        $this->employee();

        $this->postInbound($this->locationFields('SM1'));
        $this->postInbound($this->locationFields('SM1'));

        $this->assertDatabaseCount('attendance_records', 1);
        Http::assertSentCount(1);
    }

    public function test_a_text_message_opens_the_session_without_recording_attendance(): void
    {
        $employee = $this->employee();

        $this->postInbound(['MessageSid' => 'SM1', 'From' => 'whatsapp:+962790162671', 'Body' => 'صباح الخير']);

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertTrue($employee->fresh()->hasOpenWhatsAppSession());
        Http::assertNothingSent();
    }

    public function test_messages_from_unknown_numbers_are_ignored(): void
    {
        $this->employee();

        $this->postInbound($this->locationFields('SM1', 'whatsapp:+962781111111'));

        $this->assertDatabaseCount('attendance_records', 0);
        Http::assertNothingSent();
    }

    public function test_an_unsigned_inbound_message_is_ignored(): void
    {
        $employee = $this->employee();

        $this->post('/whatsapp/twilio/inbound', $this->locationFields('SM1'), ['X-Twilio-Signature' => 'forged'])
            ->assertOk();

        $this->assertDatabaseCount('attendance_records', 0);
        $this->assertNull($employee->fresh()->whatsapp_last_inbound_at);
    }

    public function test_a_meta_location_message_is_stored(): void
    {
        config([
            'services.whatsapp.provider' => 'meta',
            'services.whatsapp.token' => 'meta-token',
            'services.whatsapp.phone_number_id' => '123',
            'services.whatsapp.app_secret' => 'meta-secret',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.out']]], 200)]);

        $employee = $this->employee();
        $payload = $this->metaLocationPayload();

        $this->postJson('/whatsapp/webhook', $payload, [
            'X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', json_encode($payload), 'meta-secret'),
        ])->assertOk();

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $employee->id,
            'type' => AttendanceType::CheckIn->value,
            'provider_message_id' => 'wamid.in',
        ]);
        Http::assertSent(fn (Request $request): bool => $request['type'] === 'text');
    }

    public function test_meta_webhook_ignores_unsigned_messages_when_no_app_secret_is_configured(): void
    {
        config([
            'services.whatsapp.provider' => 'meta',
            'services.whatsapp.app_secret' => null,
        ]);

        $this->employee();

        $this->postJson('/whatsapp/webhook', $this->metaLocationPayload())->assertOk();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    /** @return array<string, mixed> */
    protected function metaLocationPayload(): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'messages' => [[
                            'from' => '962790162671',
                            'id' => 'wamid.in',
                            'type' => 'location',
                            'location' => ['latitude' => 31.95, 'longitude' => 35.91, 'name' => 'Head office'],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    public function test_check_in_reminders_use_text_inside_the_session_and_templates_outside_it(): void
    {
        $open = $this->employee('0790000001', ['whatsapp_last_inbound_at' => now()->subHours(23)]);
        $closed = $this->employee('0790000002', ['whatsapp_last_inbound_at' => now()->subHours(25)]);

        $checkedIn = $this->employee('0790000003');
        AttendanceRecord::create([
            'user_id' => $checkedIn->id,
            'type' => AttendanceType::CheckIn,
            'latitude' => 31.95,
            'longitude' => 35.91,
            'recorded_at' => now()->subMinutes(5),
        ]);

        $onLeave = $this->employee('0790000004');
        HrRequest::factory()->create([
            'user_id' => $onLeave->id,
            'type' => HrRequestType::AnnualLeave,
            'status' => HrRequestStatus::Approved,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'start_time' => null,
            'end_time' => null,
        ]);

        Role::create(['name' => 'general_manager']);
        $this->employee('0790000005')->assignRole('general_manager');

        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM-out'], 201)]);

        $this->artisan('attendance:remind check_in')->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->data()['To'] === 'whatsapp:+962790000001'
            && str_contains($request->data()['Body'] ?? '', $open->name)
            && ! array_key_exists('ContentSid', $request->data()));
        Http::assertSent(fn (Request $request): bool => $request->data()['To'] === 'whatsapp:+962790000002'
            && ($request->data()['ContentSid'] ?? null) === 'HXcheckin'
            && json_decode($request->data()['ContentVariables'], true) === ['1' => $closed->name]);
    }

    public function test_check_out_reminders_go_only_to_employees_who_checked_in_and_not_out(): void
    {
        Carbon::setTestNow('2026-10-19 13:30:00');

        $pending = $this->employee('0790000001', ['whatsapp_last_inbound_at' => now()->subHours(5)]);
        $done = $this->employee('0790000002');
        $this->employee('0790000003');

        foreach ([[$pending, AttendanceType::CheckIn], [$done, AttendanceType::CheckIn], [$done, AttendanceType::CheckOut]] as [$user, $type]) {
            AttendanceRecord::create([
                'user_id' => $user->id,
                'type' => $type,
                'latitude' => 31.95,
                'longitude' => 35.91,
                'recorded_at' => now()->subHour(),
            ]);
        }

        $this->artisan('attendance:remind check_out')->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->data()['To'] === 'whatsapp:+962790000001'
            && isset($request->data()['Body']));
    }

    public function test_only_the_general_manager_can_open_the_attendance_log(): void
    {
        $employee = $this->employee();
        AttendanceRecord::create([
            'user_id' => $employee->id,
            'type' => AttendanceType::CheckIn,
            'latitude' => 31.95,
            'longitude' => 35.91,
            'label' => 'Head office',
            'recorded_at' => now(),
        ]);

        Role::create(['name' => 'general_manager']);
        $manager = $this->employee('0790000009');
        $manager->assignRole('general_manager');

        $this->actingAs($manager)
            ->get(AttendanceRecordResource::getUrl())
            ->assertOk()
            ->assertSee('Head office')
            ->assertSee('08:10');

        $this->actingAs($employee)
            ->get(AttendanceRecordResource::getUrl())
            ->assertForbidden();
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function sign(array $fields): string
    {
        ksort($fields);

        $payload = self::INBOUND_URL;

        foreach ($fields as $name => $value) {
            $payload .= $name.$value;
        }

        return base64_encode(hash_hmac('sha1', $payload, 'twilio-secret', true));
    }
}
