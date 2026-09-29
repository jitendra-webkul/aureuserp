<?php

namespace Webkul\PointOfSale\Services;

use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Floor;
use Webkul\PointOfSale\Models\Table;

class RestaurantFloorProvisioner
{
    public function syncFor(Config $config): void
    {
        if (! $config->is_restaurant) {
            $config->floors()->detach();

            return;
        }

        if ($config->floors()->exists()) {
            return;
        }

        $floor = Floor::create([
            'name'       => $config->company?->name ?? $config->name,
            'company_id' => $config->company_id,
        ]);

        Table::create([
            'table_number' => '1',
            'seats'        => 1,
            'floor_id'     => $floor->id,
            'company_id'   => $config->company_id,
        ]);

        $config->floors()->attach($floor->id);
    }
}
