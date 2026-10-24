<?php

namespace Tests\Feature;

use App\Channels\WhatsAppChannel;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppTwilioTest extends TestCase
{
    use RefreshDatabase;

    private const CALLBACK_URL = 'https://erp.example.com/whatsapp/twilio/status';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.provider' => 'twilio',
            'services.whatsapp.token' => null,
            'services.whatsapp.phone_number_id' => null,
            'services.whatsapp.mode' => 'template',
            'services.whatsapp.default_country_code' => '962',
            'services.whatsapp.retries' => 1,
            'services.whatsapp.twilio.account_sid' => 'AC123',
            'services.whatsapp.twilio.auth_token' => 'twilio-secret',
            'services.whatsapp.twilio.from' => '+962797850865',
            'services.whatsapp.twilio.status_callback' => self::CALLBACK_URL,
            'services.whatsapp.twilio.content_sids.sales_request_submitted' => 'HXsubmitted',
        ]);
    }

    private function notifyKeeper(array $keeperAttributes = []): void
    {
        $engineer = User::factory()->create(['phone' => '0790000001', 'is_active' => true]);
        $keeper = User::factory()->create(['phone' => '0790162671', 'is_active' => true, ...$keeperAttributes]);

        $salesRequest = SalesApprovalRequest::factory()->create([
            'user_id' => $engineer->id,
            'request_number' => 'SAR-20261004-0001',
            'client_name' => 'مزرعة النخيل',
        ]);

        $keeper->notify(new SalesRequestSubmittedNotification($salesRequest));
    }

    public function test_twilio_is_configured_without_meta_credentials(): void
    {
        $this->assertTrue(WhatsAppChannel::isConfigured());

        config(['services.whatsapp.twilio.from' => null]);

        $this->assertFalse(WhatsAppChannel::isConfigured());
    }

    public function test_sales_notification_posts_a_content_template_to_twilio(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        $this->notifyKeeper();

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();
            $variables = json_decode($body['ContentVariables'], true);

            return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC123/Messages.json'
                && $request->hasHeader('Authorization', 'Basic '.base64_encode('AC123:twilio-secret'))
                && $body['From'] === 'whatsapp:+962797850865'
                && $body['To'] === 'whatsapp:+962790162671'
                && $body['ContentSid'] === 'HXsubmitted'
                && $body['StatusCallback'] === self::CALLBACK_URL
                && $variables['2'] === 'SAR-20261004-0001'
                && ! array_key_exists('0', $variables);
        });
    }

    public function test_a_recipient_with_an_open_session_gets_free_form_text(): void
    {
        config(['services.whatsapp.twilio.content_sids.sales_request_submitted' => null]);
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        $this->notifyKeeper(['whatsapp_last_inbound_at' => now()->subHours(23)]);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $body['To'] === 'whatsapp:+962790162671'
                && ! array_key_exists('ContentSid', $body)
                && str_contains($body['Body'], 'SAR-20261004-0001');
        });
    }

    public function test_a_recipient_whose_session_expired_gets_the_template(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123', 'status' => 'queued'], 201),
        ]);

        $this->notifyKeeper(['whatsapp_last_inbound_at' => now()->subHours(25)]);

        Http::assertSent(fn (Request $request): bool => ($request->data()['ContentSid'] ?? null) === 'HXsubmitted'
            && ! array_key_exists('Body', $request->data()));
    }

    public function test_a_template_without_a_content_sid_is_logged_and_not_sent(): void
    {
        config(['services.whatsapp.twilio.content_sids.sales_request_submitted' => null]);
        Http::fake();

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => str_contains($context['exception'] ?? '', 'sales_request_submitted'));

        $this->notifyKeeper();

        Http::assertNothingSent();
    }

    public function test_a_twilio_error_is_logged_and_does_not_bubble_up(): void
    {
        Http::fake([
            'api.twilio.com/*' => Http::response(['code' => 63016, 'message' => 'Outside the allowed window', 'more_info' => 'https://www.twilio.com/docs/errors/63016'], 400),
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $message === '[WhatsApp] Delivery failed'
                && $context['status'] === 400
                && $context['template'] === 'HXsubmitted'
                && $context['to'] === '********2671');

        $this->notifyKeeper();

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_a_signed_failed_status_callback_is_logged(): void
    {
        $fields = [
            'MessageSid' => 'SM123',
            'MessageStatus' => 'undelivered',
            'To' => 'whatsapp:+962790162671',
            'ErrorCode' => '63049',
        ];

        Log::shouldReceive('channel')->with('whatsapp')->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message, array $context) => $message === '[WhatsApp] Status failed'
                && $context['id'] === 'SM123'
                && $context['code'] === '63049'
                && $context['to'] === '********2671');

        $this->post('/whatsapp/twilio/status', $fields, ['X-Twilio-Signature' => $this->sign($fields)])
            ->assertOk();
    }

    public function test_an_unsigned_status_callback_is_rejected(): void
    {
        Log::shouldReceive('channel')->with('whatsapp')->andReturnSelf();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $message) => $message === '[WhatsApp] Twilio signature mismatch');

        $this->post('/whatsapp/twilio/status', ['MessageSid' => 'SM123', 'MessageStatus' => 'failed'], ['X-Twilio-Signature' => 'forged'])
            ->assertOk();
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function sign(array $fields): string
    {
        ksort($fields);

        $payload = self::CALLBACK_URL;

        foreach ($fields as $name => $value) {
            $payload .= $name.$value;
        }

        return base64_encode(hash_hmac('sha1', $payload, 'twilio-secret', true));
    }
}
