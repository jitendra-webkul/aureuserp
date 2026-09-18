<?php

namespace Webkul\PointOfSale\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\Product as AccountProduct;
use Webkul\Account\Models\Tax;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\OrderLine;
use Webkul\PointOfSale\Models\OrderLineLot;
use Webkul\PointOfSale\Models\Payment;
use Webkul\PointOfSale\Models\Session;
use Webkul\Product\Models\Product;

class OrderProcessor
{
    public function __construct(
        protected OrderCalculator $calculator,
        protected OrderWorkflow $orders,
        protected PosInvoicer $invoicer,
        protected PriceResolver $prices,
        protected SessionWorkflow $sessions,
    ) {}

    public function process(array $payload): Order
    {
        $uuid = $payload['uuid'] ?? Str::uuid()->toString();

        $existing = Order::withoutGlobalScopes()->where('uuid', $uuid)->first();

        if ($existing && $existing->state !== OrderState::DRAFT) {
            return $existing;
        }

        return DB::transaction(function () use ($payload, $uuid, $existing): Order {
            $order = $existing ?? $this->createOrder($payload, $uuid);

            $this->syncLines($order, $payload['lines'] ?? []);

            $this->syncPayments($order, $payload['payments'] ?? []);

            $order = $this->orders->markPaid($order->refresh());

            return $this->invoiceIfRequested($order);
        });
    }

    protected function invoiceIfRequested(Order $order): Order
    {
        if (! $order->is_to_invoice || $order->is_invoiced) {
            return $order;
        }

        if ($order->state !== OrderState::PAID) {
            return $order;
        }

        $this->invoicer->invoice($order);

        return $order->refresh();
    }

    public function saveDraft(array $payload): Order
    {
        $uuid = $payload['uuid'] ?? Str::uuid()->toString();

        $existing = Order::withoutGlobalScopes()->where('uuid', $uuid)->first();

        if ($existing && $existing->state !== OrderState::DRAFT) {
            return $existing;
        }

        return DB::transaction(function () use ($payload, $uuid, $existing): Order {
            $order = $existing ?? $this->createOrder($payload, $uuid);

            $order->forceFill(Arr::only($payload, ['partner_id', 'note']))->save();

            $this->syncLines($order, $payload['lines'] ?? []);

            $this->pruneLines($order, $payload['lines'] ?? []);

            return $this->calculator->recompute($order->refresh());
        });
    }

