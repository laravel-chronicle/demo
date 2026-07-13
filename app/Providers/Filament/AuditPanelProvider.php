<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AutoLoginDemoUser;
use Chronicle\Filament\ChronicleFilamentPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AuditPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('audit')
            ->path('audit')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
                AutoLoginDemoUser::class,
            ])
            ->plugin(
                ChronicleFilamentPlugin::make()
                    ->verification(true)
                    ->anchoring(true)
                    ->signingKeys(true)
                    ->cryptoShredding(true)
                    ->exports(true)
                    ->reporting(true)
                    ->erasure(true)
                    ->eraseAllowHoldOverride(false)
                    ->eraseAuthorize(fn (): bool => session('demo_persona') === 'admin')
                    ->exportAuthorize(fn (): bool => session('demo_persona') === 'admin')
            );
    }
}
