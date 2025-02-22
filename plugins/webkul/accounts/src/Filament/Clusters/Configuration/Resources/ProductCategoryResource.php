<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources;

use Webkul\Account\Filament\Clusters\Configuration;
use Webkul\Account\Filament\Clusters\Configuration\Resources\ProductCategoryResource\Pages;
use Webkul\Sale\Filament\Clusters\Configuration\Resources\ProductCategoryResource as BaseProductCategoryResource;

class ProductCategoryResource extends BaseProductCategoryResource
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = Configuration::class;

    public static function getNavigationGroup(): ?string
    {
        return __('accounts::filament/clusters/configurations/resources/product-category.navigation.group');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'view'   => Pages\ViewProductCategory::route('/{record}'),
            'edit'   => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
