<?php

namespace Webkul\Manufacturing\Listeners;

use Webkul\Inventory\Models\Warehouse;

class WarehouseLifecycleListener
{
    public function created(Warehouse $warehouse): void {}

    public function updated(Warehouse $warehouse): void {}

    public function deleted(Warehouse $warehouse): void {}

    public function restored(Warehouse $warehouse): void {}
}
