<?php

namespace Webkul\PointOfSale\Services;

use Illuminate\Support\Collection;
use Throwable;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\Session;

class SessionReconciler
{
    public function reconcile(Session $session, Move $move): void
    {
        $move->loadMissing('lines.account');

        $reconcilable = $move->lines->filter(fn (MoveLine $line): bool => (bool) $line->account?->reconcile);

        [$clearingLines, $sessionLines] = $reconcilable->partition(fn (MoveLine $line): bool => $line->partner_id !== null);

        $sessionLines
            ->groupBy('account_id')
            ->each(fn (Collection $lines) => $this->reconcileLines($lines));

        $this->reconcileInvoices($session, $clearingLines);
    }

    protected function reconcileInvoices(Session $session, Collection $clearingLines): void
    {
        if ($clearingLines->isEmpty()) {
            return;
        }

        $invoiceIds = Order::withoutGlobalScopes()
            ->where('session_id', $session->id)
            ->where('is_invoiced', true)
            ->whereNotNull('account_move_id')
            ->pluck('account_move_id');

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $clearingLines
            ->groupBy(fn (MoveLine $line): string => $line->account_id.'|'.$line->partner_id)
            ->each(function (Collection $lines) use ($invoiceIds): void {
                $first = $lines->first();

                $invoiceLines = MoveLine::query()
                    ->whereIn('move_id', $invoiceIds)
                    ->where('account_id', $first->account_id)
                    ->where('partner_id', $first->partner_id)
                    ->where('reconciled', false)
                    ->get();

                $this->reconcileLines($lines->map->refresh()->reject->reconciled->merge($invoiceLines));
            });
    }

    protected function reconcileLines(Collection $lines): void
    {
        if ($lines->count() < 2) {
            return;
        }

        try {
            AccountFacade::reconcile($lines);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
