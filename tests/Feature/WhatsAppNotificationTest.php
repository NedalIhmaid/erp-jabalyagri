<?php

namespace Tests\Feature;

use App\Channels\WhatsAppChannel;
use App\Enums\HrRequestType;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\HrRequestApprovedNotification;
use App\Notifications\SalesRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.mode' => 'template',
            'services.whatsapp.language' => 'ar',
            'services.whatsapp.default_country_code' => '966',
            'services.whatsapp.retries' => 1,
        ]);
    }

    private function salesRequestFor(User $engineer): SalesApprovalRequest
    {
        return SalesApprovalRequest::factory()->create([
            'user_id' => $engineer->id,
            'request_number' => 'SAR-20260725-0001',
            'client_name' => 'مزرعة النخيل',
        ]);
    }

    public function test_sales_notification_posts_a_template_to_the_cloud_api(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200),
        ]);

        $engineer = User::factory()->create(['phone' => '0512345678', 'is_active' => true]);
        $keeper = User::factory()->create(['name' => 'أمين المستودع', 'phone' => '0555555555', 'is_active' => true]);

        $keeper->notify(new SalesRequestSubmittedNotification($this->salesRequestFor($engineer)));

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return str_contains($request->url(), '/123456/messages')
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $body['to'] === '966555555555'
                && $body['type'] === 'template'
                && $body['template']['name'] === 'sales_request_submitted'
                && $body['template']['language']['code'] === 'ar'
                && $body['template']['components'][0]['parameters'][1]['text'] === 'SAR-20260725-0001';
        });
    }

    public function test_hr_notification_sends_free_form_text_in_text_mode(): void
    {
        config(['services.whatsapp.mode' => 'text']);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.TEST']]], 200)]);

        $employee = User::factory()->create(['name' => 'سالم', 'phone' => '+966512345678', 'is_active' => true]);
        $hrRequest = HrRequest::factory()->create([
            'user_id' => $employee->id,
            'type' => HrRequestType::AnnualLeave,
        ]);

        $employee->notify(new HrRequestApprovedNotification($hrRequest, 'المدير العام'));

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $body['type'] === 'text'
                && $body['to'] === '966512345678'
                && str_contains($body['text']['body'], 'المدير العام');
        });
    }

    public function test_nothing_is_sent_when_the_recipient_has_no_phone(): void
    {
        Http::fake();

        $engineer = User::factory()->create(['phone' => '0512345678', 'is_active' => true]);
        $keeper = User::factory()->create(['phone' => null, 'is_active' => true]);

        $keeper->notify(new SalesRequestSubmittedNotification($this->salesRequestFor($engineer)));

        Http::assertNothingSent();
    }

    public function test_nothing_is_sent_when_the_integration_is_disabled(): void
    {
        config(['services.whatsapp.enabled' => false]);
        Http::fake();

        $engineer = User::factory()->create(['phone' => '0512345678', 'is_active' => true]);
        $keeper = User::factory()->create(['phone' => '0555555555', 'is_active' => true]);

        $notification = new SalesRequestSubmittedNotification($this->salesRequestFor($engineer));

        $this->assertNotContains(WhatsAppChannel::class, $notification->via($keeper));

        $keeper->notify($notification);

        Http::assertNothingSent();
    }

    public function test_an_api_failure_is_logged_and_does_not_bubble_up(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not found']], 404),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $message === '[WhatsApp] Delivery failed'
                && $context['status'] === 404
                && $context['to'] === '********5555');

        $engineer = User::factory()->create(['phone' => '0512345678', 'is_active' => true]);
        $keeper = User::factory()->create(['phone' => '0555555555', 'is_active' => true]);

        $keeper->notify(new SalesRequestSubmittedNotification($this->salesRequestFor($engineer)));

        $this->assertDatabaseCount('notifications', 1);
    }
}
