<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppTestCommand extends Command
{
    protected $signature = 'whatsapp:test
                            {phone : Recipient number, e.g. 0512345678 or +966512345678}
                            {--template= : Template name to send; omit to send a free-form text message}
                            {--param=* : Ordered body parameters for the template}';

    protected $description = 'Send a single WhatsApp message to verify the Cloud API credentials and templates';

    public function handle(): int
    {
        foreach (['enabled', 'token', 'phone_number_id'] as $key) {
            if (! config("services.whatsapp.{$key}")) {
                $this->error("services.whatsapp.{$key} is not set. Check your .env and run: php artisan config:clear");

                return self::FAILURE;
            }
        }

        $to = PhoneNumber::normalize($this->argument('phone'));

        if (! $to) {
            $this->error('That phone number could not be normalised to E.164.');

            return self::FAILURE;
        }

        $template = $this->option('template');
        $parameters = $this->option('param');

        $payload = $template
            ? [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'template',
                'template' => array_filter([
                    'name' => $template,
                    'language' => ['code' => config('services.whatsapp.language', 'ar')],
                    'components' => $parameters === [] ? null : [[
                        'type' => 'body',
                        'parameters' => array_map(
                            fn (string $value): array => ['type' => 'text', 'text' => $value],
                            $parameters,
                        ),
                    ]],
                ]),
            ]
            : [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => 'Al-Jabali test message — '.now()->format('Y-m-d H:i'),
                ],
            ];

        $endpoint = sprintf(
            '%s/%s/%s/messages',
            config('services.whatsapp.base_url'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );

        $this->line('POST '.$endpoint);
        $this->line('to:  '.$to);

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->timeout((int) config('services.whatsapp.timeout', 10))
                ->acceptJson()
                ->post($endpoint, $payload);
        } catch (Throwable $exception) {
            $this->error('Request failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($response->successful()) {
            $this->info('Sent. Message id: '.($response->json('messages.0.id') ?? 'unknown'));

            return self::SUCCESS;
        }

        $this->error('HTTP '.$response->status().': '.($response->json('error.message') ?? $response->body()));

        if ($details = $response->json('error.error_data.details')) {
            $this->line($details);
        }

        return self::FAILURE;
    }
}
