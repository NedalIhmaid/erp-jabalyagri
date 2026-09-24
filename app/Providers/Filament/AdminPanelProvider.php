<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ApprovalDashboard;
use App\Filament\Pages\AuditLogPage;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfile;
use App\Filament\Pages\EngineerDashboard;
use App\Filament\Pages\GeneralManagerDashboard;
use App\Filament\Pages\HrReports;
use App\Filament\Pages\SalesReports;
use App\Filament\Pages\WhatsAppDiagnostics;
use App\Filament\Resources\HrRequestResource;
use App\Http\Middleware\SetUserLocale;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public static function resolveBrandLogo(): ?string
    {
        foreach (['images/logo.svg', 'images/logo.png', 'images/logo.webp'] as $candidate) {
            if (is_file(public_path($candidate))) {
                return asset($candidate);
            }
        }

        return null;
    }

    public static function resolveHomeUrl(): string
    {
        $user = auth()->user();

        if (! $user) {
            return '/';
        }

        return match (true) {
            $user->hasRole('general_manager') => GeneralManagerDashboard::getUrl(),
            $user->hasSalesApprovalRole() => ApprovalDashboard::getUrl(),
            $user->hasRole('engineer') => EngineerDashboard::getUrl(),
            $user->hasRole('employee') => HrRequestResource::getUrl(),
            default => '/',
        };
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->brandName('Al-Jabali')
            ->brandLogo(fn () => static::resolveBrandLogo())
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => '#133316',
                'gray' => Color::Slate,
            ])
            ->font(fn () => app()->getLocale() === 'ar' ? 'IBM Plex Sans Arabic' : 'Inter')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch(false)
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.topbar.actions')->render(),
            )
            ->homeUrl(fn () => static::resolveHomeUrl())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Dashboard::class,
                EngineerDashboard::class,
                ApprovalDashboard::class,
                GeneralManagerDashboard::class,
                AuditLogPage::class,
                SalesReports::class,
                HrReports::class,
                WhatsAppDiagnostics::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->userMenu(false)
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn () => __('navigation.catalog'))
                    ->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make()
                    ->label(fn () => __('navigation.admin'))
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                SetUserLocale::class,
            ]);
    }
}
