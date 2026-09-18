<?php

namespace Webkul\PointOfSale\Filament\Pos\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Facades\Tax;
use Webkul\Account\Models\Product as AccountProduct;
use Webkul\Account\Models\Tax as TaxModel;
use Webkul\Inventory\Models\Product as InventoryProduct;
use Webkul\Inventory\Support\StockScope;
use Webkul\Partner\Models\Partner;
use Webkul\PointOfSale\Enums\OrderState;
use Webkul\PointOfSale\Enums\TaxDisplay;
use Webkul\PointOfSale\Enums\SessionState;
use Webkul\PointOfSale\Facades\PointOfSale;
use Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource;
use Webkul\PointOfSale\Filament\Admin\Clusters\Reporting\Pages\SalesDetails;
use Webkul\PointOfSale\Models\Bill;
use Webkul\PointOfSale\Models\Category;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Note;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\OrderLine;
use Webkul\PointOfSale\Models\PaymentMethod;
use Webkul\PointOfSale\Models\Session;
use Webkul\PointOfSale\Services\ClosingControlReport;
use Webkul\PointOfSale\Services\PriceResolver;
use Webkul\PointOfSale\Services\SessionWorkflow;
use Webkul\PointOfSale\Services\TerminalProductCreator;
use Webkul\Product\Models\PriceList;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\ProductAttribute;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Product\Models\ProductCombination;

