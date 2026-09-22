<?php

namespace Webkul\PointOfSale\Services;

use Throwable;
use Webkul\Account\Enums\PaymentType;
use Webkul\Account\Facades\Account as AccountFacade;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\Move;
use Webkul\Account\Models\MoveLine;
use Webkul\Account\Models\Payment as AccountPayment;
use Webkul\PointOfSale\Models\Order;

class PosInvoicePayer
{
    public function __construct(
        protected AccountResolver $accounts,
    ) {}

    public function pay(Move $invoice, Order $order): void
    {
        $receivableLine = $invoice->lines->first(
            fn (MoveLine $line): bool => (bool) $line->account?->reconcile
        );

        if (! $receivableLine) {
            return;
        }

        $order->payments
            ->groupBy('payment_method_id')
            ->map(fn ($rows): array => [
                'payment' => $rows->firstWhere('is_change', false) ?? $rows->first(),
                'amount'  => float_round((float) $rows->sum('amount'), precisionDigits: 4),
            ])
            ->filter(fn (array $tender): bool => float_compare($tender['amount'], 0, precisionDigits: 2) > 0)
            ->each(function (array $tender) use ($invoice, $order, $receivableLine): void {
                $payment = $tender['payment'];

                $method = $payment->paymentMethod;

                if (! $method?->payment_method_line_id) {
                    return;
                }

                $outstanding = $this->outstandingAccountFor($order, $method, $receivableLine);

                if (! $outstanding) {
                    return;
                }

                try {
                    $accountPayment = AccountPayment::create([
                        'journal_id'             => $method->journal_id,
                        'payment_method_line_id' => $method->payment_method_line_id,
                        'payment_type'           => PaymentType::RECEIVE,
                        'partner_id'             => $order->partner_id,
                        'company_id'             => $order->company_id,
                        'currency_id'            => $order->currency_id,
                        'date'                   => $order->ordered_at,
                        'amount'                 => $tender['amount'],
                        'memo'                   => $order->name ?? $order->reference,
                        'outstanding_account_id' => $outstanding?->id,
                        'destination_account_id' => $receivableLine->account_id,
                    ]);

                    $accountPayment->generateJournalEntry();

                    AccountFacade::postPayment($accountPayment->refresh());

                    $payment->forceFill([
                        'payment_id'      => $accountPayment->id,
                        'account_move_id' => $accountPayment->move_id,
                    ])->save();

                    $this->reconcile($invoice, $accountPayment, $receivableLine);
                } catch (Throwable $exception) {
                    report($exception);
                }
            });

        $invoice->refresh()->computePaymentState();

        $invoice->save();
    }

    protected function outstandingAccountFor(Order $order, $method, MoveLine $receivableLine): ?Account
    {
        $posReceivable = $this->accounts->receivableAccountFor($order->config, $method);

        if ($posReceivable && $posReceivable->id !== $receivableLine->account_id) {
            return $posReceivable;
        }

        $outstanding = $this->accounts->outstandingAccountFor($method);

        return $outstanding && $outstanding->id !== $receivableLine->account_id
            ? $outstanding
            : null;
    }

    protected function reconcile(Move $invoice, AccountPayment $accountPayment, MoveLine $receivableLine): void
    {
        $paymentLines = MoveLine::withoutGlobalScopes()
            ->where('move_id', $accountPayment->move_id)
            ->where('account_id', $receivableLine->account_id)
            ->get();

        if ($paymentLines->isEmpty()) {
            return;
        }

        try {
            AccountFacade::reconcile($paymentLines->push($receivableLine));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
