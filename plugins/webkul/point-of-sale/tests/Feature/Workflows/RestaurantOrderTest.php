<?php

use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Enums\TableShape;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Order;

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
    $this->config->update(['is_restaurant' => true, 'enable_split_bill' => true]);
    $this->cash = $this->config->paymentMethods->first();
    $this->product = InventoryHelper::product();
    $this->floor = PosHelper::floor();
    $this->table = PosHelper::table($this->floor, ['table_number' => 'T2', 'shape' => TableShape::ROUND]);

    InventoryHelper::stockUp($this->product, $this->warehouse->lotStockLocation, 50);
});

it('assigns an order to a table', function () {
    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 2, 50.0)],
        [PosHelper::payment($this->cash, 100.0)],
        ['table_id' => $this->table->id, 'customer_count' => 3],
    ));

    expect($order->table_id)->toBe($this->table->id)
        ->and($order->customer_count)->toBe(3);
});

it('lists the tables of a floor', function () {
    PosHelper::table($this->floor, ['table_number' => 'T3']);

    expect($this->floor->refresh()->tables)->toHaveCount(2);
});

it('reports a table as occupied while a draft order is open', function () {
    $order = Order::create([
        'session_id' => $this->session->id,
        'config_id'  => $this->config->id,
        'table_id'   => $this->table->id,
    ]);

    expect($this->table->refresh()->isOccupied())->toBeTrue();

    $order->forceFill(['state' => OrderState::PAID])->save();

    expect($this->table->refresh()->isOccupied())->toBeFalse();
});

it('sums the open amount of a table', function () {
    Order::create([
        'session_id'   => $this->session->id,
        'config_id'    => $this->config->id,
        'table_id'     => $this->table->id,
        'amount_total' => 240.0,
    ]);

    expect($this->table->refresh()->openAmount())->toBe(240.0);
});

it('splits a draft order into a second bill', function () {
    $order = Order::create([
        'session_id' => $this->session->id,
        'config_id'  => $this->config->id,
        'table_id'   => $this->table->id,
    ]);

    $line = $order->lines()->create([
        'product_id' => $this->product->id,
        'qty'        => 4,
        'price_unit' => 25.0,
    ]);

    $split = PointOfSale::splitOrder($order, [$line->id => 1]);

    expect((float) $split->lines()->first()->qty)->toBe(1.0)
        ->and((float) $order->refresh()->lines()->first()->qty)->toBe(3.0)
        ->and($split->table_id)->toBe($this->table->id);
});

it('removes an emptied line from the original bill when fully split', function () {
    $order = Order::create([
        'session_id' => $this->session->id,
        'config_id'  => $this->config->id,
        'table_id'   => $this->table->id,
    ]);

    $line = $order->lines()->create([
        'product_id' => $this->product->id,
        'qty'        => 2,
        'price_unit' => 30.0,
    ]);

    PointOfSale::splitOrder($order, [$line->id => 2]);

    expect($order->refresh()->lines()->count())->toBe(0);
});

it('keeps the round shape on a table', function () {
    expect($this->table->shape)->toBe(TableShape::ROUND)
        ->and($this->table->seats)->toBe(4);
});
