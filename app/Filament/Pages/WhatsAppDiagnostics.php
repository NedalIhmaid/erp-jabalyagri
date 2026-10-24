<?php

namespace App\Filament\Pages;

use App\Services\WhatsApp\PhoneNumber;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppMessage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppDiagnostics extends Page
{
    protected string $view = 'filament.pages.whatsapp-diagnostics';

    protected static ?string $slug = 'whatsapp-diagnostics';

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $result = null;

    public function mount(): void
    {
        $this->form->fill([
            'template' => 'sales_request_submitted',
        ]);
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chat-bubble-left-right';
    }

    public static function getNavigationLabel(): string
    {
        return __('whatsapp.diagnostics.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationSort(): int
    {
        return 5;
    }

    public function getHeading(): string
    {
        return __('whatsapp.diagnostics.heading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('whatsapp.diagnostics.test_section'))
                    ->description(__('whatsapp.diagnostics.test_description'))
                    ->schema([
                        TextInput::make('phone')
                            ->label(__('whatsapp.diagnostics.recipient_phone'))
                            ->tel()
                            ->placeholder('0790162671')
                            ->required()
                            ->maxLength(20),
                        Select::make('template')
                            ->label(__('whatsapp.diagnostics.template'))
                            ->options($this->templateOptions())
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /** @return array<string, string> */
    public function configurationStatus(): array
    {
        $enabled = config('services.whatsapp.enabled') ? 'ready' : 'missing';

        if (WhatsAppClient::provider() === 'twilio') {
            $templateKeys = array_keys(config('services.whatsapp.templates', []));
            $mappedCount = collect($templateKeys)
                ->filter(fn (string $key): bool => filled(config('services.whatsapp.twilio.content_sids.'.$key)))
                ->count();

            return [
                'enabled' => $enabled,
                'account_sid' => filled(config('services.whatsapp.twilio.account_sid')) ? 'ready' : 'missing',
                'auth_token' => filled(config('services.whatsapp.twilio.auth_token')) ? 'ready' : 'missing',
                'from' => filled(config('services.whatsapp.twilio.from')) ? 'ready' : 'missing',
                'content_sids' => match (true) {
                    $mappedCount === 0 => 'missing',
                    $mappedCount < count($templateKeys) => 'warning',
                    default => 'ready',
                },
            ];
        }

        return [
            'enabled' => $enabled,
            'token' => filled(config('services.whatsapp.token')) ? 'ready' : 'missing',
            'phone_number_id' => filled(config('services.whatsapp.phone_number_id')) ? 'ready' : 'missing',
            'mode' => config('services.whatsapp.mode') === 'template' ? 'ready' : 'warning',
            'language' => filled(config('services.whatsapp.language')) ? 'ready' : 'missing',
        ];
    }

    public function sendTest(): void
    {
        $state = $this->form->getState();
        $phone = PhoneNumber::normalize($state['phone'] ?? null);

        if (! $phone) {
            $this->addError('data.phone', __('whatsapp.diagnostics.invalid_phone'));

            return;
        }

        if (! WhatsAppClient::isConfigured()) {
            Notification::make()
                ->title(__('whatsapp.diagnostics.configuration_incomplete'))
                ->danger()
                ->send();

            return;
        }

        $rateLimitKey = 'whatsapp-diagnostics:'.auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            Notification::make()
                ->title(__('whatsapp.diagnostics.rate_limited'))
                ->danger()
                ->send();

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $templateKey = (string) $state['template'];
        $message = WhatsAppMessage::make($templateKey, $this->testParameters($templateKey), '');
        $client = app(WhatsAppClient::class);
        $result = $client->send($phone, $message, useTemplate: true);

        $this->result = [
            'success' => $result->success,
            'status' => $result->status,
            'message_id' => $result->messageId,
            'error' => $result->error,
            'details' => $result->details,
            'to' => PhoneNumber::mask($phone),
            'template' => $client->templateLabel($message),
        ];

        Notification::make()
            ->title($this->result['success']
                ? __('whatsapp.diagnostics.sent_successfully')
                : __('whatsapp.diagnostics.send_failed'))
            ->{$this->result['success'] ? 'success' : 'danger'}()
            ->send();
    }

    public function clearConfigurationCache(): void
    {
        Artisan::call('config:clear');

        Notification::make()
            ->title(__('whatsapp.diagnostics.cache_cleared'))
            ->body(__('whatsapp.diagnostics.reload_page'))
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    /** @return array<string, string> */
    protected function templateOptions(): array
    {
        return collect(array_keys(config('services.whatsapp.templates', [])))
            ->mapWithKeys(fn (string $key): array => [$key => __('whatsapp.templates.'.$key)])
            ->all();
    }

    /** @return array<int, string> */
    protected function testParameters(string $template): array
    {
        $number = 'TEST-'.now()->format('Ymd-His');

        return match ($template) {
            'sales_request_submitted', 'sales_request_approved' => [__('whatsapp.diagnostics.test_user'), $number, __('whatsapp.diagnostics.test_client')],
            'sales_request_stage_advanced' => [__('whatsapp.diagnostics.test_user'), $number, __('whatsapp.diagnostics.test_stage')],
            'sales_request_rejected', 'sales_request_returned' => [__('whatsapp.diagnostics.test_user'), $number, __('whatsapp.diagnostics.test_actor'), __('whatsapp.diagnostics.test_reason')],
            'hr_request_submitted' => [__('whatsapp.diagnostics.test_user'), __('whatsapp.diagnostics.test_request_type'), __('whatsapp.diagnostics.test_employee'), now()->format('Y-m-d')],
            'hr_request_approved' => [__('whatsapp.diagnostics.test_user'), __('whatsapp.diagnostics.test_request_type'), __('whatsapp.diagnostics.test_actor')],
            'hr_request_rejected' => [__('whatsapp.diagnostics.test_user'), __('whatsapp.diagnostics.test_request_type'), __('whatsapp.diagnostics.test_actor'), __('whatsapp.diagnostics.test_reason')],
            'attendance_check_in_reminder', 'attendance_check_out_reminder' => [__('whatsapp.diagnostics.test_user')],
            default => [],
        };
    }
}
