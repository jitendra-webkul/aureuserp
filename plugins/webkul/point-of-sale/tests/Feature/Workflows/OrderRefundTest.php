<?php

use Illuminate\Support\Facades\Event;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\OperationType as OperationTypeEnum;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Events\OrderRefunded;
use Webkul\PointOfSale\Exceptions\OrderNotRefundableException;
use Webkul\PointOfSale\Exceptions\RefundExceedsSoldQuantityException;
use Webkul\PointOfSale\Facades\PointOfSale;

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
    $this->cash = $this->config->paymentMethods->first();
    $this->product = InventoryHelper::product();

    InventoryHelper::stockUp($this->product, $this->warehouse->lotStockLocation, 40);

    $this->order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config,
        $this->session,
        [PosHelper::line($this->product->id, 4, 50.0)],
        [PosHelper::payment($this->cash, 200.0)],
    ));
});

it('creates a refund order with negative lines', function () {
    $line = $this->order->lines()->first();

    $refund = PointOfSale::refundOrder($this->order, [$line->id => 1]);

    expect($refund->refunded_order_id)->toBe($this->order->id)
        ->and((float) $refund->amount_total)->toBe(-50.0)
        ->and((float) $refund->lines()->first()->qty)->toBe(-1.0)
        ->and($refund->lines()->first()->refunded_order_line_id)->toBe($line->id);
});

it('increases the refunded quantity on the original line', function () {
    $line = $this->order->lines()->first();

    PointOfSale::refundOrder($this->order, [$line->id => 3]);

    expect((float) $line->refresh()->refunded_qty)->toBe(3.0)
        ->and($line->refundableQty())->toBe(1.0);
});

it('refuses to refund more than was sold', function () {
    $line = $this->order->lines()->first();

    expect(fn () => PointOfSale::refundOrder($this->order, [$line->id => 9]))
        ->toThrow(RefundExceedsSoldQuantityException::class);
});

it('refuses a refund with nothing selected', function () {
    $line = $this->order->lines()->first();

    expect(fn () => PointOfSale::refundOrder($this->order, [$line->id => 0]))
        ->toThrow(OrderNotRefundableException::class);
});

it('refuses to refund an order that was never settled', function () {
    $draft = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config,
        $this->session,
        [PosHelper::line($this->product->id, 1, 10.0)],
        [PosHelper::payment($this->cash, 10.0)],
    ));

    $draft->forceFill(['state' => OrderState::DRAFT])->save();

    expect(fn () => PointOfSale::refundOrder($draft, [$draft->lines()->first()->id => 1]))
        ->toThrow(OrderNotRefundableException::class);
});

it('returns the stock through the point of sale return operation', function () {
    $line = $this->order->lines()->first();

    $before = PosHelper::quantityOnHand($this->product->id, $this->warehouse->lotStockLocation);

    $refund = PointOfSale::refundOrder($this->order, [$line->id => 2]);

    PointOfSale::syncOrder([
        'uuid'       => $refund->uuid,
        'config_id'  => $refund->config_id,
        'session_id' => $refund->session_id,
        'payments'   => [PosHelper::payment($this->cash, -100.0)],
    ]);

    $refund->refresh();

    $operation = $refund->operation;

    expect($operation)->not->toBeNull()
        ->and($operation->operationType->type)->toBe(OperationTypeEnum::INCOMING)
        ->and($operation->state)->toBe(OperationState::DONE)
        ->and(PosHelper::quantityOnHand($this->product->id, $this->warehouse->lotStockLocation))->toBe($before + 2.0);
});

it('names the refund after the original order', function () {
    $line = $this->order->lines()->first();

    $refund = PointOfSale::refundOrder($this->order, [$line->id => 1]);

    PointOfSale::syncOrder([
        'uuid'       => $refund->uuid,
        'config_id'  => $refund->config_id,
        'session_id' => $refund->session_id,
        'payments'   => [PosHelper::payment($this->cash, -50.0)],
    ]);

    expect($refund->refresh()->name)->toBe($this->order->name.' REFUND');
});

it('allows a refund in a later session', function () {
    $line = $this->order->lines()->first();

    PointOfSale::closeSession($this->session->refresh());

    $refund = PointOfSale::refundOrder($this->order, [$line->id => 1]);

    expect($refund->session_id)->not->toBe($this->session->id)
        ->and($refund->session->isLive())->toBeTrue();
});

it('copies the taxes and the cost snapshot onto the refund line', function () {
    $line = $this->order->lines()->first();

    $refund = PointOfSale::refundOrder($this->order, [$line->id => 2]);

    $refundLine = $refund->lines()->first();

    expect((float) $refundLine->unit_cost)->toBe((float) $line->unit_cost)
        ->and((float) $refundLine->price_unit)->toBe((float) $line->price_unit)
        ->and($refundLine->taxes()->count())->toBe($line->taxes()->count());
});

it('reports the refundable lines of an order', function () {
    $line = $this->order->lines()->first();

    PointOfSale::refundOrder($this->order, [$line->id => 1]);

    expect(PointOfSale::refundableLines($this->order->refresh()))->toBe([$line->id => 3.0]);
});

it('dispatches the refunded event', function () {
    Event::fake([OrderRefunded::class]);

    $line = $this->order->lines()->first();

    PointOfSale::refundOrder($this->order, [$line->id => 1]);

    Event::assertDispatched(OrderRefunded::class);
});
