<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('point_of_sale_terminal.enable_customer_selection', true);
        $this->migrator->add('point_of_sale_terminal.enable_line_discount', true);
        $this->migrator->add('point_of_sale_terminal.enable_global_discount', false);
        $this->migrator->add('point_of_sale_terminal.enable_restaurant', false);
        $this->migrator->add('point_of_sale_terminal.enable_ship_later', false);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('point_of_sale_terminal.enable_customer_selection');
        $this->migrator->deleteIfExists('point_of_sale_terminal.enable_line_discount');
        $this->migrator->deleteIfExists('point_of_sale_terminal.enable_global_discount');
        $this->migrator->deleteIfExists('point_of_sale_terminal.enable_restaurant');
        $this->migrator->deleteIfExists('point_of_sale_terminal.enable_ship_later');
    }
};
