<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\FloorResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\FloorResource;

class ListFloors extends ListRecords
{
    protected static string $resource = FloorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor/pages/list-floors.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
