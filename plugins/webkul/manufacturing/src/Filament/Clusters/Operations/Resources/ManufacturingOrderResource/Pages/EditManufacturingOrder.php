<?php

namespace Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource;
use Webkul\Support\Filament\Concerns\HasRepeaterColumnManager;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class EditManufacturingOrder extends EditRecord
{
    use HasRecordNavigationTabs, HasRepeaterColumnManager;

    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = ManufacturingOrderResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->reference ?: __('manufacturing::filament/clusters/operations/resources/manufacturing-order/pages/edit-manufacturing-order.title');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['destination_location_id'] = $data['final_location_id'] ?? $data['destination_location_id'] ?? null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirm')
                ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.pages.shared.header-actions.confirm.label'))
                ->color('primary')
                ->visible(fn (): bool => $this->getRecord()->state === ManufacturingOrderState::DRAFT)
                ->action(function (): void {
                    $this->getRecord()->update(['state' => ManufacturingOrderState::CONFIRMED]);

                    Notification::make()
                        ->success()
                        ->title(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.pages.shared.header-actions.confirm.notification.title'))
                        ->send();
                }),
            Action::make('cancel')
                ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.pages.shared.header-actions.cancel.label'))
                ->color('gray')
                ->visible(fn (): bool => $this->getRecord()->state !== ManufacturingOrderState::DONE)
                ->action(function (): void {
                    $this->getRecord()->update(['state' => ManufacturingOrderState::CANCEL]);

                    Notification::make()
                        ->success()
                        ->title(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.pages.shared.header-actions.cancel.notification.title'))
                        ->send();
                }),
        ];
    }
}
