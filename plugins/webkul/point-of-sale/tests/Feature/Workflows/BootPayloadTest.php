<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Webkul\PointOfSale\Services\BootLoader;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../../../inventories/tests/Helpers/InventoryHelper.php';
require_once __DIR__.'/../../Helpers/PosHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    InventoryHelper::actingAsAdmin();

    $this->warehouse = InventoryHelper::warehouse();
    $this->session = PosHelper::openSession($this->warehouse);
    $this->config = $this->session->config;
    $this->tax = PosHelper::taxWithAccounts(15.0);
    $this->product = InventoryHelper::product(['price' => 29.0, 'cost' => 12.0]);

    $this->product->update(['available_in_pos' => true]);

    InventoryHelper::stockUp($this->product, $this->warehouse->lotStockLocation, 40);
});

it('ships everything the terminal needs to compute a total without the server', function () {
    $payload = app(BootLoader::class)->load($this->config, $this->session);

    expect($payload)->toHaveKeys([
        'company', 'config', 'session', 'currencies', 'uoms', 'categories', 'products',
        'taxes', 'fiscal_positions', 'price_lists', 'prices', 'payment_methods',
        'bills', 'notes', 'partners', 'orders', 'stock',
    ]);

    expect($payload['company'])->toHaveKey('tax_calculation_rounding_method');

    expect($payload['currencies'])->not->toBeEmpty();

    expect($payload['currencies'][0])->toHaveKeys(['rounding', 'decimal_places']);
});

it('ships tax definitions rather than only tax ids', function () {
    $payload = app(BootLoader::class)->load($this->config, $this->session);

    $tax = collect($payload['taxes'])->firstWhere('id', $this->tax->id);

    expect($tax)->not->toBeNull()
        ->and($tax)->toHaveKeys([
            'amount', 'amount_type', 'price_include', 'include_base_amount',
            'is_base_affected', 'has_negative_factor', 'children_tax_ids',
        ])
        ->and($tax['amount'])->toBe(15.0)
        ->and($tax['amount_type'])->toBe('percent');
});

it('resolves a price for every product it ships', function () {
    $payload = app(BootLoader::class)->load($this->config, $this->session);

    expect($payload['products'])->not->toBeEmpty();

    foreach ($payload['products'] as $product) {
        expect($payload['prices']['0'])->toHaveKey((string) $product['id']);
    }
});

it('bounds the partner list so a large customer base cannot stall the boot', function () {
    $payload = app(BootLoader::class)->load($this->config, $this->session);

    expect(count($payload['partners']))->toBeLessThanOrEqual(BootLoader::PARTNER_LIMIT);
});

it('snapshots free quantity per product', function () {
    $payload = app(BootLoader::class)->load($this->config, $this->session);

    expect($payload['stock'])->toBeArray();

    expect((float) ($payload['stock'][$this->product->id] ?? 0))->toBe(40.0);
});

it('allows price editing when the register does not restrict it', function () {
    $this->config->forceFill(['enable_price_control' => false])->save();

    $payload = app(BootLoader::class)->load($this->config->refresh(), $this->session);

    expect($payload['config']['can_edit_price'])->toBeTrue();
});

it('allows a manager to edit prices on a restricted register', function () {
    $this->config->forceFill(['enable_price_control' => true])->save();

    $payload = app(BootLoader::class)->load($this->config->refresh(), $this->session);

    expect(Auth::user()->can('update', $this->config))->toBeTrue()
        ->and($payload['config']['can_edit_price'])->toBeTrue();
});

it('refuses price editing on a restricted register for a cashier who cannot manage it', function () {
    $this->config->forceFill(['enable_price_control' => true])->save();

    Gate::before(fn (): ?bool => false);

    $payload = app(BootLoader::class)->load($this->config->refresh(), $this->session);

    expect($payload['config']['can_edit_price'])->toBeFalse();
});
