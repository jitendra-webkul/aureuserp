<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;
use Webkul\PointOfSale\Filament\Admin\Clusters\Products\Resources\ProductResource\Pages\ListProducts;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../../../support/tests/Helpers/FilamentHelper.php';
require_once __DIR__.'/../../../../inventories/tests/Helpers/InventoryHelper.php';
require_once __DIR__.'/../../Helpers/PosHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    DB::table('plugins')->whereIn('name', ['inventories', 'accounts', 'point-of-sale'])->update([
        'is_installed' => true,
        'is_active'    => true,
        'updated_at'   => now(),
    ]);

    Package::$plugins = Plugin::all()->keyBy('name');

    URL::resolveMissingNamedRoutesUsing(fn () => '#');

    FilamentHelper::actingAs([
        'view_any_point_of_sale_product',
        'view_point_of_sale_product',
        'create_point_of_sale_product',
        'update_point_of_sale_product',
    ]);
});

it('renders the point of sale product list page', function () {
    Livewire::test(ListProducts::class)->assertOk();
});

it('lists only products available in the point of sale', function () {
    $available = InventoryHelper::product(['name' => 'Counter Croissant', 'price' => 2.5]);
    $hidden = InventoryHelper::product(['name' => 'Warehouse Pallet', 'price' => 120.0]);

    DB::table('products_products')->where('id', $available->id)->update(['available_in_pos' => true]);

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$available])
        ->assertCanNotSeeTableRecords([$hidden]);
});
