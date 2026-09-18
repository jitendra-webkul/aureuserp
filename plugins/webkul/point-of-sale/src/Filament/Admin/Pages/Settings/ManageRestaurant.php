<?php

namespace Webkul\PointOfSale\Filament\Admin\Pages\Settings;

use UnitEnum;
use Webkul\PointOfSale\Filament\Admin\Clusters\PluginSettings;
use Webkul\PointOfSale\Filament\Admin\Clusters\Settings\Pages\ManageRestaurant as BaseManageRestaurant;

class ManageRestaurant extends BaseManageRestaurant
{
    protected static ?string $cluster = PluginSettings::class;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?string $slug = 'manage-restaurant';
}
