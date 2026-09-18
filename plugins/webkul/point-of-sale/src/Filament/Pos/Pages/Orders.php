<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\Session;

class Orders extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $slug = '{session}/orders';

    protected static ?int $navigationSort = 2;

    protected string $view = 'point-of-sale::filament.pos.pages.orders';

    public static function getNavigationLabel(): string
    {
        return __('point-of-sale::filament/pos/pages/orders.navigation.label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::currentSession()?->orders()->count() ?? 0;

        return $count > 0 ? (string) $count : null;
    }

    public ?Session $session = null;

    public function mount(?Session $session = null): void
    {
        $this->session = $session ?? static::currentSession();
    }

    public static function getNavigationUrl(array $parameters = []): string
    {
        $session = static::currentSession();

        return $session
            ? static::getUrl(['session' => $session->getKey()])
            : Registers::getUrl();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) static::currentSession();
    }

    public function getTitle(): string
    {
        return __('point-of-sale::filament/pos/pages/orders.title');
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function getOrders(): Collection
    {
        $session = $this->session;

        if (! $session) {
            return collect();
        }

        return Order::withoutGlobalScopes()
            ->where('session_id', $session->getKey())
            ->with('partner')
            ->orderByDesc('id')
            ->get();
    }

    protected static function currentSession(): ?Session
    {
        return Session::query()
            ->whereIn('state', [SessionState::OPENED, SessionState::OPENING_CONTROL])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->first();
    }
}
