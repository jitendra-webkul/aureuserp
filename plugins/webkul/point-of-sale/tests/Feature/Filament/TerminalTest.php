<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Webkul\PluginManager\Models\Plugin;
use Webkul\PluginManager\Package;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Pos\Pages\Home;

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

    Livewire::test(Home::class, ['session' => $session])->assertOk();
});

it('shows the opening control until the drawer is counted', function () {
    $session = PointOfSale::openSession($this->config);

    Livewire::test(Home::class, ['session' => $session])
        ->assertOk()
        ->call('confirmOpening')
        ->assertOk();

    expect($session->refresh()->state)->toBe(SessionState::OPENED);
});

it('counts the drawer through the coins and notes dialog', function () {
    $session = PointOfSale::openSession($this->config);

    Livewire::test(Home::class, ['session' => $session])
        ->call('openMoneyDetails')
        ->assertDispatched('open-modal', id: 'pos-money-details')
        ->call('stepMoneyDetail', (string) PosHelper::bill(50)->id, 1)
        ->call('stepMoneyDetail', (string) PosHelper::bill(200)->id, 2)
        ->call('confirmMoneyDetails')
        ->assertDispatched('close-modal', id: 'pos-money-details')
        ->assertSet('openingCash', 450.0);
});

it('records the counted drawer as the opening balance', function () {
    $session = PointOfSale::openSession($this->config);

    Livewire::test(Home::class, ['session' => $session])
        ->call('openMoneyDetails')
        ->call('stepMoneyDetail', (string) PosHelper::bill(100)->id, 1)
        ->call('confirmMoneyDetails')
        ->call('confirmOpening');

    expect((float) $session->refresh()->cash_balance_start)->toBe(100.0)
        ->and($session->opening_notes)->toContain('100.00');
});

it('hands the catalogue to the client through the boot payload', function () {
    $session = PosHelper::openSession($this->warehouse);

    $payload = Livewire::test(Home::class, ['session' => $session])
        ->assertOk()
        ->instance()
        ->bootPayload();

    expect(collect($payload['products'])->pluck('id'))->toContain($this->product->id)
        ->and($payload['session']['id'])->toBe($session->id)
        ->and($payload['config']['id'])->toBe($session->config_id);
});

it('ships the locale and translations the client renders with', function () {
    $session = PosHelper::openSession($this->warehouse);

    $payload = Livewire::test(Home::class, ['session' => $session])
        ->instance()
        ->bootPayload();

    expect($payload['locale']['code'])->toBe(app()->getLocale())
        ->and($payload['locale']['direction'])->toBeIn(['ltr', 'rtl'])
        ->and($payload['translations'])->toHaveKeys(['cart', 'payment', 'receipt', 'common'])
        ->and($payload['translations']['cart']['total'])
        ->toBe(__('point-of-sale::filament/pos/pages/terminal.cart.total'));
});

it('records a cash movement from the terminal', function () {
    $session = PosHelper::openSession($this->warehouse);

    Livewire::test(Home::class, ['session' => $session])
        ->call('openCashMovement', 'in')
        ->assertDispatched('open-modal', id: 'pos-cash-movement')
        ->set('cashMovementAmount', 40.0)
        ->set('cashMovementReason', 'Float top up')
        ->call('recordCashMovement')
        ->assertDispatched('close-modal', id: 'pos-cash-movement');

    expect((float) $session->refresh()->cashMovements()->sum('amount'))->toBe(40.0);
});

it('counts the drawer again when closing the register', function () {
    $session = PosHelper::openSession($this->warehouse);

    Livewire::test(Home::class, ['session' => $session])
        ->call('goToClosing')
        ->assertDispatched('open-modal', id: 'pos-closing')
        ->call('openMoneyDetails', 'closing')
        ->call('stepMoneyDetail', (string) PosHelper::bill(100)->id, 2)
        ->call('confirmMoneyDetails')
        ->assertSet('closingCash', 200.0);
});
