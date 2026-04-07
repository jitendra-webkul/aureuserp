<?php

namespace Webkul\Manufacturing\Observers;

use Webkul\Inventory\Models\Warehouse as InventoryWarehouse;
use Webkul\Manufacturing\Models\Warehouse as ManufacturingWarehouse;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WarehouseObserver implements ShouldHandleEventsAfterCommit
{
    public function created(InventoryWarehouse $warehouse): void
    {
        $warehouse = ManufacturingWarehouse::find($warehouse->id);

        $warehouse->handleManufacturingWarehouseCreation();

        $warehouse->finalizeManufacturingWarehouseCreation();
    }

    public function updated(InventoryWarehouse $warehouse): void
    {
        $warehouse = ManufacturingWarehouse::find($warehouse->id);
        
        $warehouse->syncManufacturingWarehouseConfiguration();
    }

    public function deleted(InventoryWarehouse $warehouse): void {}

    public function restored(InventoryWarehouse $warehouse): void {}
}
