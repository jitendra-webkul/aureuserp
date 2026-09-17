<?php

namespace Webkul\PointOfSale\Enums;

use Filament\Support\Contracts\HasLabel;

enum TaxDisplay: string implements HasLabel
{
    case SUBTOTAL = 'subtotal';

    case TOTAL = 'total';

    public static function options(): array
    {
        return [
            self::SUBTOTAL->value => __('point-of-sale::enums/tax-display.subtotal'),
            self::TOTAL->value    => __('point-of-sale::enums/tax-display.total'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SUBTOTAL => __('point-of-sale::enums/tax-display.subtotal'),
            self::TOTAL    => __('point-of-sale::enums/tax-display.total'),
        };
    }
}
