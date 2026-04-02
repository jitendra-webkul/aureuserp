<?php

namespace Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class ViewManufacturingOrder extends ViewRecord
{
    use HasRecordNavigationTabs;

    protected static string $resource = ManufacturingOrderResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->reference ?: __('manufacturing::filament/clusters/operations/resources/manufacturing-order/pages/view-manufacturing-order.title');
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
