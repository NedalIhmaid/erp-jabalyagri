<?php

namespace App\Http\Controllers;

use App\Services\WhatsApp\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives WhatsApp Cloud API webhooks.
 *
 * Meta verifies the endpoint once with a GET carrying hub.* query parameters,
 * then POSTs delivery status updates and inbound messages. Every POST is
 * answered with 200 regardless of what we do with it — a non-200 makes Meta
 * retry with backoff and eventually disable the subscription.
 */
class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $token = config('services.whatsapp.webhook_verify_token');

        if ($token && $request->query('hub_mode') === 'subscribe'
            && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)
                ->header('Content-Type', 'text/plain');
        }

        Log::channel('whatsapp')->warning('[WhatsApp] Webhook verification rejected', [
            'mode' => $request->query('hub_mode'),
            'ip' => $request->ip(),
        ]);

        return response('Forbidden', 403);
    }

    public function handle(Request $request): Response
    {
        if (! $this->signatureIsValid($request)) {
            Log::channel('whatsapp')->warning('[WhatsApp] Webhook signature mismatch', ['ip' => $request->ip()]);

            return response('', 200);
        }

        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->logStatus($status);
                }

                foreach ($value['messages'] ?? [] as $message) {
                    $this->logInbound($message);
                }
            }
        }

        return response('', 200);
    }

    /**
     * @param  array<string, mixed>  $status
     */
    protected function logStatus(array $status): void
    {
        $errors = $status['errors'] ?? [];

        $context = [
            'id' => $status['id'] ?? null,
            'status' => $status['status'] ?? null,
            'to' => PhoneNumber::mask((string) ($status['recipient_id'] ?? '')),
        ];

        if ($errors === []) {
            Log::channel('whatsapp')->info('[WhatsApp] Status', $context);

            return;
        }

        Log::channel('whatsapp')->warning('[WhatsApp] Status failed', $context + [
            'code' => $errors[0]['code'] ?? null,
            'title' => $errors[0]['title'] ?? null,
            'details' => $errors[0]['error_data']['details'] ?? ($errors[0]['message'] ?? null),
        ]);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function logInbound(array $message): void
    {
        Log::channel('whatsapp')->info('[WhatsApp] Inbound', [
            'from' => PhoneNumber::mask((string) ($message['from'] ?? '')),
            'type' => $message['type'] ?? null,
            'body' => $message['text']['body'] ?? null,
        ]);
    }

    /**
     * Meta signs each POST with the app secret. When no secret is configured the
     * check is skipped so the endpoint still works during initial setup.
     */
    protected function signatureIsValid(Request $request): bool
    {
        $secret = config('services.whatsapp.app_secret');

        if (! $secret) {
            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256');

        if ($signature === '') {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $request->getContent(), $secret), $signature);
    }
}
