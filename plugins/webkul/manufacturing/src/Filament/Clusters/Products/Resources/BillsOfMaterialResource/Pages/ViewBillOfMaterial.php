<?php

namespace Webkul\Manufacturing\Filament\Clusters\Products\Resources\BillsOfMaterialResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Webkul\Manufacturing\Filament\Clusters\Products\Resources\BillsOfMaterialResource;
use Webkul\Support\Traits\HasRecordNavigationTabs;

class ViewBillOfMaterial extends ViewRecord
{
    use HasRecordNavigationTabs;

    protected static string $resource = BillsOfMaterialResource::class;
}
