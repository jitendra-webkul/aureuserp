<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Webkul\Inventory\Enums\CreateBackorder;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\ProductQuantity;

class ValidateAction extends Action
{
    protected bool | Closure $hasDatabaseTransactions = true;

    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.validate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('inventories::filament/clusters/operations/actions/validate.label'))
            ->color(function (Operation $record) {
                if (in_array($record->state, [OperationState::DRAFT, OperationState::CONFIRMED])) {
                    return 'gray';
                }

                return 'primary';
            })
            ->requiresConfirmation(function (Operation $record) {
                return $record->operationType->create_backorder === CreateBackorder::ASK
                    && $this->canCreateBackOrder($record);
            })
            ->modalHeading(fn (Operation $record) => (
                $record->operationType->create_backorder === CreateBackorder::ASK
                && $this->canCreateBackOrder($record)
            ) ? __('inventories::filament/clusters/operations/actions/validate.modal-heading') : null)
            ->modalDescription(fn (Operation $record) => (
                $record->operationType->create_backorder === CreateBackorder::ASK
                && $this->canCreateBackOrder($record)
            ) ? __('inventories::filament/clusters/operations/actions/validate.modal-description') : null)
            ->extraModalFooterActions(fn (Operation $record) => (
                $record->operationType->create_backorder === CreateBackorder::ASK
                && $this->canCreateBackOrder($record)
            ) ? [
                Action::make('no-backorder')
                    ->label(__('inventories::filament/clusters/operations/actions/validate.extra-modal-footer-actions.no-backorder.label'))
                    ->color('danger')
                    ->action(function (Operation $record, Component $livewire): void {
                        Inventory::doneTransfer($record, true);

                        $livewire->updateForm();
                    }),
            ] : [])
            ->action(function (Operation $record, Component $livewire): void {
                Inventory::doneTransfer($record);

                $livewire->updateForm();
            })
            ->hidden(function (Operation $record) {
                return in_array($record->state, [
                    OperationState::DONE,
                    OperationState::CANCELED,
                ]);
            });
    }

    public function canCreateBackOrder(Operation $record): bool
    {
        if ($record->operationType->create_backorder === CreateBackorder::NEVER) {
            return false;
        }

        return $record->moves->sum('product_uom_qty') > $record->moves->sum('quantity');
    }

    /**
     * Send a notification with the given title, body and type.
     */
    private function sendNotification(string $titleKey, string $bodyKey, string $type = 'info'): void
    {
        Notification::make()
            ->title(__($titleKey))
            ->body(__($bodyKey))
            ->{$type}()
            ->send();
    }
}
