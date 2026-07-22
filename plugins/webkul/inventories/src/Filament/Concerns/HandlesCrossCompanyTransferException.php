<?php

namespace Webkul\Inventory\Filament\Concerns;

use Filament\Notifications\Notification;
use Webkul\Inventory\Exceptions\CrossCompanyTransferException;

trait HandlesCrossCompanyTransferException
{
    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (CrossCompanyTransferException $exception) {
            $this->notifyCrossCompanyTransfer($exception);
        }
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (CrossCompanyTransferException $exception) {
            $this->notifyCrossCompanyTransfer($exception);
        }
    }

    protected function notifyCrossCompanyTransfer(CrossCompanyTransferException $exception): void
    {
        Notification::make()
            ->danger()
            ->title($exception->title())
            ->body($exception->getMessage())
            ->send();
    }
}
