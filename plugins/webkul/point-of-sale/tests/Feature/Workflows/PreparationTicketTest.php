<?php

use Illuminate\Support\Facades\Event;
use Webkul\PointOfSale\Events\PreparationTicketsRouted;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Printer;

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
    $this->config->update(['is_restaurant' => true]);
    $this->cash = $this->config->paymentMethods->first();
    $this->product = InventoryHelper::product(['price' => 100.0]);

    InventoryHelper::stockUp($this->product, $this->warehouse->lotStockLocation, 20);

    $this->kitchen = Printer::create([
        'name'       => 'Kitchen',
        'company_id' => PosHelper::company()->id,
    ]);

    $this->config->printers()->attach($this->kitchen);
});

it('routes every line to a printer without category filters', function () {
    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 2, 100.0)],
        [PosHelper::payment($this->cash, 200.0)],
    ));

    $tickets = PointOfSale::routePreparationTickets($order->refresh());

    expect($tickets)->toHaveCount(0);
});

it('builds a ticket for the printer categories', function () {
    $category = PosHelper::posCategory();

    $this->kitchen->categories()->attach($category);

    $this->product->posCategories()->attach($category);

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
    ));

    $line = $order->refresh()->lines()->first();

    $line->forceFill(['is_skipped_in_preparation' => false])->save();

    $tickets = PointOfSale::routePreparationTickets($order->refresh());

    expect($tickets)->toHaveCount(1)
        ->and($tickets->first()['printer_id'])->toBe($this->kitchen->id)
        ->and($tickets->first()['lines'])->toHaveCount(1);
});

it('marks routed lines so they are not sent twice', function () {
    $category = PosHelper::posCategory();

    $this->kitchen->categories()->attach($category);

    $this->product->posCategories()->attach($category);

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
    ));

    expect($order->refresh()->lines()->first()->is_skipped_in_preparation)->toBeTrue()
        ->and(PointOfSale::routePreparationTickets($order->refresh()))->toHaveCount(0);
});

it('dispatches the routed event for a restaurant order', function () {
    Event::fake([PreparationTicketsRouted::class]);

    $category = PosHelper::posCategory();

    $this->kitchen->categories()->attach($category);

    $this->product->posCategories()->attach($category);

    PointOfSale::syncOrder(PosHelper::orderPayload(
        $this->config->refresh(),
        $this->session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($this->cash, 100.0)],
    ));

    Event::assertDispatched(PreparationTicketsRouted::class);
});

it('routes nothing for a retail terminal', function () {
    $config = PosHelper::configWithCashMethod(InventoryHelper::warehouse());

    $session = PointOfSale::confirmSessionOpeningControl(PointOfSale::openSession($config), 0.0);

    $order = PointOfSale::syncOrder(PosHelper::orderPayload(
        $config,
        $session,
        [PosHelper::line($this->product->id, 1, 100.0)],
        [PosHelper::payment($config->paymentMethods->first(), 100.0)],
    ));

    expect(PointOfSale::routePreparationTickets($order->refresh()))->toHaveCount(0);
});
