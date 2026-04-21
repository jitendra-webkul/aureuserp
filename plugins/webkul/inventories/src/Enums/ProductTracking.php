<?php

namespace Webkul\Inventory\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductTracking: string implements HasLabel
{
    case SERIAL = 'serial';

    case LOT = 'lot';

    case QTY = 'qty';

    public function getLabel(): string
    {
        return match ($this) {
            self::SERIAL => __('inventories::enums/product-tracking.serial'),
            self::LOT    => __('inventories::enums/product-tracking.lot'),
            self::QTY    => __('inventories::enums/product-tracking.qty'),
        };
    }
}
