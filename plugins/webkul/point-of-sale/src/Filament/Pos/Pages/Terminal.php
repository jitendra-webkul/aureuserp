<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource;
use Webkul\PointOfSale\Models\Bill;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Session;
use Webkul\PointOfSale\Services\BootLoader;
use Webkul\PointOfSale\Services\ClosingControlReport;
use Webkul\PointOfSale\Services\SessionSalesDetailsReport;
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

    public float $openingCash = 0;

    public ?string $openingNote = null;

    public array $moneyDetails = [];

    public string $moneyDetailsTarget = 'opening';

    public string $cashMovementType = 'in';

    public ?float $cashMovementAmount = null;

    public ?string $cashMovementReason = null;

    public ?float $closingCash = null;

    public ?string $closingNote = null;

    public array $paymentCounted = [];

    public bool $showCashMoves = false;

    public function mount(Session $session): void
    {
        abort_unless(PosAccess::reachesSession($session), 403);

        $this->session = $session;

        $this->config = $session->config;

        if (! $session->isLive()) {
            $live = app(SessionWorkflow::class)->liveSessionFor($this->config);

            if (! $live) {
                redirect()->to(ConfigResource::getUrl(panel: 'admin'));

                return;
            }

            $this->session = $live;
        }
    }

    public function needsOpeningControl(): bool
    {
        return $this->session->state === SessionState::OPENING_CONTROL;
    }

    public function money(float $amount): string
    {
        return money($amount, $this->config->currency?->name ?? $this->config->company?->currency?->name);
    }

    public function backUrl(): string
    {
        return ConfigResource::getUrl(panel: 'admin');
    }

    public function downloadSalesDetails(): StreamedResponse
    {
        $report = app(SessionSalesDetailsReport::class)->build($this->session->refresh());

        $pdf = Pdf::loadView('point-of-sale::filament.pos.reports.sales-details', [
            ...$report,
            'money' => fn (float $amount): string => $this->money($amount),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $name = __('point-of-sale::filament/pos/reports/sales-details.filename')
            .' - '.str_replace('/', '_', (string) $this->session->name).'.pdf';

        return response()->streamDownload(function () use ($pdf): void {
            echo $pdf->output();
        }, $name);
    }

    public function bootPayload(): array
    {
        return app(BootLoader::class)->load($this->config, $this->session);
    }

    public function syncEndpoint(): string
    {
        return route('point-of-sale.till.orders.sync');
    }

    public function getBills(): Collection
    {
        return Bill::query()
            ->where(fn (Builder $query) => $query
                ->where('is_for_all_configs', true)
                ->orWhereHas('configs', fn (Builder $configs) => $configs->whereKey($this->config->id)))
            ->orderByDesc('value')
            ->get();
    }

    public function openMoneyDetails(string $target = 'opening'): void
    {
        $this->moneyDetailsTarget = $target;

        $this->dispatch('open-modal', id: 'pos-money-details');
    }

    public function stepMoneyDetail(int|string $billId, int $step): void
    {
        $quantity = (int) ($this->moneyDetails[(string) $billId] ?? 0) + $step;

        $this->moneyDetails[(string) $billId] = max(0, $quantity);
    }

    public function moneyDetailsTotal(): float
    {
        $bills = $this->getBills()->keyBy('id');

        return float_round(collect($this->moneyDetails)->reduce(
            fn (float $total, $quantity, $billId): float => $total
                + ((float) ($bills->get((int) $billId)?->value ?? 0) * (int) $quantity),
            0.0,
        ), precisionDigits: 2);
    }

    public function confirmMoneyDetails(): void
    {
        $total = $this->moneyDetailsTotal();

        if ($this->moneyDetailsTarget === 'closing') {
            $this->closingCash = $total;

            $this->closingNote = $this->moneyDetailsNote($total);
        } else {
            $this->openingCash = $total;

            $this->openingNote = $this->moneyDetailsNote($total);
        }

        $this->dispatch('close-modal', id: 'pos-money-details');
    }

    protected function moneyDetailsNote(float $total): ?string
    {
        if (float_is_zero($total, precisionDigits: 2)) {
            return null;
        }

        $heading = $this->moneyDetailsTarget === 'closing' ? 'closing-heading' : 'opening-heading';

        $note = __("point-of-sale::filament/pos/pages/terminal.money-details.{$heading}")."\n";

        foreach ($this->getBills()->sortBy('value') as $bill) {
            $quantity = (int) ($this->moneyDetails[(string) $bill->id] ?? 0);

            if ($quantity === 0) {
                continue;
            }

            $note .= "\t{$quantity} x ".$this->money((float) $bill->value)."\n";
        }

        return $note.__('point-of-sale::filament/pos/pages/terminal.money-details.total', [
            'total' => $this->money($total),
        ]);
    }

    public function confirmOpening(): void
    {
        try {
            $this->session = PointOfSale::confirmSessionOpeningControl(
                $this->session,
                (float) $this->openingCash,
                $this->openingNote,
            );
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }

    #[On('pos-open-cash-movement')]
    public function openCashMovementFromMenu(): void
    {
        $this->openCashMovement('in');
    }

    public function openCashMovement(string $type): void
    {
        $this->cashMovementType = $type;

        $this->cashMovementAmount = null;

        $this->cashMovementReason = null;

        $this->dispatch('open-modal', id: 'pos-cash-movement');
    }

    public function recordCashMovement(): void
    {
        try {
            $this->cashMovementType === 'in'
                ? PointOfSale::cashIn($this->session, (float) $this->cashMovementAmount, $this->cashMovementReason)
                : PointOfSale::cashOut($this->session, (float) $this->cashMovementAmount, $this->cashMovementReason);

            $this->dispatch('close-modal', id: 'pos-cash-movement');

            Notification::make()
                ->success()
                ->title(__('point-of-sale::filament/pos/pages/terminal.cash-movement.notification.title'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }

    #[On('pos-close-register')]
    public function closeRegisterFromMenu(): void
    {
        $this->goToClosing();
    }

    public function goToClosing(): void
    {
        $this->moneyDetails = [];

        $this->closingCash = null;

        $this->closingNote = null;

        $this->paymentCounted = [];

        $this->showCashMoves = false;

        $this->dispatch('open-modal', id: 'pos-closing');
    }

    public function toggleCashMoves(): void
    {
        $this->showCashMoves = ! $this->showCashMoves;
    }

    public function closingControl(): array
    {
        return app(ClosingControlReport::class)->build($this->session->refresh());
    }

    public function paymentDifference(array $method): float
    {
        $counted = $this->paymentCounted[$method['id']] ?? null;

        return $counted === null || $counted === ''
            ? 0.0
            : float_round((float) $counted - $method['amount'], precisionDigits: 2);
    }

    public function copyExpectedCash(): void
    {
        $this->closingCash = float_round((float) $this->session->expectedCashBalance(), precisionDigits: 2);
    }

    public function copyExpectedPayment(int $methodId, float $amount): void
    {
        $this->paymentCounted[$methodId] = float_round($amount, precisionDigits: 2);
    }

    public function cashDifference(array $cash): float
    {
        return $this->closingCash === null
            ? 0.0
            : float_round((float) $this->closingCash - $cash['amount'], precisionDigits: 2);
    }

    protected function paymentDifferences(): array
    {
        $methods = collect($this->closingControl()['non_cash_payment_methods']);

        return collect($this->paymentCounted)
            ->filter(fn ($counted): bool => $counted !== null && $counted !== '')
            ->mapWithKeys(function ($counted, $methodId) use ($methods): array {
                $amount = (float) ($methods->firstWhere('id', (int) $methodId)['amount'] ?? 0);

                return [(int) $methodId => float_round((float) $counted - $amount, precisionDigits: 2)];
            })
            ->all();
    }

    public function closeRegister(): void
    {
        try {
            PointOfSale::closeSessionWithAccounting(
                $this->session,
                $this->closingCash === null ? null : (float) $this->closingCash,
                $this->closingNote,
                null,
                $this->paymentDifferences(),
            );

            $this->redirect($this->backUrl());
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }
}
