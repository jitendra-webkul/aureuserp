<?php

namespace Webkul\Manufacturing\Filament\Clusters\Configurations\Resources\WorkCenterResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\Manufacturing\Filament\Clusters\Configurations\Resources\WorkCenterResource;

class CreateWorkCenter extends CreateRecord
{
    protected static string $resource = WorkCenterResource::class;

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('manufacturing::filament/clusters/configurations/resources/work-center/pages/create-work-center.notification.title'))
            ->body(__('manufacturing::filament/clusters/configurations/resources/work-center/pages/create-work-center.notification.body'));
    }
}
