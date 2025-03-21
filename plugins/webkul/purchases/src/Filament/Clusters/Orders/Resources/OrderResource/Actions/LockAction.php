<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Purchase\Enums\OrderState;
use Webkul\Purchase\Models\Order;

class LockAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'purchases.orders.lock';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('purchases::filament/clusters/orders/resources/order/actions/lock.label'))
            ->color('gray')
            ->action(function (Order $record, Component $livewire): void {
                $record->update([
                    'state' => OrderState::DONE,
                ]);

                foreach ($record->lines as $move) {
                    $move->update([
                        'state' => OrderState::DONE,
                    ]);
                }

                $livewire->updateForm();

                Notification::make()
                    ->title(__('purchases::filament/clusters/orders/resources/order/actions/lock.action.notification.success.title'))
                    ->body(__('purchases::filament/clusters/orders/resources/order/actions/lock.action.notification.success.body'))
                    ->success()
                    ->send();
            })
            ->visible(fn () => $this->getRecord()->state === OrderState::PURCHASE);
    }
}
