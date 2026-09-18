<?php

use Webkul\Inventory\Enums\DeliveryStep;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\Rule;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Enums\StockUpdateMode;
use Webkul\PointOfSale\Facades\PointOfSale;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../../../inventories/tests/Helpers/InventoryHelper.php';
require_once __DIR__.'/../../Helpers/PosHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    InventoryHelper::actingAsAdmin();

    PosHelper::stockUpdateMode(StockUpdateMode::REAL_TIME);

    $this->warehouse = InventoryHelper::warehouse();
    $this->session = PosHelper::openSession($this->warehouse);
    $this->config = $this->session->config;
    $this->config->update(['enable_ship_later' => true]);
    $this->cash = $this->config->paymentMethods->first();
    $this->product = InventoryHelper::product();

    InventoryHelper::stockUp($this->product, $this->warehouse->lotStockLocation, 30);
});

it('creates a procurement group instead of an immediate operation', function () {
    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 2, 100.0)],
        [PosHelper::payment($this->cash, 200.0)],
        ['shipped_at' => now()->addWeek()->toDateString()],
    ));

    expect($order->state)->toBe(OrderState::PAID)
        ->and($order->operation_id)->toBeNull()
        ->and($order->procurement_group_id)->not->toBeNull();
});

it('leaves the delivery open for the back office', function () {
    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
        ['shipped_at' => now()->addDays(3)->toDateString()],
    ));

    $operations = Operation::query()
        ->where('procurement_group_id', $order->procurement_group_id)
        ->get();

    expect($operations)->not->toBeEmpty()
        ->and($operations->every(fn (Operation $operation): bool => $operation->state !== OperationState::DONE))->toBeTrue();
});

it('keeps stock on hand until the delivery is validated', function () {
    $before = PosHelper::quantityOnHand($this->product->id, $this->warehouse->lotStockLocation);

    PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 2, 100.0)],
        [PosHelper::payment($this->cash, 200.0)],
        ['shipped_at' => now()->addWeek()->toDateString()],
    ));

    expect(PosHelper::quantityOnHand($this->product->id, $this->warehouse->lotStockLocation))->toBe($before);
});

it('stamps the shipping date on the order', function () {
    $date = now()->addDays(5)->toDateString();

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
        ['shipped_at' => $date],
    ));

    expect($order->shipped_at->toDateString())->toBe($date);
});

it('still delivers immediately when no shipping date is set', function () {
    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
    ));

    expect($order->operation_id)->not->toBeNull()
        ->and($order->procurement_group_id)->toBeNull();
});

it('runs the ship later procurement on the route configured for the terminal', function () {
    $this->warehouse->update(['delivery_steps' => DeliveryStep::TWO_STEPS]);

    $route = $this->warehouse->refresh()->deliveryRoute;

    expect($route)->not->toBeNull();

    $this->config->update(['ship_later_route_id' => $route->id]);

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
        ['shipped_at' => now()->addWeek()->toDateString()],
    ));

    $operations = Operation::query()
        ->where('procurement_group_id', $order->procurement_group_id)
        ->get();

    $ruleRouteIds = Rule::query()
        ->whereIn('operation_type_id', $operations->pluck('operation_type_id')->unique())
        ->pluck('route_id')
        ->unique();

    expect($operations)->not->toBeEmpty()
        ->and($ruleRouteIds)->toContain($route->id);
});

it('groups the session stock into one operation when stock updates at closing', function () {
    PosHelper::stockUpdateMode(StockUpdateMode::AT_CLOSING);

    $config = PosHelper::configWithCashMethod(InventoryHelper::warehouse());

    $session = PointOfSale::confirmSessionOpeningControl(PointOfSale::openSession($config), 0.0);

    $cash = $config->paymentMethods->first();

    $product = InventoryHelper::product();

    InventoryHelper::stockUp($product, $config->warehouse->lotStockLocation, 20);

    foreach ([1, 2] as $qty) {
        PointOfSale::syncOrder(PosHelper::orderPayload(
            $config,
            $session,
            [PosHelper::line($product->id, $qty, 10.0)],
            [PosHelper::payment($cash, 10.0 * $qty)],
        ));
    }

    $operation = PointOfSale::generateSessionPicking(PointOfSale::closeSession($session->refresh()));

    expect($operation)->not->toBeNull()
        ->and($operation->moves()->count())->toBe(1)
        ->and((float) $operation->moves()->first()->product_uom_qty)->toBe(3.0);
});

it('does not create an immediate operation in closing stock mode', function () {
    PosHelper::stockUpdateMode(StockUpdateMode::AT_CLOSING);

    $config = PosHelper::configWithCashMethod(InventoryHelper::warehouse());

    $session = PointOfSale::confirmSessionOpeningControl(PointOfSale::openSession($config), 0.0);

    $product = InventoryHelper::product();

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $config,
        $session,
        [PosHelper::line($product->id, 1, 10.0)],
        [PosHelper::payment($config->paymentMethods->first(), 10.0)],
    ));

    expect($order->operation_id)->toBeNull();
});
