<?php

namespace Webkul\Manufacturing\Filament\Clusters;

use Filament\Clusters\Cluster;

class Operations extends Cluster
{
    protected static ?string $slug = 'manufacturing/operations';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('manufacturing::filament/clusters/operations.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('manufacturing::filament/clusters/operations.navigation.group');
    }
}
