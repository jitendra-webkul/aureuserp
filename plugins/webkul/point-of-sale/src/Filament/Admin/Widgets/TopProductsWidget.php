<?php

namespace Webkul\PointOfSale\Filament\Admin\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Models\OrderLine;

class TopProductsWidget extends ChartWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '320px';

    public function getHeading(): string|Htmlable|null
    {
        return __('point-of-sale::filament/admin/widgets/top-products.heading');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $lines = OrderLine::query()
            ->select('product_id', DB::raw('SUM(price_subtotal) as revenue'))
            ->whereHas('order', fn ($query) => $query->whereIn('state', [
                OrderState::PAID,
                OrderState::DONE,
                OrderState::INVOICED,
            ]))
            ->groupBy('product_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->with('product')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('point-of-sale::filament/admin/widgets/top-products.dataset'),
                    'data'  => $lines->pluck('revenue')->map(fn ($revenue): float => (float) $revenue)->all(),
                ],
            ],
            'labels' => $lines->map(fn (OrderLine $line): string => $line->product?->name ?? '—')->all(),
        ];
    }
}
