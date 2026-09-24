<?php

namespace App\Filament\Pages;

use App\Services\WhatsApp\PhoneNumber;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

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
        return [
            'enabled' => config('services.whatsapp.enabled') ? 'ready' : 'missing',
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

        if (! config('services.whatsapp.enabled') || blank(config('services.whatsapp.token')) || blank(config('services.whatsapp.phone_number_id'))) {
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
        $templateName = (string) config('services.whatsapp.templates.'.$templateKey, $templateKey);
        $parameters = $this->testParameters($templateKey);
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => config('services.whatsapp.language', 'ar')],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $value): array => ['type' => 'text', 'text' => $value],
                        $parameters,
                    ),
                ]],
            ],
        ];

        $endpoint = sprintf(
            '%s/%s/%s/messages',
            config('services.whatsapp.base_url'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->timeout((int) config('services.whatsapp.timeout', 10))
                ->acceptJson()
                ->post($endpoint, $payload);

            $this->result = [
                'success' => $response->successful(),
                'status' => $response->status(),
                'message_id' => $response->json('messages.0.id'),
                'error' => $response->successful()
                    ? null
                    : ($response->json('error.message') ?? $response->body()),
                'details' => $response->json('error.error_data.details'),
                'to' => PhoneNumber::mask($phone),
                'template' => $templateName,
            ];
        } catch (Throwable $exception) {
            $this->result = [
                'success' => false,
                'status' => null,
                'message_id' => null,
                'error' => $exception->getMessage(),
                'details' => null,
                'to' => PhoneNumber::mask($phone),
                'template' => $templateName,
            ];
        }

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
            default => [],
        };
    }
}
