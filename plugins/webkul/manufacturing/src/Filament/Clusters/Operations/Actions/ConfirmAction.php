<?php

namespace Webkul\Manufacturing\Filament\Clusters\Operations\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Throwable;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Facades\Manufacturing as ManufacturingFacade;
use Webkul\Manufacturing\Models\Order;

class ConfirmAction extends Action
{
    protected bool|Closure $hasDatabaseTransactions = true;

    public static function getDefaultName(): ?string
    {
        return 'manufacturing.order.confirm';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Confirm')
            ->action(function (Order $record, Component $livewire): void {
                // try {
                    $record = ManufacturingFacade::confirmManufacturingOrder($record);

                    // $livewire->updateForm();

                    Notification::make()
                        ->success()
                        ->title(__('inventories::filament/clusters/operations/actions/todo.notification.success.title'))
                        ->body(__('inventories::filament/clusters/operations/actions/todo.notification.success.body'))
                        ->send();
                // } catch (Throwable $e) {
                //     Notification::make()
                //         ->danger()
                //         ->body($e->getMessage())
                //         ->send();

                //     $this->halt(shouldRollBackDatabaseTransaction: true);
                // }
            })
            ->hidden(fn () => $this->getRecord()->state !== ManufacturingOrderState::DRAFT);
    }
}
