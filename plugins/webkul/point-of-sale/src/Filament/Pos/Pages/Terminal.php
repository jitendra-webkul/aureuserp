<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Session;
use Webkul\PointOfSale\Services\BootLoader;
use Webkul\PointOfSale\Services\SessionWorkflow;
use Webkul\PointOfSale\Support\PosAccess;

abstract class Terminal extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $slug = 'terminal/{session}';

    protected string $view = 'point-of-sale::filament.pos.pages.till';

    protected static bool $shouldRegisterNavigation = false;

    public Session $session;

    public Config $config;

    public function mount(Session $session): void
    {
        abort_unless(PosAccess::reachesActiveSession($session), 403);

        $this->session = $session;

        $this->config = $session->config;

        if (! $session->isLive()) {
            $live = app(SessionWorkflow::class)->liveSessionFor($this->config);

            $this->redirect($live && PosAccess::reachesSession($live)
                ? static::getUrl(['session' => $live->getKey()])
                : $this->backUrl());

            return;
        }

        $this->session = PointOfSale::loginSession($session);
    }

    public function backUrl(): string
    {
        return Registers::getUrl();
    }

    public function bootPayload(): array
    {
        return array_merge(app(BootLoader::class)->load($this->config, $this->session), [
            'pending_refund' => session()->pull(static::pendingRefundKey($this->session)),
        ]);
    }

    public static function pendingRefundKey(Session $session): string
    {
        return 'point-of-sale.pending-refund.'.$session->getKey();
    }

    public function syncEndpoint(): string
    {
        return route('point-of-sale.till.orders.sync');
    }
}
