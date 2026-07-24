<?php

namespace Webkul\Support\Filament\Resources\UOMCategoryResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Webkul\Support\Exceptions\UOMInUseException;
use Webkul\Support\Filament\Resources\UOMCategoryResource;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class EditUOMCategory extends EditRecord
{
    use HasRecordNavigationTabs;

    protected static string $resource = UOMCategoryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return parent::handleRecordUpdate($record, $data);
        } catch (UOMInUseException $exception) {
            Notification::make()
                ->danger()
                ->title(__('support::filament/resources/uom-category/pages/edit-uom-category.notification.uom-in-use'))
                ->body($exception->getMessage())
                ->send();

            throw new Halt;
        }
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title(__('support::filament/resources/uom-category/pages/edit-uom-category.notification.title'))
            ->body(__('support::filament/resources/uom-category/pages/edit-uom-category.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('support::filament/resources/uom-category/pages/edit-uom-category.header-actions.delete.notification.title'))
                        ->body(__('support::filament/resources/uom-category/pages/edit-uom-category.header-actions.delete.notification.body')),
                ),
        ];
    }
}
