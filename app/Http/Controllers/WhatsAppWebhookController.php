<?php

namespace App\Http\Controllers;

use App\Services\Attendance\AttendanceRecorder;
use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Receives WhatsApp Cloud API webhooks, Twilio status callbacks and Twilio
 * inbound messages. Inbound locations from employees become attendance records.
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

    public function handle(Request $request, AttendanceRecorder $attendance): Response
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

                    $location = ($message['type'] ?? null) === 'location' ? ($message['location'] ?? null) : null;

                    $attendance->handleInbound(
                        (string) ($message['from'] ?? ''),
                        $message['id'] ?? null,
                        is_array($location) ? [
                            'latitude' => $location['latitude'] ?? null,
                            'longitude' => $location['longitude'] ?? null,
                            'address' => $location['address'] ?? null,
                            'label' => $location['name'] ?? null,
                        ] : null,
                    );
                }
            }
        }

        return response('', 200);
    }

    /**
     * Twilio POSTs a form-encoded status update to the StatusCallback URL sent
     * with each message. Failures carry a Twilio ErrorCode (63xxx for WhatsApp).
     */
    public function twilioStatus(Request $request): Response
    {
        if (! $this->twilioSignatureIsValid($request, WhatsAppClient::twilioStatusCallbackUrl())) {
            Log::channel('whatsapp')->warning('[WhatsApp] Twilio signature mismatch', ['ip' => $request->ip()]);

            return response('', 200);
        }

        $status = (string) $request->input('MessageStatus');
        $errorCode = $request->input('ErrorCode');

        $context = [
            'id' => $request->input('MessageSid'),
            'status' => $status,
            'to' => PhoneNumber::mask(preg_replace('/\D/', '', (string) $request->input('To'))),
        ];

        if (! $errorCode && ! in_array($status, ['failed', 'undelivered'], true)) {
            Log::channel('whatsapp')->info('[WhatsApp] Status', $context);

            return response('', 200);
        }

        Log::channel('whatsapp')->warning('[WhatsApp] Status failed', $context + [
            'code' => $errorCode,
            'details' => $request->input('ErrorMessage'),
        ]);

        return response('', 200);
    }

    /**
     * Twilio POSTs every message sent to the WhatsApp sender to the "A message
     * comes in" URL configured on the sender. A shared location arrives as
     * Latitude/Longitude fields. The reply is sent through the API, so the
     * TwiML response is always empty.
     */
    public function twilioInbound(Request $request, AttendanceRecorder $attendance): Response
    {
        $emptyTwiml = response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
            ->header('Content-Type', 'text/xml');

        if (! $this->twilioSignatureIsValid($request, WhatsAppClient::twilioInboundUrl())) {
            Log::channel('whatsapp')->warning('[WhatsApp] Twilio signature mismatch', ['ip' => $request->ip()]);

            return $emptyTwiml;
        }

        $from = preg_replace('/\D/', '', (string) $request->input('From'));
        $hasLocation = $request->filled('Latitude') && $request->filled('Longitude');

        Log::channel('whatsapp')->info('[WhatsApp] Inbound', [
            'from' => PhoneNumber::mask($from),
            'type' => $hasLocation ? 'location' : 'text',
            'body' => $request->input('Body'),
        ]);

        $attendance->handleInbound(
            $from,
            $request->input('MessageSid'),
            $hasLocation ? [
                'latitude' => $request->input('Latitude'),
                'longitude' => $request->input('Longitude'),
                'address' => $request->input('Address'),
                'label' => $request->input('Label'),
            ] : null,
        );

        return $emptyTwiml;
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

    /**
     * Twilio signs the public URL it posted to followed by every POST field,
     * sorted by name and concatenated as name+value, using HMAC-SHA1 with the
     * auth token.
     */
    protected function twilioSignatureIsValid(Request $request, string $url): bool
    {
        $token = config('services.whatsapp.twilio.auth_token');
        $signature = (string) $request->header('X-Twilio-Signature');

        if (! $token || $signature === '') {
            return false;
        }

        $fields = $request->request->all();
        ksort($fields);

        $payload = $url;

        foreach ($fields as $name => $value) {
            $payload .= $name.$value;
        }

        return hash_equals(base64_encode(hash_hmac('sha1', $payload, $token, true)), $signature);
    }
}
