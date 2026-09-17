<?php

namespace Webkul\PointOfSale\Services;

use Throwable;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\PointOfSale\Models\Session;

class SessionReconciler
{
    public function reconcile(Session $session, Move $move): void
    {
        $move->loadMissing('lines.account');

        $move->lines
            ->filter(fn (MoveLine $line): bool => (bool) $line->account?->reconcile)
            ->groupBy('account_id')
            ->each(function ($lines) {
                if ($lines->count() < 2) {
                    return;
                }

                try {
                    AccountFacade::reconcile($lines);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
    }
}
