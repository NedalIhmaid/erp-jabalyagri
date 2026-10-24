<?php

namespace App\Channels;

use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Delivers notifications over WhatsApp through the configured provider
 * (Meta Cloud API or Twilio).
 *
 * Recipients who messaged the business within the last 24 hours get the
 * free-form text; everyone else gets the approved template, whose name or
 * Twilio Content SID is mapped in config/services.php. Delivery failures are
 * logged and swallowed so an unreachable WhatsApp API can never roll back or
 * interrupt an approval workflow.
 */
class WhatsAppChannel
{
    public function __construct(protected WhatsAppClient $client)
    {
    }

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
        return WhatsAppClient::isConfigured();
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

        // Inside the recipient's 24-hour session a free-form message is delivered;
        // outside it only an approved template gets through.
        $useTemplate = config('services.whatsapp.mode') !== 'text'
            && ! (method_exists($notifiable, 'hasOpenWhatsAppSession') && $notifiable->hasOpenWhatsAppSession());

        $result = $this->client->send($to, $message, $useTemplate, (int) config('services.whatsapp.retries', 2));

        if ($result->success) {
            return;
        }

        if ($result->status === null) {
            Log::warning('[WhatsApp] Delivery threw', [
                'to' => PhoneNumber::mask($to),
                'notification' => $notification::class,
                'exception' => $result->error,
            ]);

            return;
        }

        Log::warning('[WhatsApp] Delivery failed', [
            'to' => PhoneNumber::mask($to),
            'notification' => $notification::class,
            'template' => $useTemplate ? $this->client->templateLabel($message) : null,
            'status' => $result->status,
            'error' => $result->error,
        ]);
    }
}
