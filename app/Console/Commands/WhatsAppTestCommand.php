<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppMessage;
use Illuminate\Console\Command;

class WhatsAppTestCommand extends Command
{
    protected $signature = 'whatsapp:test
                            {phone : Recipient number, e.g. 0512345678 or +966512345678}
                            {--template= : Template key, Meta template name, or Twilio Content SID (HX…); omit to send a free-form text message}
                            {--param=* : Ordered body parameters for the template}';

    protected $description = 'Send a single WhatsApp message to verify the provider credentials and templates';

    public function handle(WhatsAppClient $client): int
    {
        if (! WhatsAppClient::isConfigured()) {
            $this->error('WhatsApp is disabled or the '.WhatsAppClient::provider().' credentials are incomplete. Check your .env and run: php artisan config:clear');

            return self::FAILURE;
        }

        $to = PhoneNumber::normalize($this->argument('phone'));

        if (! $to) {
            $this->error('That phone number could not be normalised to E.164.');

            return self::FAILURE;
        }

        $template = $this->option('template');

        $message = WhatsAppMessage::make(
            (string) $template,
            $this->option('param'),
            'Al-Jabali test message — '.now()->format('Y-m-d H:i'),
        );

        $this->line('provider: '.WhatsAppClient::provider());
        $this->line('to:       '.$to);

        if ($template) {
            $this->line('template: '.$client->templateLabel($message));
        }

        $result = $client->send($to, $message, useTemplate: (bool) $template);

        if ($result->success) {
            $this->info('Sent. Message id: '.($result->messageId ?? 'unknown'));

            return self::SUCCESS;
        }

        $this->error(($result->status ? 'HTTP '.$result->status.': ' : 'Request failed: ').$result->error);

        if ($result->details) {
            $this->line($result->details);
        }

        return self::FAILURE;
    }
}
