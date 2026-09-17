<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Orders\Resources\OrderResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Admin\Clusters\Orders\Resources\OrderResource;
use Webkul\PointOfSale\Models\Order;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class EditOrder extends EditRecord
{
    use HasRecordNavigationTabs;

    protected static string $resource = OrderResource::class;

    /**
     * Payment lines added here change what the order is worth, so the totals are
     * rebuilt the same way the till rebuilds them.
     */
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Order) {
            PointOfSale::recomputeOrder($record);
        }
    }
}