    public function discardDraft(Order $order): void
    {
        if ($order->state !== OrderState::DRAFT) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->lines()->each(fn (OrderLine $line) => $line->delete());

            $order->delete();
        });
    }

    protected function pruneLines(Order $order, array $lines): void
    {
        $keep = collect($lines)->pluck('uuid')->filter()->all();

        $order->lines()
            ->when($keep, fn ($query) => $query->whereNotIn('uuid', $keep))
            ->get()
            ->each(fn (OrderLine $line) => $line->delete());
    }

    public function processBatch(array $orders): array
    {
        $data = [];

        $errors = [];

        foreach ($orders as $payload) {
            try {
                $data[] = $this->process($payload);
            } catch (Throwable $exception) {
                $errors[] = [
                    'uuid'    => $payload['uuid'] ?? null,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'data'   => $data,
            'errors' => $errors,
        ];
    }

    public function resolveSession(Config $config, ?int $sessionId = null): Session
    {
        $session = $sessionId
            ? Session::withoutGlobalScopes()->whereKey($sessionId)->first()
            : null;

        if ($session && $session->config_id === $config->id && $session->isLive()) {
            return $session;
        }

        $live = $this->sessions->liveSessionFor($config);

        if ($live) {
            return $live;
        }

        return $session && $session->config_id === $config->id
            ? $this->sessions->openRescueFor($session)
            : $this->sessions->open($config);
    }

    protected function createOrder(array $payload, string $uuid): Order
    {
        $config = Config::withoutGlobalScopes()->findOrFail($payload['config_id']);

        $session = $this->resolveSession($config, $payload['session_id'] ?? null);

        $attributes = array_merge(
            Arr::only($payload, [
                'reference',
                'note',
                'email',
                'mobile',
                'partner_id',
                'price_list_id',
                'fiscal_position_id',
                'shipped_at',
                'is_to_invoice',
                'customer_count',
                'is_takeaway',
                'table_id',
                'refunded_order_id',
            ]),
            [
                'uuid'       => $uuid,
                'config_id'  => $config->id,
                'session_id' => $session->id,
                'ordered_at' => $payload['ordered_at'] ?? now(),
            ],
        );

        try {
            return Order::create($attributes);
        } catch (UniqueConstraintViolationException) {
            return Order::withoutGlobalScopes()->where('uuid', $uuid)->firstOrFail();
        }
    }

    protected function syncLines(Order $order, array $lines): void
    {
        foreach ($lines as $line) {
            $model = OrderLine::updateOrCreate(
                ['uuid' => $line['uuid'] ?? Str::uuid()->toString()],
                array_merge(
                    ['price_unit' => $this->resolvePrice($order, $line)],
                    Arr::only($line, [
                        'product_id',
                        'uom_id',
                        'qty',
                        'price_unit',
                        'price_extra',
                        'price_type',
                        'discount',
                        'customer_note',
                        'note',
                        'refunded_order_line_id',
                        'route_id',
                        'warehouse_id',
                    ]),
                    ['order_id' => $order->id],
                ),
            );

            if (array_key_exists('tax_ids', $line)) {
                $model->taxes()->sync($line['tax_ids']);
            } else {
                $product = Product::withoutGlobalScopes()->find($model->product_id);

                $model->taxes()->sync($this->defaultTaxIds($product, $order->company_id));
            }

            if (array_key_exists('attribute_value_ids', $line)) {
                $model->attributeValues()->sync($line['attribute_value_ids']);
            }

            if (array_key_exists('lots', $line)) {
                $this->syncLots($model, $line['lots']);
            }
        }
    }

    protected function syncLots(OrderLine $line, array $lots): void
    {
        $line->lots()->delete();

        foreach ($lots as $lot) {
            if (blank($lot['lot_name'] ?? null)) {
                continue;
            }

            OrderLineLot::create([
                'order_line_id' => $line->id,
                'lot_name'      => $lot['lot_name'],
                'lot_id'        => $lot['lot_id'] ?? null,
                'qty'           => (float) ($lot['qty'] ?? 1),
            ]);
        }
    }

    protected function resolvePrice(Order $order, array $line): float
    {
        if (array_key_exists('price_unit', $line)) {
            return (float) $line['price_unit'];
        }

        $product = Product::withoutGlobalScopes()->find($line['product_id'] ?? null);

        if (! $product) {
            return 0.0;
        }

        return $this->prices->resolveForOrder($order, $product, (float) ($line['qty'] ?? 1));
    }

    protected function defaultTaxIds(?Product $product, ?int $companyId): array
    {
        if (! $product) {
            return [];
        }

        $accountProduct = AccountProduct::withoutGlobalScopes()->find($product->id);

        if (! $accountProduct) {
            return [];
        }

        return Tax::forProduct($accountProduct, TypeTaxUse::SALE, $companyId);
    }

    protected function syncPayments(Order $order, array $payments): void
    {
        foreach ($payments as $payment) {
            Payment::updateOrCreate(
                ['uuid' => $payment['uuid'] ?? Str::uuid()->toString()],
                array_merge(
                    Arr::only($payment, [
                        'payment_method_id',
                        'amount',
                        'terminal_status',
                        'transaction_reference',
                        'card_type',
                        'card_brand',
                        'cardholder_name',
                        'ticket',
                        'paid_at',
                    ]),
                    [
                        'order_id'   => $order->id,
                        'session_id' => $order->session_id,
                    ],
                ),
            );
        }
    }
}
