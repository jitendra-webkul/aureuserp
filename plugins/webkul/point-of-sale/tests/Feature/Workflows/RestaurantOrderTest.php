<?php

use Webkul\Account\Models\FiscalPosition;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Enums\TableShape;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Services\FiscalPositionResolver;

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

    $this->config->floors()->attach($this->floor->id);

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

it('provisions a default floor with one table when a restaurant terminal has none', function () {
    $this->config->floors()->detach();

    $this->config->refresh()->syncRestaurantFloors();

    $floors = $this->config->refresh()->floors;

    expect($floors)->toHaveCount(1)
        ->and($floors->first()->name)->toBe($this->config->company->name)
        ->and($floors->first()->tables)->toHaveCount(1)
        ->and($floors->first()->tables->first()->table_number)->toBe('1')
        ->and($floors->first()->tables->first()->seats)->toBe(1);
});

it('keeps the chosen floors of a restaurant terminal', function () {
    $this->config->refresh()->syncRestaurantFloors();

    expect($this->config->refresh()->floors->pluck('id')->all())->toBe([$this->floor->id]);
});

it('releases the floors when restaurant mode is turned off', function () {
    $this->config->update(['is_restaurant' => false]);

    $this->config->refresh()->syncRestaurantFloors();

    expect($this->config->refresh()->floors)->toBeEmpty()
        ->and($this->floor->refresh()->exists)->toBeTrue();
});

it('drops a table that belongs to a floor the terminal does not use', function () {
    $foreignTable = PosHelper::table(PosHelper::floor(), ['table_number' => 'X1']);

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 50.0)],
        [PosHelper::payment($this->cash, 50.0)],
        ['table_id' => $foreignTable->id],
    ));

    expect($order->table_id)->toBeNull();
});

it('drops the table once restaurant mode is off', function () {
    $this->config->update(['is_restaurant' => false]);

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 50.0)],
        [PosHelper::payment($this->cash, 50.0)],
        ['table_id' => $this->table->id],
    ));

    expect($order->table_id)->toBeNull();
});

it('applies the takeaway fiscal position even when flexible taxes are off', function () {
    $takeaway = FiscalPosition::create([
        'name'       => 'Takeaway',
        'company_id' => PosHelper::company()->id,
    ]);

    $this->config->update([
        'enable_fiscal_position'      => false,
        'enable_takeaway'             => true,
        'takeaway_fiscal_position_id' => $takeaway->id,
    ]);

    $order = Order::create([
        'session_id'  => $this->session->id,
        'config_id'   => $this->config->id,
        'is_takeaway' => true,
    ]);

    expect(app(FiscalPositionResolver::class)->resolveFor($order->refresh())?->id)->toBe($takeaway->id);
});

it('keeps a dine-in order off the takeaway fiscal position', function () {
    $takeaway = FiscalPosition::create([
        'name'       => 'Takeaway',
        'company_id' => PosHelper::company()->id,
    ]);

    $this->config->update([
        'enable_fiscal_position'      => false,
        'enable_takeaway'             => true,
        'takeaway_fiscal_position_id' => $takeaway->id,
    ]);

    $order = Order::create([
        'session_id' => $this->session->id,
        'config_id'  => $this->config->id,
    ]);

    expect(app(FiscalPositionResolver::class)->resolveFor($order->refresh()))->toBeNull();
});
