<?php

namespace Webkul\PointOfSale\Filament\Admin\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkul\PointOfSale\Filament\Admin\Widgets\SessionStatsWidget;
use Webkul\PointOfSale\Filament\Admin\Widgets\TopProductsWidget;
use Webkul\PointOfSale\Models\Config;
use Webkul\Support\Enums\NavigationGroup;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    use HasPageShield;

    protected static string $routePath = 'point-of-sale';

    protected static function getPagePermission(): ?string
    {
        return 'page_point_of_sale_dashboard';
    }

    public static function getNavigationLabel(): string
    {
        return __('point-of-sale::filament/admin/pages/dashboard.navigation.title');
    }

    public static function getNavigationGroup(): string|UnitEnum
    {
        return NavigationGroup::Dashboard;
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return null;
    }

    public function getTitle(): string
    {
        return __('point-of-sale::filament/admin/pages/dashboard.title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns([
                        'default' => 1,
                        'sm'      => 2,
                        'xl'      => 4,
                    ])
                    ->schema([
                        Select::make('selectedTerminals')
                            ->label(__('point-of-sale::filament/admin/pages/dashboard.filters-form.terminals'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn (): array => Config::query()->pluck('name', 'id')->all())
                            ->reactive(),
                    ]),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            SessionStatsWidget::class,
            TopProductsWidget::class,
        ];
    }
}
