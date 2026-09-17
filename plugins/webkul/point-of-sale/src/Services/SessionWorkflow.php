<?php

namespace Webkul\PointOfSale\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\PointOfSale\Enums\CashMovementType;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Events\CashMovementRecorded;
use Webkul\PointOfSale\Events\SessionClosed;
use Webkul\PointOfSale\Events\SessionClosing;
use Webkul\PointOfSale\Events\SessionOpened;
use Webkul\PointOfSale\Exceptions\InvalidCashMovementException;
use Webkul\PointOfSale\Exceptions\InvalidSessionStateException;
use Webkul\PointOfSale\Exceptions\SessionAlreadyOpenException;
use Webkul\PointOfSale\Exceptions\SessionNotOpenException;
use Webkul\PointOfSale\Models\CashMovement;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Session;

class SessionWorkflow
{
    public function __construct(
        protected CashMovementPoster $cashMovements,
        protected SessionPreflight $preflight,
    ) {}

    public function open(Config $config, ?int $userId = null): Session
    {
        return DB::transaction(function () use ($config, $userId): Session {
            $this->preflight->assertCanOpen($config);

            if ($this->liveSessionFor($config)) {
                throw new SessionAlreadyOpenException(
                    __('point-of-sale::system.session-workflow.open.already-open', ['config' => $config->name])
                );
            }

            $session = Session::create([
                'config_id' => $config->id,
                'user_id'   => $userId ?? Auth::id(),
            ]);

            if ($session->state === SessionState::OPENED) {
                $session->forceFill(['started_at' => now()])->save();
            }

            SessionOpened::dispatch($session);

            return $session->refresh();
        });
    }

    public function confirmOpeningControl(Session $session, float $cashBalanceStart = 0.0, ?string $notes = null): Session
    {
        return DB::transaction(function () use ($session, $cashBalanceStart, $notes): Session {
            $this->assertState($session, [SessionState::OPENING_CONTROL]);

            $session->forceFill([
                'state'              => SessionState::OPENED,
                'started_at'         => now(),
                'cash_balance_start' => $cashBalanceStart,
                'opening_notes'      => $notes,
            ])->save();

            SessionOpened::dispatch($session);

            return $session->refresh();
        });
    }

    public function requestClosing(Session $session): Session
    {
        return DB::transaction(function () use ($session): Session {
            $this->assertState($session, [SessionState::OPENED]);

            $session->forceFill([
                'state'      => SessionState::CLOSING_CONTROL,
                'stopped_at' => now(),
            ])->save();

            SessionClosing::dispatch($session);

            return $session->refresh();
        });
    }

    public function close(Session $session, ?float $cashBalanceEndReal = null, ?string $notes = null): Session
    {
        return DB::transaction(function () use ($session, $cashBalanceEndReal, $notes): Session {
            $this->assertState($session, [SessionState::OPENED, SessionState::CLOSING_CONTROL]);

            $session->loadMissing('cashMovements');

            $expected = $session->expectedCashBalance();

            $counted = $session->has_cash_control
                ? ($cashBalanceEndReal ?? $expected)
                : null;

            $session->forceFill([
                'state'                 => SessionState::CLOSED,
                'stopped_at'            => $session->stopped_at ?? now(),
                'closed_by_id'          => Auth::id(),
                'closing_notes'         => $notes,
                'cash_balance_end'      => $session->has_cash_control ? $expected : null,
                'cash_balance_end_real' => $counted,
                'cash_difference'       => $counted === null ? null : $counted - $expected,
            ])->save();

            SessionClosed::dispatch($session);

            return $session->refresh();
        });
    }

    public function openRescueFor(Session $session): Session
    {
        return DB::transaction(function () use ($session): Session {
            $rescue = Session::create([
                'config_id'             => $session->config_id,
                'rescue_for_session_id' => $session->id,
                'is_rescue'             => true,
                'state'                 => SessionState::OPENED,
                'started_at'            => now(),
                'user_id'               => Auth::id() ?? $session->user_id,
            ]);

            SessionOpened::dispatch($rescue);

            return $rescue->refresh();
        });
    }

    public function cashIn(Session $session, float $amount, ?string $reason = null): CashMovement
    {
        return $this->recordCashMovement($session, CashMovementType::IN, $amount, $reason);
    }

    public function cashOut(Session $session, float $amount, ?string $reason = null): CashMovement
    {
        return $this->recordCashMovement($session, CashMovementType::OUT, $amount, $reason);
    }

    public function assertOpen(Session $session): void
    {
        if ($session->state !== SessionState::OPENED) {
            throw new SessionNotOpenException(
                __('point-of-sale::system.session-workflow.assert-open.not-open', ['session' => $session->name])
            );
        }
    }

    public function liveSessionFor(Config $config): ?Session
    {
        return Session::withoutGlobalScopes()
            ->where('config_id', $config->id)
            ->whereIn('state', [
                SessionState::OPENING_CONTROL,
                SessionState::OPENED,
                SessionState::CLOSING_CONTROL,
            ])
            ->latest('id')
            ->first();
    }

    protected function recordCashMovement(Session $session, CashMovementType $type, float $amount, ?string $reason): CashMovement
    {
        return DB::transaction(function () use ($session, $type, $amount, $reason): CashMovement {
            $this->assertOpen($session);

            if (float_compare($amount, 0, precisionDigits: 2) <= 0) {
                throw new InvalidCashMovementException(
                    __('point-of-sale::system.session-workflow.cash-movement.invalid-amount')
                );
            }

            $cashMovement = CashMovement::create([
                'session_id' => $session->id,
                'type'       => $type,
                'amount'     => $amount,
                'reason'     => $reason,
            ]);

            $this->cashMovements->post($cashMovement);

            CashMovementRecorded::dispatch($cashMovement);

            return $cashMovement->refresh();
        });
    }

    protected function assertState(Session $session, array $states): void
    {
        if (! in_array($session->state, $states, true)) {
            throw new InvalidSessionStateException(
                __('point-of-sale::system.session-workflow.assert-state.invalid', [
                    'session' => $session->name,
                    'state'   => $session->state->getLabel(),
                ])
            );
        }
    }
}
