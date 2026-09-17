<?php

use Illuminate\Support\Facades\Event;
use Webkul\PointOfSale\Enums\CashMovementType;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Events\CashMovementRecorded;
use Webkul\PointOfSale\Events\SessionClosed;
use Webkul\PointOfSale\Events\SessionOpened;
use Webkul\PointOfSale\Exceptions\InvalidCashMovementException;
use Webkul\PointOfSale\Exceptions\InvalidSessionStateException;
use Webkul\PointOfSale\Exceptions\SessionAlreadyOpenException;
use Webkul\PointOfSale\Exceptions\SessionNotOpenException;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Session;

require_once __DIR__.'/../../../../support/tests/Helpers/TestBootstrapHelper.php';
require_once __DIR__.'/../../../../inventories/tests/Helpers/InventoryHelper.php';
require_once __DIR__.'/../../Helpers/PosHelper.php';

beforeEach(function () {
    TestBootstrapHelper::ensurePluginInstalled('inventories');
    TestBootstrapHelper::ensurePluginInstalled('accounts');
    TestBootstrapHelper::ensurePluginInstalled('point-of-sale');

    InventoryHelper::actingAsAdmin();

    $this->warehouse = InventoryHelper::warehouse();
});

it('opens a session in opening control when cash control is on', function () {
    $config = PosHelper::configWithCashMethod($this->warehouse);

    $session = PointOfSale::openSession($config);

    expect($session->state)->toBe(SessionState::OPENING_CONTROL)
        ->and($session->has_cash_control)->toBeTrue()
        ->and($session->cash_journal_id)->not->toBeNull();
});

it('opens straight into progress when cash control is off', function () {
    $config = PosHelper::config($this->warehouse, ['enable_cash_control' => false]);

    $session = PointOfSale::openSession($config);

    expect($session->state)->toBe(SessionState::OPENED)
        ->and($session->has_cash_control)->toBeFalse()
        ->and($session->started_at)->not->toBeNull();
});

it('names the session from the terminal session sequence', function () {
    $config = PosHelper::configWithCashMethod($this->warehouse, ['code' => 'SHOP']);

    $session = PointOfSale::openSession($config);

    expect($session->name)->toStartWith('SHOP/SESSION/');
});

it('refuses a second live session for the same terminal', function () {
    $config = PosHelper::configWithCashMethod($this->warehouse);

    PointOfSale::openSession($config);

    expect(fn () => PointOfSale::openSession($config))
        ->toThrow(SessionAlreadyOpenException::class);
});

it('records the opening balance when the opening control is confirmed', function () {
    $config = PosHelper::configWithCashMethod($this->warehouse);

    $session = PointOfSale::openSession($config);

    $session = PointOfSale::confirmSessionOpeningControl($session, 500.0, 'Float counted');

    expect($session->state)->toBe(SessionState::OPENED)
        ->and((float) $session->cash_balance_start)->toBe(500.0)
        ->and($session->opening_notes)->toBe('Float counted')
        ->and($session->started_at)->not->toBeNull();
});

it('refuses to confirm the opening control twice', function () {
    $session = PosHelper::openSession($this->warehouse);

    expect(fn () => PointOfSale::confirmSessionOpeningControl($session, 100.0))
        ->toThrow(InvalidSessionStateException::class);
});

it('moves an open session into closing control', function () {
    $session = PosHelper::openSession($this->warehouse);

    $session = PointOfSale::requestSessionClosing($session);

    expect($session->state)->toBe(SessionState::CLOSING_CONTROL)
        ->and($session->stopped_at)->not->toBeNull();
});

it('records cash in and cash out movements against the drawer', function () {
    $session = PosHelper::openSession($this->warehouse, 200.0);

    PointOfSale::cashIn($session, 150.0, 'Change float');
    PointOfSale::cashOut($session, 50.0, 'Supplier payment');

    $session->refresh()->load('cashMovements');

    expect($session->cashMovements)->toHaveCount(2)
        ->and($session->cashMovementTotal())->toBe(100.0)
        ->and($session->expectedCashBalance())->toBe(300.0);
});

