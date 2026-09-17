<?php

namespace Webkul\PointOfSale\Filament\Admin\Widgets;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\Session;

class SessionStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make(
                __('point-of-sale::filament/admin/widgets/session-stats.stats.open-sessions.label'),
                Session::query()->whereIn('state', [
                    SessionState::OPENING_CONTROL,
                    SessionState::OPENED,
                    SessionState::CLOSING_CONTROL,
                ])->count(),
            )
                ->description(__('point-of-sale::filament/admin/widgets/session-stats.stats.open-sessions.description'))
                ->icon('heroicon-o-play-circle')
                ->color('success'),

            Stat::make(
                __('point-of-sale::filament/admin/widgets/session-stats.stats.today-orders.label'),
                Order::query()
                    ->whereDate('ordered_at', today())
                    ->whereIn('state', [OrderState::PAID, OrderState::DONE, OrderState::INVOICED])
                    ->count(),
            )
                ->description(__('point-of-sale::filament/admin/widgets/session-stats.stats.today-orders.description'))
                ->icon('heroicon-o-receipt-percent')
                ->color('primary'),

            Stat::make(
                __('point-of-sale::filament/admin/widgets/session-stats.stats.today-revenue.label'),
                number_format((float) Order::query()
                    ->whereDate('ordered_at', today())
                    ->whereIn('state', [OrderState::PAID, OrderState::DONE, OrderState::INVOICED])
                    ->sum('amount_total'), 2),
            )
                ->description(__('point-of-sale::filament/admin/widgets/session-stats.stats.today-revenue.description'))
                ->icon('heroicon-o-banknotes')
                ->color('info'),

            Stat::make(
                __('point-of-sale::filament/admin/widgets/session-stats.stats.failed-operations.label'),
                Order::query()->where('has_failed_operation', true)->count(),
            )
                ->description(__('point-of-sale::filament/admin/widgets/session-stats.stats.failed-operations.description'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
