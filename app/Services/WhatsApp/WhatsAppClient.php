<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sends a single WhatsApp message through the configured provider and
 * normalises the outcome, so callers never deal with provider-specific
 * payloads or response shapes. Never throws.
 */
class WhatsAppClient
{
    public const TWILIO_BASE_URL = 'https://api.twilio.com/2010-04-01';

    public static function provider(): string
    {
        return config('services.whatsapp.provider') === 'twilio' ? 'twilio' : 'meta';
    }

    public static function isConfigured(): bool
    {
        if (! config('services.whatsapp.enabled')) {
            return false;
        }

        if (static::provider() === 'twilio') {
            return filled(config('services.whatsapp.twilio.account_sid'))
                && filled(config('services.whatsapp.twilio.auth_token'))
                && filled(config('services.whatsapp.twilio.from'));
        }

        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    public static function twilioStatusCallbackUrl(): string
    {
        return config('services.whatsapp.twilio.status_callback') ?: route('whatsapp.twilio.status');
    }

    public static function twilioInboundUrl(): string
    {
        return config('services.whatsapp.twilio.inbound_url') ?: route('whatsapp.twilio.inbound');
    }

    /**
     * @param  string  $to  Digits-only E.164 number from PhoneNumber::normalize()
     * @param  bool|null  $useTemplate  Null follows the configured message mode
     * @param  int  $attempts  Total attempts for connection errors and 5xx/429 responses
     */
    public function send(string $to, WhatsAppMessage $message, ?bool $useTemplate = null, int $attempts = 1): WhatsAppResult
    {
        $useTemplate ??= config('services.whatsapp.mode') !== 'text';

        try {
            return static::provider() === 'twilio'
                ? $this->sendViaTwilio($to, $message, $useTemplate, $attempts)
                : $this->sendViaMeta($to, $message, $useTemplate, $attempts);
        } catch (Throwable $exception) {
            return WhatsAppResult::failure($exception->getMessage());
        }
    }

    /**
     * The identifier the provider uses for this message's template: the Meta
     * template name, or the Twilio Content SID.
     */
    public function templateLabel(WhatsAppMessage $message): string
    {
        if (static::provider() === 'twilio') {
            return $this->twilioContentSid($message) ?? $message->templateKey;
        }

        return $this->metaTemplateName($message);
    }

    protected function sendViaMeta(string $to, WhatsAppMessage $message, bool $useTemplate, int $attempts): WhatsAppResult
    {
        $endpoint = sprintf(
            '%s/%s/%s/messages',
            config('services.whatsapp.base_url'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );

        $response = Http::withToken(config('services.whatsapp.token'))
            ->timeout((int) config('services.whatsapp.timeout', 10))
            ->retry(max(1, $attempts), 500, throw: false)
            ->acceptJson()
            ->post($endpoint, $this->metaPayload($to, $message, $useTemplate));

        if ($response->successful()) {
            return new WhatsAppResult(true, $response->status(), $response->json('messages.0.id'));
        }

        return WhatsAppResult::failure(
            $response->json('error.message') ?? $response->body(),
            $response->status(),
            $response->json('error.error_data.details'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function metaPayload(string $to, WhatsAppMessage $message, bool $useTemplate): array
    {
        if (! $useTemplate) {
            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message->text,
                ],
            ];
        }

        $parameters = array_map(
            fn (string $value): array => ['type' => 'text', 'text' => $value],
            $message->sanitizedParameters(),
        );

        $template = [
            'name' => $this->metaTemplateName($message),
            'language' => ['code' => config('services.whatsapp.language', 'ar')],
        ];

        if ($parameters !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => $parameters,
            ]];
        }

        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => $template,
        ];
    }

    protected function metaTemplateName(WhatsAppMessage $message): string
    {
        return config('services.whatsapp.templates.'.$message->templateKey, $message->templateKey);
    }

    protected function sendViaTwilio(string $to, WhatsAppMessage $message, bool $useTemplate, int $attempts): WhatsAppResult
    {
        $accountSid = (string) config('services.whatsapp.twilio.account_sid');

        $params = [
            'From' => 'whatsapp:+'.preg_replace('/\D/', '', (string) config('services.whatsapp.twilio.from')),
            'To' => 'whatsapp:+'.$to,
            'StatusCallback' => static::twilioStatusCallbackUrl(),
        ];

        if ($useTemplate) {
            $contentSid = $this->twilioContentSid($message);

            if (! $contentSid) {
                return WhatsAppResult::failure("No Twilio Content SID is configured for template \"{$message->templateKey}\".");
            }

            $params['ContentSid'] = $contentSid;

            $variables = $message->sanitizedParameters();

            // Twilio numbers content variables from 1, matching {{1}}, {{2}}… in the template body.
            if ($variables !== []) {
                $params['ContentVariables'] = json_encode(
                    array_combine(array_map('strval', range(1, count($variables))), $variables),
                    JSON_UNESCAPED_UNICODE,
                );
            }
        } else {
            $params['Body'] = $message->text;
        }

        $response = Http::asForm()
            ->withBasicAuth($accountSid, (string) config('services.whatsapp.twilio.auth_token'))
            ->timeout((int) config('services.whatsapp.timeout', 10))
            ->retry(max(1, $attempts), 500, throw: false)
            ->acceptJson()
            ->post(self::TWILIO_BASE_URL."/Accounts/{$accountSid}/Messages.json", $params);

        if ($response->successful()) {
            return new WhatsAppResult(true, $response->status(), $response->json('sid'));
        }

        $code = $response->json('code');

        return WhatsAppResult::failure(
            $response->json('message') ?? $response->body(),
            $response->status(),
            $code ? trim("Twilio error {$code} ".($response->json('more_info') ?? '')) : null,
        );
    }

    /**
     * A configured Content SID for the template key, or the key itself when it
     * is already a Content SID (as passed to `whatsapp:test --template=HX…`).
     */
    protected function twilioContentSid(WhatsAppMessage $message): ?string
    {
        $configured = config('services.whatsapp.twilio.content_sids.'.$message->templateKey);

        if (filled($configured)) {
            return $configured;
        }

        return str_starts_with($message->templateKey, 'HX') ? $message->templateKey : null;
    }
}
