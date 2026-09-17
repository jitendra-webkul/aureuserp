<?php

namespace Webkul\PointOfSale\Enums;

use Filament\Support\Contracts\HasLabel;

enum InvoicePaymentMode: string implements HasLabel
{
    case AT_ORDER = 'at_order';

    case AT_CLOSE = 'at_close';

    public static function options(): array
    {
        return [
            self::AT_ORDER->value => __('point-of-sale::enums/invoice-payment-mode.at-order'),
            self::AT_CLOSE->value => __('point-of-sale::enums/invoice-payment-mode.at-close'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::AT_ORDER => __('point-of-sale::enums/invoice-payment-mode.at-order'),
            self::AT_CLOSE => __('point-of-sale::enums/invoice-payment-mode.at-close'),
        };
    }
}
