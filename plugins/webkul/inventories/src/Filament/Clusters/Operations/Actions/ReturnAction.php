<?php

namespace Webkul\Inventory\Filament\Clusters\Operations\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;
use Throwable;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Facades\Inventory;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Operation;

class ReturnAction extends Action
{
    protected bool|Closure $hasDatabaseTransactions = true;

    public static function getDefaultName(): ?string
    {
        return 'inventories.operations.return';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('inventories::filament/clusters/operations/actions/return.label'))
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (Operation $record, Component $livewire) {
                try {
                    $newRecord = Inventory::returnTransfer($record);

                    $livewire->updateForm();

                    return redirect()->to(OperationResource::getUrl('edit', ['record' => $newRecord]));
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->body($e->getMessage())
                        ->send();

                    $this->halt();
                }
            })
            ->visible(fn () => $this->getRecord()->state == OperationState::DONE);
    }
}