it('refuses a cash movement that is not positive', function () {
    $session = PosHelper::openSession($this->warehouse);

    expect(fn () => PointOfSale::cashIn($session, 0.0))
        ->toThrow(InvalidCashMovementException::class);
});

it('refuses a cash movement on a session that is not open', function () {
    $config = PosHelper::configWithCashMethod($this->warehouse);

    $session = PointOfSale::openSession($config);

    expect(fn () => PointOfSale::cashIn($session, 100.0))
        ->toThrow(SessionNotOpenException::class);
});

it('closes the session with the counted balance and its difference', function () {
    $session = PosHelper::openSession($this->warehouse, 200.0);

    PointOfSale::cashIn($session, 100.0);

    $session = PointOfSale::closeSession($session->refresh(), 320.0, 'Counted twice');

    expect($session->state)->toBe(SessionState::CLOSED)
        ->and((float) $session->cash_balance_end)->toBe(300.0)
        ->and((float) $session->cash_balance_end_real)->toBe(320.0)
        ->and((float) $session->cash_difference)->toBe(20.0)
        ->and($session->closing_notes)->toBe('Counted twice')
        ->and($session->closed_by_id)->not->toBeNull();
});

it('closes a session without cash control and leaves the drawer figures empty', function () {
    $config = PosHelper::config($this->warehouse, ['enable_cash_control' => false]);

    $session = PointOfSale::closeSession(PointOfSale::openSession($config));

    expect($session->state)->toBe(SessionState::CLOSED)
        ->and($session->cash_balance_end)->toBeNull()
        ->and($session->cash_difference)->toBeNull();
});

it('refuses to close a session twice', function () {
    $session = PointOfSale::closeSession(PosHelper::openSession($this->warehouse));

    expect(fn () => PointOfSale::closeSession($session))
        ->toThrow(InvalidSessionStateException::class);
});

it('opens a rescue session linked to the closed one', function () {
    $session = PointOfSale::closeSession(PosHelper::openSession($this->warehouse));

    $rescue = PointOfSale::openRescueSession($session);

    expect($rescue->is_rescue)->toBeTrue()
        ->and($rescue->rescue_for_session_id)->toBe($session->id)
        ->and($rescue->state)->toBe(SessionState::OPENED)
        ->and($rescue->config_id)->toBe($session->config_id);
});

it('finds the live session for a terminal and nothing once it closes', function () {
    $config = PosHelper::configWithCashMethod($this->warehouse);

    $session = PointOfSale::openSession($config);

    expect(PointOfSale::liveSessionFor($config)?->id)->toBe($session->id);

    PointOfSale::closeSession(PointOfSale::confirmSessionOpeningControl($session, 0.0));

    expect(PointOfSale::liveSessionFor($config))->toBeNull();
});

it('dispatches session and cash movement events', function () {
    Event::fake([SessionOpened::class, SessionClosed::class, CashMovementRecorded::class]);

    $session = PosHelper::openSession($this->warehouse);

    PointOfSale::cashIn($session, 25.0);

    PointOfSale::closeSession($session->refresh());

    Event::assertDispatched(SessionOpened::class);
    Event::assertDispatched(CashMovementRecorded::class);
    Event::assertDispatched(SessionClosed::class);
});

it('signs cash movements by type', function () {
    $session = PosHelper::openSession($this->warehouse);

    $in = PointOfSale::cashIn($session, 60.0);
    $out = PointOfSale::cashOut($session, 20.0);

    expect($in->type)->toBe(CashMovementType::IN)
        ->and($in->signedAmount())->toBe(60.0)
        ->and($out->type)->toBe(CashMovementType::OUT)
        ->and($out->signedAmount())->toBe(-20.0);
});

it('snapshots the stock update mode from the terminal', function () {
    $session = PosHelper::openSession($this->warehouse);

    expect($session->stock_update_mode)->toBe($session->config->stock_update_mode);
});

it('keeps sessions queryable by terminal and state', function () {
    $session = PosHelper::openSession($this->warehouse);

    $live = Session::query()
        ->where('config_id', $session->config_id)
        ->where('state', SessionState::OPENED)
        ->pluck('id');

    expect($live->all())->toContain($session->id);
});
