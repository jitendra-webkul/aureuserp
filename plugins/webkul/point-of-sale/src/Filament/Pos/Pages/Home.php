<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;

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
        $session = Registers::currentSession();

        return $session
            ? static::getUrl(['session' => $session->getKey()])
            : Registers::getUrl();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) Registers::currentSession();
    }
}
