<?php

namespace Webkul\PointOfSale\Enums;

use Filament\Support\Contracts\HasLabel;

enum PickingPolicy: string implements HasLabel
{
    case DIRECT = 'direct';

    case ONE = 'one';

    public static function options(): array
    {
        return [
            self::DIRECT->value => __('point-of-sale::enums/picking-policy.direct'),
            self::ONE->value    => __('point-of-sale::enums/picking-policy.one'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::DIRECT => __('point-of-sale::enums/picking-policy.direct'),
            self::ONE    => __('point-of-sale::enums/picking-policy.one'),
        };
    }
}
