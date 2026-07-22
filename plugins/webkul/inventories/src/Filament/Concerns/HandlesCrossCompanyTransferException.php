<?php

namespace Webkul\Inventory\Filament\Concerns;

use Filament\Notifications\Notification;
use Webkul\Inventory\Exceptions\CrossCompanyTransferException;
use Webkul\Inventory\Support\CrossCompanyTransferGuard;

trait HandlesCrossCompanyTransferException
{
    public function create(bool $another = false): void
    {
        try {
            $this->assertNoCrossCompanyTransfer();

            parent::create($another);
        } catch (CrossCompanyTransferException $exception) {
            $this->notifyCrossCompanyTransfer($exception);
        }
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            $this->assertNoCrossCompanyTransfer();

            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (CrossCompanyTransferException $exception) {
            $this->notifyCrossCompanyTransfer($exception);
        }
    }

    protected function assertNoCrossCompanyTransfer(): void
    {
        CrossCompanyTransferGuard::assert(
            $this->data['source_location_id'] ?? null,
            $this->data['destination_location_id'] ?? null,
        );
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
