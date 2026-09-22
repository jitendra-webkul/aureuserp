<?php

namespace Webkul\PointOfSale\Services;

use Illuminate\Support\Facades\DB;
use Webkul\PointOfSale\Enums\PaymentMethodType;
use Webkul\PointOfSale\Events\OrderRefunded;
use Webkul\PointOfSale\Exceptions\OrderNotRefundableException;
use Webkul\PointOfSale\Exceptions\RefundExceedsSoldQuantityException;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\OrderLine;
use Webkul\PointOfSale\Models\OrderLineLot;
use Webkul\PointOfSale\Models\Payment;
use Webkul\PointOfSale\Models\PaymentMethod;
use Webkul\PointOfSale\Models\Session;

class RefundProcessor
{
    public function __construct(
        protected OrderCalculator $calculator,
        protected OrderWorkflow $orders,
        protected SessionWorkflow $sessions,
    ) {}

    public function refund(Order $order, array $lineQuantities, ?Session $session = null): Order
    {
        return DB::transaction(function () use ($order, $lineQuantities, $session): Order {
            if (! $order->state->isSettled()) {
                throw new OrderNotRefundableException(
                    __('point-of-sale::system.order-workflow.refund.not-refundable', ['order' => $order->reference])
                );
            }

            $session = $this->resolveSession($order, $session);

            $refund = Order::create([
                'session_id'         => $session->id,
                'config_id'          => $order->config_id,
                'partner_id'         => $order->partner_id,
                'price_list_id'      => $order->price_list_id,
                'fiscal_position_id' => $order->fiscal_position_id,
                'currency_id'        => $order->currency_id,
                'refunded_order_id'  => $order->id,
                'origin'             => $order->name ?? $order->reference,
            ]);

            $refunded = 0;

            foreach ($lineQuantities as $lineId => $quantity) {
                $quantity = abs((float) $quantity);

                if (float_is_zero($quantity, precisionDigits: 4)) {
                    continue;
                }

                $line = OrderLine::withoutGlobalScopes()
                    ->where('order_id', $order->id)
                    ->whereKey($lineId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (float_compare($quantity, $line->refundableQty(), precisionDigits: 4) > 0) {
                    throw new RefundExceedsSoldQuantityException(
                        __('point-of-sale::system.order-workflow.refund.exceeds-sold', [
                            'product' => $line->full_product_name ?? $line->product?->name,
                        ])
                    );
                }

                $this->copyLine($refund, $line, $quantity);

                $line->refresh();

                $refunded++;
            }

            if ($refunded === 0) {
                throw new OrderNotRefundableException(
                    __('point-of-sale::system.order-workflow.refund.nothing-to-refund')
                );
            }

            $refund = $this->calculator->recompute($refund->refresh());

            OrderRefunded::dispatch($refund);

            return $refund;
        });
    }

    public function settle(Order $refund, ?int $paymentMethodId = null): Order
    {
        return DB::transaction(function () use ($refund, $paymentMethodId): Order {
            $refund = $this->calculator->recompute($refund->refresh());

            $method = $this->paymentMethodFor($refund, $paymentMethodId);

            if (! $method) {
                throw new OrderNotRefundableException(
                    __('point-of-sale::system.order-workflow.refund.no-payment-method', ['order' => $refund->reference])
                );
            }

            $due = float_round((float) $refund->amount_total - $refund->paidAmount(), precisionDigits: 2);

            if (! float_is_zero($due, precisionDigits: 2)) {
                Payment::create([
                    'order_id'          => $refund->id,
                    'session_id'        => $refund->session_id,
                    'payment_method_id' => $method->id,
                    'amount'            => $due,
                ]);
            }

            return $this->orders->markPaid($refund->refresh());
        });
    }

    protected function paymentMethodFor(Order $refund, ?int $paymentMethodId): ?PaymentMethod
    {
        $methods = $refund->config?->paymentMethods ?? collect();

        if ($paymentMethodId) {
            return $methods->firstWhere('id', $paymentMethodId);
        }

        return $methods->firstWhere('type', PaymentMethodType::CASH) ?? $methods->first();
    }

    public function refundableLines(Order $order): array
    {
        return $order->lines
            ->filter(fn (OrderLine $line): bool => float_compare($line->refundableQty(), 0, precisionDigits: 4) > 0)
            ->mapWithKeys(fn (OrderLine $line): array => [$line->id => $line->refundableQty()])
            ->all();
    }

    protected function copyLots(OrderLine $refundLine, OrderLine $line, float $quantity): void
    {
        $remaining = $quantity;

        foreach ($line->lots as $lot) {
            if (float_compare($remaining, 0, precisionDigits: 4) <= 0) {
                break;
            }

            $taken = min((float) $lot->qty, $remaining);

            OrderLineLot::create([
                'order_line_id' => $refundLine->id,
                'lot_name'      => $lot->lot_name,
                'lot_id'        => $lot->lot_id,
                'qty'           => $taken,
                'company_id'    => $refundLine->company_id,
            ]);

            $remaining = float_round($remaining - $taken, precisionDigits: 4);
        }
    }

    protected function resolveSession(Order $order, ?Session $session): Session
    {
        if ($session) {
            return $session;
        }

        $live = $this->sessions->liveSessionFor($order->config);

        return $live ?? $this->sessions->openRescueFor($order->session);
    }

    protected function copyLine(Order $refund, OrderLine $line, float $quantity): OrderLine
    {
        $refundLine = OrderLine::create([
            'order_id'               => $refund->id,
            'product_id'             => $line->product_id,
            'uom_id'                 => $line->uom_id,
            'price_list_id'          => $line->price_list_id,
            'name'                   => $line->name,
            'full_product_name'      => $line->full_product_name,
            'qty'                    => -$quantity,
            'price_unit'             => $line->price_unit,
            'price_extra'            => $line->price_extra,
            'price_type'             => $line->price_type,
            'discount'               => $line->discount,
            'unit_cost'              => $line->unit_cost,
            'is_cost_computed'       => (bool) $line->is_cost_computed,
            'customer_note'          => $line->customer_note,
            'refunded_order_line_id' => $line->id,
            'route_id'               => $line->route_id,
            'warehouse_id'           => $line->warehouse_id,
        ]);

        $refundLine->taxes()->sync($line->taxes->pluck('id')->all());

        $refundLine->attributeValues()->sync($line->attributeValues->pluck('id')->all());

        $this->copyLots($refundLine, $line, $quantity);

        return $refundLine;
    }
}
