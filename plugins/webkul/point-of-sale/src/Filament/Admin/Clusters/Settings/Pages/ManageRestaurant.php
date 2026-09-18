<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Settings\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use UnitEnum;
use Webkul\PointOfSale\Settings\RestaurantSettings;
use Webkul\Support\Filament\Clusters\Settings;

class ManageRestaurant extends SettingsPage
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $slug = 'point-of-sale/manage-restaurant';

    protected static string|UnitEnum|null $navigationGroup = 'Point of Sale';

    protected static ?int $navigationSort = 1;

    protected static string $settings = RestaurantSettings::class;

    protected static ?string $cluster = Settings::class;

    protected static function getPagePermission(): ?string
    {
        return 'page_point_of_sale_manage_restaurant';
    }

    public function getBreadcrumbs(): array
    {
        return [
            __('point-of-sale::filament/admin/clusters/settings/pages/manage-restaurant.title'),
        ];
    }

    public function getTitle(): string
    {
        return __('point-of-sale::filament/admin/clusters/settings/pages/manage-restaurant.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('point-of-sale::filament/admin/clusters/settings/pages/manage-restaurant.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('enable_restaurant')
                    ->label(__('point-of-sale::filament/admin/clusters/settings/pages/manage-restaurant.form.fields.enable-restaurant'))
                    ->helperText(__('point-of-sale::filament/admin/clusters/settings/pages/manage-restaurant.form.fields.enable-restaurant-helper-text')),
            ]);
    }
}
