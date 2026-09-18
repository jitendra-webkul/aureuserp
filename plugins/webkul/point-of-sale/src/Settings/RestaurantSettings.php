<?php

namespace Webkul\PointOfSale\Settings;

use Spatie\LaravelSettings\Settings;

class RestaurantSettings extends Settings
{
    public bool $enable_restaurant;

    public static function group(): string
    {
        return 'point_of_sale_restaurant';
    }
}
