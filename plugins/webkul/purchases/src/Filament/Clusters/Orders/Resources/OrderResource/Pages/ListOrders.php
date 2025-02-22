<?php

namespace Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource\Pages;

use Webkul\Purchase\Filament\Clusters\Orders\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getPresetTableViews(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('purchases::filament/clusters/orders/resources/order/pages/list-orders.header-actions.create.label'))
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
