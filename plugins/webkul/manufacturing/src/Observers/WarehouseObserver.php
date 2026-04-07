<?php

namespace Webkul\Manufacturing\Observers;

use Webkul\Inventory\Models\Warehouse;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WarehouseObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Warehouse $warehouse): void {}

    public function updated(Warehouse $warehouse): void
    {
        dd($warehouse);
    }

    public function deleted(Warehouse $warehouse): void {}

    public function restored(Warehouse $warehouse): void {}
}
