<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('point_of_sale_restaurant.enable_restaurant', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('point_of_sale_restaurant.enable_restaurant');
    }
};
