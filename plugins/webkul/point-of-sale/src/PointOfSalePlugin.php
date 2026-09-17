<?php

namespace Webkul\PointOfSale;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\PluginManager\Package;

class PointOfSalePlugin implements Plugin
{
    public function getId(): string
    {
        return 'point-of-sale';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        $panel
            ->when($panel->getId() == 'admin', function (Panel $panel) {
                $panel
                    ->discoverResources(
                        in: __DIR__.'/Filament/Admin/Resources',
                        for: 'Webkul\\PointOfSale\\Filament\\Admin\\Resources'
                    )
                    ->discoverPages(
                        in: __DIR__.'/Filament/Admin/Pages',
                        for: 'Webkul\\PointOfSale\\Filament\\Admin\\Pages'
                    )
                    ->discoverClusters(
                        in: __DIR__.'/Filament/Admin/Clusters',
                        for: 'Webkul\\PointOfSale\\Filament\\Admin\\Clusters'
                    )
                    ->discoverWidgets(
                        in: __DIR__.'/Filament/Admin/Widgets',
                        for: 'Webkul\\PointOfSale\\Filament\\Admin\\Widgets'
                    );
            })
            ->when($panel->getId() == 'pos', function (Panel $panel) {
                $panel
                    ->discoverPages(
                        in: __DIR__.'/Filament/Pos/Pages',
                        for: 'Webkul\\PointOfSale\\Filament\\Pos\\Pages'
                    )
                    ->discoverWidgets(
                        in: __DIR__.'/Filament/Pos/Widgets',
                        for: 'Webkul\\PointOfSale\\Filament\\Pos\\Widgets'
                    );
            });
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
