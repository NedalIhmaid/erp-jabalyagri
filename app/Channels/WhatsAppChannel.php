<?php

namespace App\Channels;

use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers notifications through the Meta WhatsApp Cloud API.
 *
 * Business-initiated messages must use templates approved in Meta Business
 * Manager; the template names are mapped in config/services.php. Delivery
 * failures are logged and swallowed so an unreachable WhatsApp API can never
 * roll back or interrupt an approval workflow.
 */
class WhatsAppChannel
{
    /**
     * Channels to append to a notification's via() list for this notifiable.
     * Returns an empty array when WhatsApp is off, misconfigured, or the
     * recipient has no usable number.
     *
     * @return array<int, string>
     */
    public static function enabledFor(object $notifiable): array
    {
        if (! static::isConfigured()) {
            return [];
        }

        if (! method_exists($notifiable, 'routeNotificationFor')) {
            return [];
        }

        return $notifiable->routeNotificationFor('whatsapp') ? [static::class] : [];
    }

    public static function isConfigured(): bool
    {
        return (bool) config('services.whatsapp.enabled')
            && (bool) config('services.whatsapp.token')
            && (bool) config('services.whatsapp.phone_number_id');
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! static::isConfigured() || ! method_exists($notification, 'toWhatsApp')) {
            return;
        }

        $to = PhoneNumber::normalize($notifiable->routeNotificationFor('whatsapp'));

        if (! $to) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);

        if (! $message instanceof WhatsAppMessage) {
            return;
        }

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->timeout((int) config('services.whatsapp.timeout', 10))
                ->retry((int) config('services.whatsapp.retries', 2), 500, throw: false)
                ->acceptJson()
                ->post($this->endpoint(), $this->payload($to, $message));

            if ($response->failed()) {
                Log::warning('[WhatsApp] Delivery failed', [
                    'to' => PhoneNumber::mask($to),
                    'notification' => $notification::class,
                    'template' => $this->templateName($message),
                    'status' => $response->status(),
                    'error' => $response->json('error.message') ?? $response->body(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('[WhatsApp] Delivery threw', [
                'to' => PhoneNumber::mask($to),
                'notification' => $notification::class,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    protected function endpoint(): string
    {
        return sprintf(
            '%s/%s/%s/messages',
            config('services.whatsapp.base_url'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(string $to, WhatsAppMessage $message): array
    {
        if (config('services.whatsapp.mode') === 'text') {
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
            'name' => $this->templateName($message),
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

    protected function templateName(WhatsAppMessage $message): string
    {
        return config('services.whatsapp.templates.'.$message->templateKey, $message->templateKey);
    }
}
