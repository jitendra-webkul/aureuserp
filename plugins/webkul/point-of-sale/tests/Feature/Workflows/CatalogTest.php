<?php

use Illuminate\Support\Facades\DB;
use Webkul\PointOfSale\Services\CatalogLoader;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../../../inventories/tests/Helpers/InventoryHelper.php';
require_once __DIR__.'/../../Helpers/PosHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    InventoryHelper::actingAsAdmin();

    $this->warehouse = InventoryHelper::warehouse();
    $this->config = PosHelper::configWithCashMethod($this->warehouse);
});

it('loads only products flagged available in pos', function () {
    $available = InventoryHelper::product(['name' => 'Counter Coffee', 'price' => 3.0]);
    InventoryHelper::product(['name' => 'Back Office Service', 'price' => 50.0]);

    DB::table('products_products')->where('id', $available->id)->update(['available_in_pos' => true]);

    $catalog = app(CatalogLoader::class)->load($this->config);

    $names = collect($catalog['products'])->pluck('name');

    expect($names)->toContain('Counter Coffee')
        ->and($names)->not->toContain('Back Office Service');
});

it('lists a configurable template and flags it for the configurator', function () {
    $template = InventoryHelper::product(['name' => 'Configurable Shirt', 'price' => 20.0]);

    DB::table('products_products')->where('id', $template->id)->update([
        'available_in_pos' => true,
        'is_configurable'  => true,
    ]);

    $catalog = app(CatalogLoader::class)->load($this->config);

    $listed = collect($catalog['products'])->firstWhere('name', 'Configurable Shirt');

    expect($listed)->not->toBeNull()
        ->and($listed['is_configurable'])->toBeTrue();
});

it('keeps the variants of a template out of the catalogue', function () {
    $template = InventoryHelper::product(['name' => 'Configurable Shirt', 'price' => 20.0]);

    $variant = InventoryHelper::product(['name' => 'Configurable Shirt - S', 'price' => 20.0]);

    DB::table('products_products')->where('id', $template->id)->update([
        'available_in_pos' => true,
        'is_configurable'  => true,
    ]);

    DB::table('products_products')->where('id', $variant->id)->update([
        'available_in_pos' => true,
        'parent_id'        => $template->id,
    ]);

    $catalog = app(CatalogLoader::class)->load($this->config);

    $names = collect($catalog['products'])->pluck('name');

    expect($names)->toContain('Configurable Shirt')
        ->not->toContain('Configurable Shirt - S');
});
