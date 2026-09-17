<?php

namespace Webkul\PointOfSale\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TableShape: string implements HasIcon, HasLabel
{
    case SQUARE = 'square';

    case ROUND = 'round';

    public static function options(): array
    {
        return [
            self::SQUARE->value => __('point-of-sale::enums/table-shape.square'),
            self::ROUND->value  => __('point-of-sale::enums/table-shape.round'),
        ];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::SQUARE => __('point-of-sale::enums/table-shape.square'),
            self::ROUND  => __('point-of-sale::enums/table-shape.round'),
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::SQUARE => 'heroicon-o-stop',
            self::ROUND  => 'heroicon-o-circle-stack',
        };
    }
}
