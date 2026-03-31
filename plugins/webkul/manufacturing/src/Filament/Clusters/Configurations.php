<?php

namespace Webkul\Manufacturing\Filament\Clusters;

use Filament\Clusters\Cluster;

class Configurations extends Cluster
{
    protected static ?string $slug = 'manufacturing/configurations';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('manufacturing::filament/clusters/configurations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('manufacturing::filament/clusters/configurations.navigation.group');
    }
}
