<?php

namespace Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource\Pages;

use Exception;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\EditRecord;
use Webkul\Account\Filament\Clusters\Configuration\Resources\PaymentTermResource;
use Webkul\Account\Models\PaymentDueTerm;

class EditPaymentTerm extends EditRecord
{
    protected static string $resource = PaymentTermResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title(__('accounts::filament/clusters/configurations/resources/payment-term/pages/edit-payment-term.header-actions.delete.notification.title'))
                        ->body(__('accounts::filament/clusters/configurations/resources/payment-term/pages/edit-payment-term.header-actions.delete.notification.body'))
                ),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('accounts::filament/clusters/configurations/resources/payment-term/pages/edit-payment-term.notification.title'))
            ->body(__('accounts::filament/clusters/configurations/resources/payment-term/pages/edit-payment-term.notification.body'));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $paymentTermId = $this->record->id;

        $paymentDueTerm = PaymentDueTerm::where('payment_id', $paymentTermId)->get();

        $totalPercent = $paymentDueTerm->where('value', 'percent')->sum('value_amount');

        if (round($totalPercent, 2) !== 100.0) {
            throw new Exception(__('The Payment Term must have at least one percent and the sum of the percent must be 100%.'));
        }

        if (count($paymentDueTerm) > 1 && $data['early_discount'] ?? false) {
            throw new Exception(__('The Early Payment Discount functionality can only be used with payment terms using a single 100%.'));
        }

        if (($data['early_discount'] ?? false) && ($data['discount_percentage'] ?? 0.0) <= 0.0) {
            throw new Exception(__('The Early Payment Discount must be strictly positive.'));
        }

        if (($data['early_discount'] ?? false) && ($data['discount_days'] ?? 0) <= 0) {
            throw new Exception(__('The Early Payment Discount days must be strictly positive.'));
        }

        return $data;
    }
}
