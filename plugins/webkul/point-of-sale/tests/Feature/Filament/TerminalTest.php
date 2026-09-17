<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Pos\Pages\Terminal;
use Webkul\PointOfSale\Models\Order;

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

    FilamentHelper::actingAs();

    Filament::setCurrentPanel(Filament::getPanel('pos'));

    Filament::bootCurrentPanel();

    $this->warehouse = InventoryHelper::warehouse();
    $this->config = PosHelper::configWithCashMethod($this->warehouse);
    $this->cash = $this->config->paymentMethods->first();
    $this->product = InventoryHelper::product(['price' => 25.0]);

    DB::table('products_products')->where('id', $this->product->id)->update(['available_in_pos' => true]);

    InventoryHelper::stockUp($this->product, $this->warehouse->lotStockLocation, 50);
});

it('renders the terminal for a live session', function () {
    $session = PosHelper::openSession($this->warehouse);

    Livewire::test(Terminal::class, ['session' => $session])->assertOk();
});

it('shows the opening control until the drawer is counted', function () {
    $session = PointOfSale::openSession($this->config);

    Livewire::test(Terminal::class, ['session' => $session])
        ->assertOk()
        ->call('confirmOpening')
        ->assertOk();

    expect($session->refresh()->state)->toBe(SessionState::OPENED);
});

it('counts the drawer through the coins and notes dialog', function () {
    $session = PointOfSale::openSession($this->config);

    Livewire::test(Terminal::class, ['session' => $session])
        ->call('openMoneyDetails')
        ->assertDispatched('open-modal', id: 'pos-money-details')
        ->call('stepMoneyDetail', '50', 1)
        ->call('stepMoneyDetail', '200', 2)
        ->call('confirmMoneyDetails')
        ->assertDispatched('close-modal', id: 'pos-money-details')
        ->assertSet('openingCash', 450.0);
});

it('records the counted drawer as the opening balance', function () {
    $session = PointOfSale::openSession($this->config);

    Livewire::test(Terminal::class, ['session' => $session])
        ->call('openMoneyDetails')
        ->call('stepMoneyDetail', '100', 1)
        ->call('confirmMoneyDetails')
        ->call('confirmOpening');

    expect((float) $session->refresh()->cash_balance_start)->toBe(100.0)
        ->and($session->opening_notes)->toContain('100.00');
});

it('sells a product from the terminal', function () {
    $session = PosHelper::openSession($this->warehouse);

    Livewire::test(Terminal::class, ['session' => $session])
        ->call('addProduct', $this->product->id)
        ->call('goToPayment')
        ->call('addPayment', $this->cash->id)
        ->call('validateOrder')
        ->assertSet('screen', 'receipt');

    $order = Order::withoutGlobalScopes()->where('session_id', $session->id)->first();

    expect($order)->not->toBeNull()
        ->and($order->state)->toBe(OrderState::PAID)
        ->and((float) $order->amount_total)->toBe(25.0);
});

it('filters the catalogue by point of sale category', function () {
    $session = PosHelper::openSession($this->warehouse);

    $category = PosHelper::posCategory();

    $category->products()->attach($this->product);

    Livewire::test(Terminal::class, ['session' => $session])
        ->call('selectCategory', $category->id)
        ->assertSet('selectedCategoryId', $category->id);
});

it('edits a cart line through the numpad', function () {
    $session = PosHelper::openSession($this->warehouse);

    Livewire::test(Terminal::class, ['session' => $session])
        ->call('addProduct', $this->product->id)
        ->call('setNumpadMode', 'qty')
        ->call('pressNumpad', '3')
        ->assertSet('numpadMode', 'qty')
        ->assertSet('cart.'.$this->product->id.'.qty', 3.0);
});
