<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyBrandSettings;
use App\Http\Middleware\SetLocale;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource;
use Webkul\PointOfSale\Filament\Pos\Pages\Registers;

class PosPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pos')
            ->path('pos')
            ->login()
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->maxContentWidth(Width::Full)
            ->topNavigation()
            ->userMenuItems([
                'cashMovement' => Action::make('cashMovement')
                    ->label(fn (): string => __('point-of-sale::filament/pos/pages/terminal.menu.cash-in-out'))
                    ->icon('heroicon-m-banknotes')
                    ->visible(fn (): bool => Registers::ownSession() !== null)
                    ->action(fn ($livewire) => $livewire->dispatch('pos-open-cash-movement')),

                'backOffice' => Action::make('backOffice')
                    ->label(fn (): string => __('point-of-sale::filament/pos/pages/terminal.menu.back-office'))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (): string => ConfigResource::getUrl(panel: 'admin')),

                'closeRegister' => Action::make('closeRegister')
                    ->label(fn (): string => __('point-of-sale::filament/pos/pages/terminal.menu.close-register'))
                    ->icon('heroicon-m-lock-closed')
                    ->color('danger')
                    ->visible(fn (): bool => Registers::ownSession() !== null)
                    ->action(fn ($livewire) => $livewire->dispatch('pos-close-register')),
            ])
            ->spa()
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
                SetLocale::class,
                ApplyBrandSettings::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
