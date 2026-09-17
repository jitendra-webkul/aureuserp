<?php

namespace Webkul\PointOfSale\Settings;

use Spatie\LaravelSettings\Settings;

class TerminalSettings extends Settings
{
    public bool $enable_customer_selection;

    public bool $enable_line_discount;

    public bool $enable_global_discount;

    public bool $enable_restaurant;

    public bool $enable_ship_later;

    public static function group(): string
    {
        return 'point_of_sale_terminal';
    }
}