class Terminal extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $slug = 'terminal/{session}';

    protected string $view = 'point-of-sale::filament.pos.pages.terminal';

    protected static bool $shouldRegisterNavigation = false;

    public Session $session;

    public Config $config;

    public array $cart = [];

    public array $payments = [];

    public ?int $selectedPaymentIndex = null;

    public string $paymentBuffer = '';

    public bool $toInvoice = false;

    public ?int $priceListId = null;

    public ?string $shippedAt = null;

    public bool $isTakeaway = false;

    public ?int $selectedCategoryId = null;

    public ?string $search = null;

    public string $screen = 'products';

    public ?int $partnerId = null;

    public ?array $lastOrder = null;

    public float $globalDiscount = 0;

    public int $pendingSyncCount = 0;

    protected array $accountProducts = [];

    protected array $lineTaxes = [];

    public ?string $activeLineKey = null;

    public string $numpadMode = 'qty';

    public string $numpadBuffer = '';

    public string $noteDraft = '';

    public ?int $variantProductId = null;

    public array $variantSelection = [];

    public float $openingCash = 0;

    public ?string $openingNote = null;

    public array $moneyDetails = [];

    public function mount(Session $session): void
    {
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

        $this->restoreActiveOrder();
    }

    protected function activeOrderSessionKey(): string
    {
        return 'point-of-sale.active-order.'.$this->session->id;
    }

    protected function rememberActiveOrder(): void
    {
        $this->activeOrderId
            ? session()->put($this->activeOrderSessionKey(), $this->activeOrderId)
            : session()->forget($this->activeOrderSessionKey());
    }

    protected function restoreActiveOrder(): void
    {
        $remembered = session()->get($this->activeOrderSessionKey());

        $order = $remembered
            ? $this->draftOrderById((int) $remembered)
            : null;

        $order ??= $this->getDraftOrders()->last();

        if (! $order) {
            return;
        }

        $this->loadDraft($order);
    }

    protected function draftOrderById(int $orderId): ?Order
    {
        return Order::withoutGlobalScopes()
            ->with('lines')
            ->where('session_id', $this->session->id)
            ->where('state', OrderState::DRAFT)
            ->find($orderId);
    }

    protected function loadDraft(Order $order): void
    {
        $order->loadMissing('lines');

        $this->activeOrderId = $order->id;

        $this->partnerId = $order->partner_id;

        $this->cart = $order->lines
            ->mapWithKeys(fn (OrderLine $line): array => [(string) $line->product_id => [
                'uuid'          => $line->uuid,
                'product_id'    => $line->product_id,
                'name'          => $line->full_product_name ?? $line->name,
                'qty'           => (float) $line->qty,
                'price_unit'    => (float) $line->price_unit,
                'discount'      => (float) $line->discount,
                'note'          => $line->note,
                'customer_note' => $line->customer_note,
            ]])
            ->all();

        $this->activeLineKey = null;

        $this->rememberActiveOrder();
    }

    public function getHeading(): string
    {
        return '';
    }

    public function imageUrl(Product $product): ?string
    {
        $image = collect($product->images)->first();

        return $image ? Storage::url($image) : null;
    }

    public function categoryImageUrl(Category $category): ?string
    {
        return filled($category->image) ? Storage::url($category->image) : null;
    }

    public function money(float $amount): string
    {
        return money($amount, $this->config->currency?->name ?? $this->config->company?->currency?->name);
    }

    public function salesDetailsUrl(): string
    {
        return SalesDetails::getUrl(panel: 'admin');
    }

    public function backUrl(): string
    {
        return ConfigResource::getUrl(panel: 'admin');
    }

    public function getTitle(): string
    {
        return $this->config->name;
    }

    public function getCategories(): Collection
    {
        $query = Category::query()->orderBy('sort');

        if ($this->config->limit_categories && $this->config->categories->isNotEmpty()) {
            $query->whereIn('id', $this->config->categories->pluck('id'));
        }

        return $query->get();
    }
    /**
     * @return array<int, float>
     */
    public function cartQuantityByProduct(): array
    {
        $quantities = [];

        $productIds = collect($this->cart)->pluck('product_id')->filter()->unique();

        if ($productIds->isEmpty()) {
            return $quantities;
        }

        $parents = Product::query()
            ->whereKey($productIds)
            ->pluck('parent_id', 'id');

        foreach ($this->cart as $line) {
            $productId = (int) $line['product_id'];

            $key = (int) ($parents[$productId] ?? $productId);

            $quantities[$key] = ($quantities[$key] ?? 0) + (float) $line['qty'];
        }

        return $quantities;
    }

    public function getProducts(): Collection
    {
        return Product::query()
            ->where('available_in_pos', true)
            ->whereNull('parent_id')
            ->when(filled($this->search), fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->when($this->selectedCategoryId, fn ($query) => $query->whereHas(
                'posCategories',
                fn ($categories) => $categories->whereKey($this->selectedCategoryId),
            ))
            ->limit($this->config->limited_products_amount ?: 100)
            ->get();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $this->selectedCategoryId === $categoryId ? null : $categoryId;
    }

    public function needsOpeningControl(): bool
    {
        return $this->session->state === SessionState::OPENING_CONTROL;
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

    public function openMoneyDetails(): void
    {
        $this->dispatch('open-modal', id: 'pos-money-details');
    }

    public function stepMoneyDetail(string $value, int $step): void
    {
        $quantity = (int) ($this->moneyDetails[$value] ?? 0) + $step;

        $this->moneyDetails[$value] = max(0, $quantity);
    }

    public function moneyDetailsTotal(): float
    {
        return float_round(collect($this->moneyDetails)->reduce(
            fn (float $total, $quantity, $value): float => $total + ((float) $value * (int) $quantity),
            0.0,
        ), precisionDigits: 2);
    }

    public function confirmMoneyDetails(): void
    {
        $total = $this->moneyDetailsTotal();

        $this->openingCash = $total;

        $this->openingNote = $this->moneyDetailsNote($total);

        $this->dispatch('close-modal', id: 'pos-money-details');
    }

    protected function moneyDetailsNote(float $total): ?string
    {
        if (float_is_zero($total, precisionDigits: 2)) {
            return null;
        }

        $note = __('point-of-sale::filament/pos/pages/terminal.money-details.heading')."\n";

        foreach ($this->getBills() as $bill) {
            $quantity = (int) ($this->moneyDetails[(string) (float) $bill->value] ?? 0);

            if ($quantity === 0) {
                continue;
            }

            $note .= "\t{$quantity} x ".number_format((float) $bill->value, 2)."\n";
        }

        return $note.__('point-of-sale::filament/pos/pages/terminal.money-details.total', [
            'total' => number_format($total, 2),
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

    public ?int $activeOrderId = null;

    public string $cashMovementType = 'in';

    public ?float $cashMovementAmount = null;

    public ?string $cashMovementReason = null;

    public array $newProduct = ['name' => null, 'barcode' => null, 'price' => null, 'category_id' => null];

    public ?float $closingCash = null;

    public array $paymentCounted = [];

    public bool $showCashMoves = false;

    public ?string $closingNote = null;

    public function getDraftOrders(): Collection
    {
        return Order::withoutGlobalScopes()
            ->where('session_id', $this->session->id)
            ->where('state', OrderState::DRAFT)
            ->orderBy('id')
            ->get();
    }

    public function getSessionOrders(): Collection
    {
        return Order::withoutGlobalScopes()
            ->where('session_id', $this->session->id)
            ->with('partner')
            ->orderByDesc('id')
            ->get();
    }

    public function goToOrders(): void
    {
        $this->dispatch('open-modal', id: 'pos-orders');
    }

    public function newOrder(): void
    {
        $this->persistDraft();

        $this->resetCart();

        $this->lastOrder = null;

        $order = PointOfSale::saveDraftOrder([
            'uuid'       => (string) Str::uuid(),
            'config_id'  => $this->config->id,
            'session_id' => $this->session->id,
            'lines'      => [],
        ]);

        $this->activeOrderId = $order->id;

        $this->rememberActiveOrder();

        $this->screen = 'products';
    }

    public function switchOrder(int $orderId): void
    {
        $this->persistDraft();

        $order = $this->draftOrderById($orderId);

        if (! $order) {
            return;
        }

        $this->loadDraft($order);

        $this->dispatch('close-modal', id: 'pos-orders');

        $this->screen = 'products';
    }

    public function discardOrder(int $orderId): void
    {
        $order = Order::withoutGlobalScopes()->find($orderId);

        if (! $order) {
            return;
        }

        PointOfSale::discardDraftOrder($order);

        if ($this->activeOrderId === $orderId) {
            $this->resetCart();
        }
    }

    public function persistDraft(): void
    {
        if (empty($this->cart) && ! $this->activeOrderId) {
            return;
        }

        $order = PointOfSale::saveDraftOrder([
            'uuid'       => $this->draftUuid(),
            'config_id'  => $this->config->id,
            'session_id' => $this->session->id,
            'partner_id' => $this->partnerId,
            'lines'      => $this->linePayloads(),
        ]);

        $this->activeOrderId = $order->id;

        $this->rememberActiveOrder();
    }

    protected function draftUuid(): string
    {
        if ($this->activeOrderId) {
            $uuid = Order::withoutGlobalScopes()->whereKey($this->activeOrderId)->value('uuid');

            if ($uuid) {
                return $uuid;
            }
        }

        return (string) Str::uuid();
    }

    protected function resetCart(): void
    {
        $this->cart = [];

        $this->payments = [];

        $this->partnerId = null;

        $this->activeLineKey = null;

        $this->numpadBuffer = '';

        $this->selectedPaymentIndex = null;

        $this->paymentBuffer = '';

        $this->toInvoice = false;

        $this->noteDraft = '';

        $this->activeOrderId = null;

        $this->rememberActiveOrder();
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

    public function paymentDifference(array $method): float
    {
        $counted = $this->paymentCounted[$method['id']] ?? null;

        return $counted === null || $counted === ''
            ? 0.0
            : float_round((float) $counted - $method['amount'], precisionDigits: 2);
    }

    public function cashDifference(array $cash): float
    {
        return $this->closingCash === null
            ? 0.0
            : float_round((float) $this->closingCash - $cash['amount'], precisionDigits: 2);
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

    public function closingControl(): array
    {
        return app(ClosingControlReport::class)->build($this->session->refresh());
    }

    public ?int $productInfoId = null;

    public function openProductInfo(int $productId): void
    {
        $this->productInfoId = $productId;

        $this->dispatch('open-modal', id: 'pos-product-info');
    }

    public function productInfo(): ?array
    {
        if (! $this->productInfoId) {
            return null;
        }

        $product = InventoryProduct::withoutGlobalScopes()->find($this->productInfoId);

        if (! $product) {
            return null;
        }

        $scope = StockScope::make()->forLocations($this->config->operationType?->source_location_id);

        $scoped = $product->withStockScope($scope);

        $price = (float) $product->price;

        $cost = (float) $product->cost;

        $line = collect($this->cart)->firstWhere('product_id', $product->id);

        $quantity = $line ? (float) $line['qty'] : 0.0;

        return [
            'name'         => $product->name,
            'available'    => (float) $scoped->free_qty,
            'forecasted'   => (float) $scoped->virtual_available_qty,
            'price'        => $price,
            'cost'         => $cost,
            'margin'       => $price - $cost,
            'margin_ratio' => $price > 0 ? (($price - $cost) / $price) * 100 : 0.0,
            'qty'          => $quantity,
            'total_price'  => $price * $quantity,
            'total_cost'   => $cost * $quantity,
            'total_margin' => ($price - $cost) * $quantity,
        ];
    }

    public function openProductForm(): void
    {
        $this->newProduct = ['name' => null, 'barcode' => null, 'price' => null, 'category_id' => null];

        $this->dispatch('open-modal', id: 'pos-product-form');
    }

    public function createProduct(): void
    {
        try {
            $product = app(TerminalProductCreator::class)->create($this->config, $this->newProduct);

            $this->dispatch('close-modal', id: 'pos-product-form');

            $this->addProduct($product->id);

            Notification::make()
                ->success()
                ->title(__('point-of-sale::filament/pos/pages/terminal.product-form.notification.title'))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function openCustomers(): void
    {
        $this->dispatch('open-modal', id: 'pos-customers');
    }

    public function openNotes(): void
    {
        if (! $this->activeLineKey || ! isset($this->cart[$this->activeLineKey])) {
            return;
        }

        $this->noteDraft = (string) ($this->cart[$this->activeLineKey]['note'] ?? '');

        $this->dispatch('open-modal', id: 'pos-notes');
    }

    public function toggleNote(int $noteId): void
    {
        $name = $this->getNotes()->firstWhere('id', $noteId)?->name;

        if (! $name) {
            return;
        }

        $lines = collect(explode("\n", $this->noteDraft))
            ->reject(fn (string $line): bool => $line === '')
            ->values();

        $this->noteDraft = $lines->contains($name)
            ? $lines->reject(fn (string $line): bool => $line === $name)->implode("\n")
            : $lines->push($name)->implode("\n");
    }

    public function isNoteSelected(string $name): bool
    {
        return collect(explode("\n", $this->noteDraft))->contains($name);
    }

    public function discardNote(): void
    {
        $this->noteDraft = '';

        $this->dispatch('close-modal', id: 'pos-notes');
    }

    public function getNotes(): Collection
    {
        return Note::query()->orderBy('sort')->get();
    }

    /**
     * @return string|array<int, string>
     */
    public function noteColor(string $name): string|array
    {
        $color = $this->getNotes()->firstWhere('name', $name)?->color;

        return filled($color) ? Color::hex($color) : 'gray';
    }

    public function getCustomers(): Collection
    {
        return Partner::query()
            ->where(owned_by_company($this->config->company_id))
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function selectCustomer(?int $partnerId): void
    {
        $this->partnerId = $partnerId;

        $this->persistDraft();

        $this->dispatch('close-modal', id: 'pos-customers');
    }

    public function applyNote(): void
    {
        if (! $this->activeLineKey || ! isset($this->cart[$this->activeLineKey])) {
            return;
        }

        $this->cart[$this->activeLineKey]['note'] = trim($this->noteDraft) === ''
            ? null
            : $this->noteDraft;

        $this->noteDraft = '';

        $this->persistDraft();

        $this->dispatch('close-modal', id: 'pos-notes');
    }

    public function removeActiveLine(): void
    {
        if (! $this->activeLineKey) {
            return;
        }

        $this->removeLine($this->activeLineKey);
    }

    public function selectedCustomerName(): ?string
    {
        return $this->partnerId
            ? Partner::query()->whereKey($this->partnerId)->value('name')
            : null;
    }

    public function selectLine(string $key): void
    {
        $this->activeLineKey = (string) $this->activeLineKey === $key ? null : $key;

        $this->numpadBuffer = '';
    }

    public function setNumpadMode(string $mode): void
    {
        $this->numpadMode = $mode;

        $this->numpadBuffer = '';
    }

    public function pressNumpad(string $value): void
    {
        if (! $this->activeLineKey || ! isset($this->cart[$this->activeLineKey])) {
            return;
        }

        if ($value === 'backspace') {
            $this->numpadBuffer = substr($this->numpadBuffer, 0, -1);
        } elseif ($value === 'clear') {
            $this->numpadBuffer = '';
        } elseif ($value === '.' && str_contains($this->numpadBuffer, '.')) {
            return;
        } else {
            $this->numpadBuffer .= $value;
        }

        $this->applyNumpad();
    }

    protected function applyNumpad(): void
    {
        $amount = (float) ($this->numpadBuffer === '' ? 0 : $this->numpadBuffer);

        match ($this->numpadMode) {
            'price'    => $this->setPrice($this->activeLineKey, $amount),
            'discount' => $this->setDiscount($this->activeLineKey, $amount),
            default    => $this->setQty($this->activeLineKey, $amount),
        };
    }

    public function setPrice(string $key, float $price): void
    {
        if (! isset($this->cart[$key]) || ! $this->config->enable_price_control) {
            return;
        }

        $this->cart[$key]['price_unit'] = max(0, $price);

        $this->cart[$key]['price_manual'] = true;

        $this->persistDraft();
    }

    public function resolvePrice(Product $product, float $quantity = 1.0): float
    {
        return app(PriceResolver::class)->resolve($product, $this->selectedPriceList(), $quantity);
    }

    public function selectedPriceList(): ?PriceList
    {
        if ($this->priceListId) {
            return $this->availablePriceLists()->firstWhere('id', $this->priceListId);
        }

        return app(PriceResolver::class)->priceListForConfig($this->config);
    }

    public function availablePriceLists(): Collection
    {
        if (! $this->config->enable_price_list) {
            return collect();
        }

        return $this->config->priceLists->when(
            $this->config->priceList && ! $this->config->priceLists->contains('id', $this->config->price_list_id),
            fn (Collection $lists): Collection => $lists->push($this->config->priceList),
        )->values();
    }

    public function openPriceLists(): void
    {
        $this->dispatch('open-modal', id: 'pos-price-lists');
    }

    public function selectPriceList(?int $priceListId): void
    {
        $this->priceListId = $priceListId;

        foreach ($this->cart as $key => $line) {
            if ($line['price_manual'] ?? false) {
                continue;
            }

            $product = Product::find($line['product_id']);

            if (! $product) {
                continue;
            }

            $this->cart[$key]['price_unit'] = $this->resolvePrice($product, (float) $line['qty']);
        }

        $this->persistDraft();

        $this->dispatch('close-modal', id: 'pos-price-lists');
    }

    public function selectedPriceListName(): ?string
    {
        return $this->selectedPriceList()?->name;
    }

    public function toggleTakeaway(): void
    {
        if (! $this->config->enable_takeaway) {
            return;
        }

        $this->isTakeaway = ! $this->isTakeaway;

        $this->persistDraft();
    }

    public function fiscalPositionId(): ?int
    {
        if ($this->isTakeaway && $this->config->takeaway_fiscal_position_id) {
            return $this->config->takeaway_fiscal_position_id;
        }

        return $this->config->fiscal_position_id;
    }

    public function setShippedAt(?string $shippedAt): void
    {
        if (! $this->config->enable_ship_later) {
            return;
        }

        $this->shippedAt = $shippedAt;

        $this->persistDraft();
    }

    public function addTip(float $amount): void
    {
        if (! $this->config->enable_tip || ! $this->config->tip_product_id) {
            return;
        }

        $tipProduct = Product::find($this->config->tip_product_id);

        if (! $tipProduct) {
            return;
        }

        $existing = collect($this->cart)
            ->search(fn (array $line): bool => (int) $line['product_id'] === $tipProduct->id);

        if ($existing !== false) {
            $this->cart[$existing]['price_unit'] = max(0, $amount);
            $this->cart[$existing]['price_manual'] = true;

            $this->persistDraft();

            return;
        }

        $key = (string) Str::uuid();

        $this->cart[$key] = [
            'uuid'          => $key,
            'product_id'    => $tipProduct->id,
            'name'          => $tipProduct->name,
            'qty'           => 1,
            'price_unit'    => max(0, $amount),
            'price_manual'  => true,
            'discount'      => 0,
            'note'          => null,
            'customer_note' => null,
        ];

        $this->persistDraft();
    }

    public function tipAmount(): float
    {
        if (! $this->config->tip_product_id) {
            return 0.0;
        }

        return (float) collect($this->cart)
            ->firstWhere('product_id', $this->config->tip_product_id)['price_unit'] ?? 0.0;
    }

    public function getPaymentMethods(): Collection
    {
        return $this->config->paymentMethods;
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        if (! $product->is_configurable) {
            $this->addProduct($productId);

            return;
        }

        $this->variantProductId = $productId;

        $this->variantSelection = $this->variantAttributes()
            ->mapWithKeys(fn (array $attribute): array => [
                $attribute['id'] => $attribute['options']->first()?->attribute_option_id,
            ])
            ->all();

        $this->dispatch('open-modal', id: 'pos-variants');
    }

    /**
     * @return Collection<int, array{id: int, name: string, options: Collection}>
     */
    public function variantAttributes(): Collection
    {
        if (! $this->variantProductId) {
            return collect();
        }

        $product = Product::with(['attributes.attribute', 'attributes.values.attributeOption'])
            ->findOrFail($this->variantProductId);

        return $product->attributes->map(fn (ProductAttribute $attribute): array => [
            'id'      => $attribute->attribute_id,
            'name'    => $attribute->attribute?->name,
            'options' => $attribute->values,
        ])->values();
    }

    public function configurableProductName(): ?string
    {
        return $this->variantProductId
            ? Product::query()->whereKey($this->variantProductId)->value('name')
            : null;
    }

    public function selectVariantOption(int $attributeId, int $optionId): void
    {
        $this->variantSelection[$attributeId] = $optionId;
    }

    public function resolvedVariant(): ?Product
    {
        if (! $this->variantProductId || empty($this->variantSelection)) {
            return null;
        }

        $valueIds = ProductAttributeValue::query()
            ->where('product_id', $this->variantProductId)
            ->whereIn('attribute_option_id', array_values($this->variantSelection))
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        if (count($valueIds) !== count($this->variantSelection)) {
            return null;
        }

        return Product::query()
            ->where('parent_id', $this->variantProductId)
            ->get()
            ->first(fn (Product $variant): bool => ProductCombination::query()
                ->where('product_id', $variant->id)
                ->pluck('product_attribute_value_id')
                ->sort()
                ->values()
                ->all() === $valueIds);
    }

    public function confirmVariant(): void
    {
        $variant = $this->resolvedVariant();

        if (! $variant) {
            return;
        }

        $this->addProduct($variant->id);

        $this->variantProductId = null;

        $this->variantSelection = [];

        $this->dispatch('close-modal', id: 'pos-variants');
    }

    public function discardVariant(): void
    {
        $this->variantProductId = null;

        $this->variantSelection = [];

        $this->dispatch('close-modal', id: 'pos-variants');
    }

    public function addProduct(int $productId): void
    {
        $product = Product::findOrFail($productId);

        $key = (string) $productId;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;

            $this->activeLineKey = $key;

            $this->persistDraft();

            return;
        }

        $this->activeLineKey = $key;

        $this->cart[$key] = [
            'uuid'          => (string) Str::uuid(),
            'product_id'    => $product->id,
            'name'          => $product->name,
            'qty'           => 1,
            'price_unit'    => $this->resolvePrice($product),
            'price_manual'  => false,
            'discount'      => 0,
            'note'          => null,
            'customer_note' => null,
        ];

        $this->persistDraft();
    }

    public function setQty(string $key, float $qty): void
    {
        if (! isset($this->cart[$key])) {
            return;
        }

        if (float_compare($qty, 0, precisionDigits: 4) <= 0 && $this->numpadBuffer === '') {
            unset($this->cart[$key]);

            $this->activeLineKey = null;

            $this->persistDraft();

            return;
        }

        $this->cart[$key]['qty'] = $qty;

        $this->persistDraft();
    }

    public function setDiscount(string $key, float $discount): void
    {
        if (! isset($this->cart[$key]) || ! $this->config->enable_line_discount) {
            return;
        }

        $this->cart[$key]['discount'] = max(0, min(100, $discount));

        $this->persistDraft();
    }

    public function removeLine(string $key): void
    {
        unset($this->cart[$key]);

        if ($this->activeLineKey === $key) {
            $this->activeLineKey = null;
        }

        $this->persistDraft();
    }

    public function addPayment(int $paymentMethodId, ?float $amount = null): void
    {
        $method = PaymentMethod::findOrFail($paymentMethodId);

        $this->cleanEmptyPayments();

        $existing = collect($this->payments)
            ->search(fn (array $payment): bool => $payment['payment_method_id'] === $method->id);

        if ($existing !== false && $amount === null) {
            $this->selectPayment($existing);

            return;
        }

        $this->payments[] = [
            'uuid'              => (string) Str::uuid(),
            'payment_method_id' => $method->id,
            'name'              => $method->name,
            'amount'            => $amount ?? $this->remainingDue(),
        ];

        $this->selectedPaymentIndex = array_key_last($this->payments);

        $this->paymentBuffer = '';
    }

    protected function cleanEmptyPayments(): void
    {
        $this->payments = collect($this->payments)
            ->reject(fn (array $payment): bool => float_is_zero((float) $payment['amount'], precisionDigits: 2))
            ->values()
            ->all();

        if ($this->selectedPaymentIndex !== null && ! isset($this->payments[$this->selectedPaymentIndex])) {
            $this->selectedPaymentIndex = $this->payments === [] ? null : array_key_last($this->payments);
        }
    }

    public function selectPayment(int $index): void
    {
        if (! isset($this->payments[$index])) {
            return;
        }

        $this->selectedPaymentIndex = $index;

        $this->paymentBuffer = '';
    }

    public function removePayment(int $index): void
    {
        unset($this->payments[$index]);

        $this->payments = array_values($this->payments);

        $this->selectedPaymentIndex = $this->payments === [] ? null : array_key_last($this->payments);

        $this->paymentBuffer = '';
    }

    public function toggleToInvoice(): void
    {
        $this->toInvoice = ! $this->toInvoice;
    }

    public function pressPaymentNumpad(string $value): void
    {
        if ($this->selectedPaymentIndex === null || ! isset($this->payments[$this->selectedPaymentIndex])) {
            return;
        }

        if (in_array($value, ['+10', '+20', '+50'], true)) {
            $base = $this->paymentBuffer === ''
                ? (float) $this->payments[$this->selectedPaymentIndex]['amount']
                : (float) $this->paymentBuffer;

            $this->paymentBuffer = (string) float_round($base + (float) ltrim($value, '+'), precisionDigits: 2);
        } elseif ($value === '-') {
            $this->paymentBuffer = str_starts_with($this->paymentBuffer, '-')
                ? ltrim($this->paymentBuffer, '-')
                : '-'.$this->paymentBuffer;
        } elseif ($value === 'backspace') {
            $this->paymentBuffer = substr($this->paymentBuffer, 0, -1);
        } elseif ($value === '.' && str_contains($this->paymentBuffer, '.')) {
            return;
        } else {
            $this->paymentBuffer .= $value;
        }

        $this->payments[$this->selectedPaymentIndex]['amount'] = in_array($this->paymentBuffer, ['', '-', '.', '-.'], true)
            ? 0.0
            : float_round((float) $this->paymentBuffer, precisionDigits: 2);
    }

    public function cartSubtotal(): float
    {
        return $this->cartTotals()['untaxed'];
    }

    public function cartTax(): float
    {
        return $this->cartTotals()['tax'];
    }

    public function cartTotal(): float
    {
        return $this->cartTotals()['total'];
    }

    public function displayUnitPrice(int $productId, float $priceUnit): float
    {
        if ($this->config->tax_display !== TaxDisplay::TOTAL) {
            return float_round($priceUnit, precisionDigits: 2);
        }

        $taxes = $this->taxesFor($productId);

        if ($taxes->isEmpty()) {
            return float_round($priceUnit, precisionDigits: 2);
        }

        $result = Tax::computeAll(
            $taxes,
            $priceUnit,
            $this->config->company?->currency,
            1.0,
            $this->productFor($productId),
        );

        return float_round((float) $result['total_included'], precisionDigits: 2);
    }

    public function cartTotals(): array
    {
        $untaxed = 0.0;

        $total = 0.0;

        foreach ($this->linePayloads() as $line) {
            $priceUnit = (float) $line['price_unit'] * (1 - ((float) $line['discount'] / 100));

            $taxes = $this->taxesFor((int) $line['product_id']);

            if ($taxes->isEmpty()) {
                $lineUntaxed = $priceUnit * (float) $line['qty'];

                $untaxed += $lineUntaxed;

                $total += $lineUntaxed;

                continue;
            }

            $result = Tax::computeAll(
                $taxes,
                $priceUnit,
                $this->config->company?->currency,
                (float) $line['qty'],
                $this->productFor((int) $line['product_id']),
            );

            $untaxed += (float) $result['total_excluded'];

            $total += (float) $result['total_included'];
        }

        return [
            'untaxed' => float_round($untaxed, precisionDigits: 2),
            'tax'     => float_round($total - $untaxed, precisionDigits: 2),
            'total'   => float_round($total, precisionDigits: 2),
        ];
    }

    protected function productFor(int $productId): ?AccountProduct
    {
        return $this->accountProducts[$productId] ??= AccountProduct::withoutGlobalScopes()->find($productId);
    }

    protected function taxesFor(int $productId): Collection
    {
        if (array_key_exists($productId, $this->lineTaxes)) {
            return $this->lineTaxes[$productId];
        }

        $product = $this->productFor($productId);

        $taxIds = $product
            ? TaxModel::forProduct($product, TypeTaxUse::SALE, $this->config->company_id)
            : [];

        return $this->lineTaxes[$productId] = TaxModel::withoutGlobalScopes()->whereKey($taxIds)->get();
    }

    public function paidTotal(): float
    {
        return float_round(collect($this->payments)->sum('amount'), precisionDigits: 2);
    }

    public function remainingDue(): float
    {
        return float_round(max($this->cartTotal() - $this->paidTotal(), 0), precisionDigits: 2);
    }

    public function changeDue(): float
    {
        return float_round(max($this->paidTotal() - $this->cartTotal(), 0), precisionDigits: 2);
    }

    public function hasRemainingDue(): bool
    {
        return float_compare($this->remainingDue(), 0, precisionDigits: 2) > 0;
    }

    public function goToPayment(): void
    {
        if (empty($this->cart)) {
            return;
        }

        $this->screen = 'payment';
    }

    public function goToProducts(): void
    {
        $this->screen = 'products';
    }

    public function customerRequirementReason(): ?string
    {
        if ($this->partnerId) {
            return null;
        }

        if ($this->config->enable_customer_required) {
            return 'required-by-terminal';
        }

        if ($this->toInvoice) {
            return 'required-to-invoice';
        }

        if (filled($this->shippedAt)) {
            return 'required-to-ship';
        }

        $splitPayment = collect($this->payments)->contains(
            fn (array $payment): bool => (bool) $this->getPaymentMethods()
                ->firstWhere('id', $payment['payment_method_id'] ?? null)?->is_split_transaction
        );

        return $splitPayment ? 'required-by-payment-method' : null;
    }

    public function validateOrder(): void
    {
        $reason = $this->customerRequirementReason();

        if ($reason !== null) {
            Notification::make()
                ->warning()
                ->body(__("point-of-sale::system.order-workflow.customer.{$reason}"))
                ->send();

            $this->openCustomers();

            return;
        }

        try {
            $order = PointOfSale::syncOrder([
                'uuid'          => $this->draftUuid(),
                'config_id'     => $this->config->id,
                'session_id'    => $this->session->id,
                'partner_id'    => $this->partnerId,
                'is_to_invoice' => $this->toInvoice,
                'price_list_id' => $this->selectedPriceList()?->id,
                'fiscal_position_id' => $this->fiscalPositionId(),
                'shipped_at'    => $this->shippedAt,
                'is_takeaway'   => $this->isTakeaway,
                'lines'         => $this->linePayloads(),
                'payments'      => $this->paymentPayloads(),
            ]);

            $this->lastOrder = $this->receiptFor($order);

            $this->resetCart();

            if ($this->config->enable_receipt_print) {
                $this->screen = 'receipt';

                if ($this->config->enable_receipt_auto_print) {
                    $this->dispatch('pos-print-receipt');
                }
            } else {
                $this->newOrder();
            }

            Notification::make()
                ->success()
                ->title(__('point-of-sale::filament/pos/pages/terminal.notification.success.title'))
                ->body(__('point-of-sale::filament/pos/pages/terminal.notification.success.body', ['order' => $order->name]))
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->danger()
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function syncEndpoint(): string
    {
        return route('admin.api.v1.point-of-sale.orders.sync');
    }

    public function queueOrderPayload(): array
    {
        return [
            'uuid'          => $this->draftUuid(),
            'config_id'     => $this->config->id,
            'session_id'    => $this->session->id,
            'partner_id'    => $this->partnerId,
            'is_to_invoice' => $this->toInvoice,
            'lines'         => $this->linePayloads(),
            'payments'      => $this->paymentPayloads(),
        ];
    }

    public function markQueued(int $pending): void
    {
        $this->pendingSyncCount = $pending;

        $this->resetCart();

        $this->screen = 'products';

        Notification::make()
            ->warning()
            ->title(__('point-of-sale::filament/pos/pages/terminal.notification.queued.title'))
            ->body(__('point-of-sale::filament/pos/pages/terminal.notification.queued.body', ['count' => $pending]))
            ->send();
    }

    public function setGlobalDiscount(float $percentage): void
    {
        $this->globalDiscount = max(0, min(100, $percentage));
    }

    protected function linePayloads(): array
    {
        return collect($this->cart)->map(fn (array $line): array => [
            'uuid'       => $line['uuid'],
            'product_id' => $line['product_id'],
            'qty'        => $line['qty'],
            'price_unit' => $line['price_unit'],
            'discount'   => float_compare($this->globalDiscount, 0, precisionDigits: 2) > 0
                ? max((float) $line['discount'], $this->globalDiscount)
                : (float) $line['discount'],
            'note'          => $line['note'] ?? null,
            'customer_note' => $line['customer_note'] ?? null,
        ])->values()->all();
    }

    protected function paymentPayloads(): array
    {
        return collect($this->payments)->map(fn (array $payment): array => [
            'uuid'              => $payment['uuid'],
            'payment_method_id' => $payment['payment_method_id'],
            'amount'            => $payment['amount'],
        ])->values()->all();
    }

    protected function receiptFor(Order $order): array
    {
        return [
            'name'           => $order->name,
            'reference'      => $order->reference,
            'amount_untaxed' => $order->amount_untaxed,
            'amount_tax'     => $order->amount_tax,
            'amount_total'   => $order->amount_total,
            'amount_paid'    => $order->amount_paid,
            'amount_return'  => $order->amount_return,
            'header'         => $this->config->receipt_header,
            'footer'         => $this->config->receipt_footer,
            'lines'          => $order->lines->map(fn ($line): array => [
                'name'  => $line->full_product_name,
                'qty'   => $line->qty,
                'total' => $line->price_subtotal_incl,
            ])->all(),
        ];
    }
}
