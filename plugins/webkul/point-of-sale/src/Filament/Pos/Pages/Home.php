<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Illuminate\Support\Facades\Auth;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Models\Session;

class Home extends Terminal
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $slug = '{session}/home';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationLabel(): string
    {
        return __('point-of-sale::filament/pos/pages/home.navigation.label');
    }

    public static function getNavigationUrl(array $parameters = []): string
    {
        $session = static::navigationSession();

        return $session
            ? static::getUrl(['session' => $session->getKey()])
            : Registers::getUrl();
    }


    public static function shouldRegisterNavigation(): bool
    {
        return (bool) static::navigationSession();
    }

    protected static function navigationSession(): ?Session
    {
        $sessions = Session::query()
            ->whereIn('state', [SessionState::OPENING_CONTROL, SessionState::OPENED])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->limit(2)
            ->get();

        return $sessions->count() === 1 ? $sessions->first() : null;
    }
}
