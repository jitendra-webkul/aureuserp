<?php

namespace Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource;
use Webkul\Account\Filament\Clusters\Customer\Resources\PaymentsResource\Actions as BaseActions;
use Webkul\Chatter\Filament\Actions as ChatterActions;

class EditPayments extends EditRecord
{
    protected static string $resource = PaymentsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/clusters/customers/resources/payment/pages/edit-payment.notification.title'))
            ->body(__('accounts::filament/clusters/customers/resources/payment/pages/edit-payment.notification.body'));
    }

    protected function getHeaderActions(): array
    {
        return [
            ChatterActions\ChatterAction::make()
                ->setResource(static::$resource),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            BaseActions\ConfirmAction::make(),
            BaseActions\ResetToDraftAction::make(),
            BaseActions\MarkAsSendAdnUnsentAction::make(),
            BaseActions\CancelAction::make(),
            BaseActions\RejectAction::make(),
        ];
    }
}
