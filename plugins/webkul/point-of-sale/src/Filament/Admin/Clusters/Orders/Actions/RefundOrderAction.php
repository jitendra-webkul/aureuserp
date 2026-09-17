<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Orders\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Throwable;
use Webkul\PointOfSale\Enums\PaymentMethodType;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\OrderLine;
use Webkul\Support\Filament\Forms\Components\Repeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn;

class RefundOrderAction extends Action
{
    protected bool|Closure $hasDatabaseTransactions = true;

    public static function getDefaultName(): ?string
    {
        return 'point-of-sale.order.refund';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.label'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalWidth('2xl')
            ->fillForm(fn (Order $record): array => [
                'payment_method_id' => $record->config?->paymentMethods
                    ->firstWhere('type', PaymentMethodType::CASH)?->id
                    ?? $record->config?->paymentMethods->first()?->id,
                'lines' => $record->lines
                    ->filter(fn (OrderLine $line): bool => float_compare($line->refundableQty(), 0, precisionDigits: 4) > 0)
                    ->map(fn (OrderLine $line): array => [
                        'line_id'  => $line->id,
                        'product'  => $line->full_product_name ?? $line->product?->name,
                        'quantity' => $line->refundableQty(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->schema([
                Select::make('payment_method_id')
                    ->label(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.form.fields.payment-method'))
                    ->options(fn (Order $record): array => $record->config?->paymentMethods
                        ->pluck('name', 'id')
                        ->all() ?? [])
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('lines')
                    ->label(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.form.fields.lines'))
                    ->table([
                        TableColumn::make(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.form.fields.product')),
                        TableColumn::make(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.form.fields.quantity')),
                    ])
                    ->schema([
                        TextInput::make('product')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('quantity')
                            ->numeric()
                            ->minValue(0),

                        Hidden::make('line_id'),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->columnSpanFull(),
            ])
            ->action(function (Order $record, array $data): void {
                try {
                    $quantities = collect($data['lines'] ?? [])
                        ->mapWithKeys(fn (array $line): array => [
                            (int) $line['line_id'] => (float) ($line['quantity'] ?? 0),
                        ])
                        ->all();

                    $refund = PointOfSale::refundOrder($record, $quantities);

                    PointOfSale::settleRefund($refund, (int) $data['payment_method_id']);

                    Notification::make()
                        ->success()
                        ->title(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.notification.success.title'))
                        ->body(__('point-of-sale::filament/admin/clusters/orders/actions/refund-order.notification.success.body'))
                        ->send();
                } catch (Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->body($exception->getMessage())
                        ->send();

                    $this->halt(shouldRollBackDatabaseTransaction: true);
                }
            })
            ->visible(fn (Order $record): bool => $record->state->isSettled() && ! $record->isRefund());
    }
}
