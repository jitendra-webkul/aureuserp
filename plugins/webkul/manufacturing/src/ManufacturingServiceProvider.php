<?php

namespace Webkul\Manufacturing;

use Filament\Panel;
use Webkul\PluginManager\Console\Commands\InstallCommand;
use Webkul\PluginManager\Console\Commands\UninstallCommand;
use Webkul\PluginManager\Package;
use Webkul\PluginManager\PackageServiceProvider;

class ManufacturingServiceProvider extends PackageServiceProvider
{
    public static string $name = 'manufacturing';

    public static string $viewNamespace = 'manufacturing';

    public function configureCustomPackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                '2026_03_31_064242_create_manufacturing_bills_of_materials_table',
                '2026_03_31_064243_create_manufacturing_work_centers_table',
                '2026_03_31_064244_create_manufacturing_operations_table',
                '2026_03_31_064245_create_manufacturing_bill_of_material_lines_table',
                '2026_03_31_064246_create_manufacturing_bill_of_material_byproducts_table',
                '2026_03_31_064247_create_manufacturing_orders_table',
                '2026_03_31_064248_create_manufacturing_work_orders_table',
                '2026_03_31_064249_create_manufacturing_unbuild_orders_table',
                '2026_03_31_064250_create_manufacturing_batch_productions_table',
                '2026_03_31_064251_create_manufacturing_consumption_warnings_table',
                '2026_03_31_064252_create_manufacturing_consumption_warning_lines_table',
                '2026_03_31_064253_create_manufacturing_order_backorders_table',
                '2026_03_31_064254_create_manufacturing_order_backorder_lines_table',
                '2026_03_31_064255_create_manufacturing_order_split_batches_table',
                '2026_03_31_064256_create_manufacturing_order_splits_table',
                '2026_03_31_064257_create_manufacturing_order_split_lines_table',
                '2026_03_31_064258_create_manufacturing_work_center_capacities_table',
                '2026_03_31_064259_create_manufacturing_work_center_loss_types_table',
                '2026_03_31_064260_create_manufacturing_work_center_productivity_losses_table',
                '2026_03_31_064261_create_manufacturing_work_center_productivity_logs_table',
                '2026_03_31_064262_create_manufacturing_work_center_tags_table',
                '2026_03_31_064263_create_manufacturing_bill_of_material_byproduct_attribute_values_table',
                '2026_03_31_064264_create_manufacturing_bill_of_material_line_attribute_values_table',
                '2026_03_31_064265_create_manufacturing_operation_dependencies_table',
                '2026_03_31_064266_create_manufacturing_operation_attribute_values_table',
                '2026_03_31_064267_create_manufacturing_consumption_warning_order_table',
                '2026_03_31_064268_create_manufacturing_order_backorder_order_table',
                '2026_03_31_064269_create_manufacturing_order_label_types_table',
                '2026_03_31_064270_create_manufacturing_work_center_alternatives_table',
                '2026_03_31_064271_create_manufacturing_work_center_tag_table',
                '2026_03_31_064272_create_manufacturing_work_order_dependencies_table',
                '2026_03_31_180000_add_worksheet_to_manufacturing_operations_table',
            ])
            ->runsMigrations()
            ->hasDependencies([
                'products',
                'inventories',
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->installDependencies()
                    ->runsMigrations();
            })
            ->hasUninstallCommand(function (UninstallCommand $command): void {})
            ->icon('manufacturing');
    }

    public function packageBooted(): void
    {
        //
    }

    public function packageRegistered(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            $panel->plugin(ManufacturingPlugin::make());
        });
    }
}
