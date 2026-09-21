<?php

namespace Webkul\PointOfSale\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Webkul\Account\Enums\AmountType;
use Webkul\Account\Enums\TypeTaxUse;
use Webkul\Account\Models\FiscalPosition;
use Webkul\Account\Models\FiscalPositionTax;
use Webkul\Account\Models\Product as AccountProduct;
use Webkul\Account\Models\Tax as TaxModel;
use Webkul\Account\Settings\TaxesSettings;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Partner\Models\Partner;
use Webkul\PointOfSale\Models\Bill;
use Webkul\PointOfSale\Models\Category;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Note;
use Webkul\PointOfSale\Models\Order;
use Webkul\PointOfSale\Models\OrderLine;
use Webkul\PointOfSale\Models\PaymentMethod;
use Webkul\PointOfSale\Models\Product;
use Webkul\PointOfSale\Models\Session;
use Webkul\Product\Models\PriceList;
use Webkul\Product\Models\PriceRuleItem;
use Webkul\Support\Models\Currency;
use Webkul\Support\Models\UOM;

class BootLoader
{
    public const PARTNER_LIMIT = 500;

    public function __construct(
        protected PriceResolver $prices,
    ) {}

    public function load(Config $config, Session $session): array
    {
        $taxes = $this->collectTaxes($config);

        return [
            'company'          => $this->company($config),
            'config'           => $this->config($config),
            'session'          => $this->session($session),
            'currencies'       => $this->currencies($config),
            'uoms'             => $this->uoms(),
            'categories'       => $this->categories($config),
            'products'         => $this->products($config, $session),
            'taxes'            => $taxes,
            'fiscal_positions' => $this->fiscalPositions($config),
            'price_lists'      => $this->priceLists($config),
            'prices'           => $this->prices($config, $session),
            'payment_methods'  => $this->paymentMethods($config),
            'bills'            => $this->bills($config),
            'notes'            => $this->notes(),
            'partners'         => $this->partners($config),
            'orders'           => $this->openOrders($session),
            'stock'            => $this->stock($config),
        ];
    }

    protected function company(Config $config): array
    {
        $company = $config->company;

        return [
            'id'                              => $company->id,
            'name'                            => $company->name,
            'logo'                            => $company->logo ? Storage::url($company->logo) : null,
            'phone'                           => $company->phone,
            'email'                           => $company->email,
            'website'                         => $company->website,
            'currency_id'                     => $company->currency_id,
            'tax_calculation_rounding_method' => (new TaxesSettings)->tax_calculation_rounding_method,
        ];
    }

    protected function config(Config $config): array
    {
        return [
            'id'                            => $config->id,
            'name'                          => $config->name,
            'code'                          => $config->code,
            'currency_id'                   => $config->currency_id,
            'company_id'                    => $config->company_id,
            'tax_display'                   => $config->tax_display?->value,
            'stock_update_mode'             => Session::defaultStockUpdateMode(),
            'price_list_id'                 => $config->price_list_id,
            'fiscal_position_id'            => $config->fiscal_position_id,
            'takeaway_fiscal_position_id'   => $config->takeaway_fiscal_position_id,
            'discount_product_id'           => $config->discount_product_id,
            'tip_product_id'                => $config->tip_product_id,
            'limited_products_amount'       => $config->limited_products_amount,
            'receipt_header'                => $config->receipt_header,
            'receipt_footer'                => $config->receipt_footer,
            'is_restaurant'                 => (bool) $config->is_restaurant,
            'enable_line_discount'          => (bool) $config->enable_line_discount,
            'enable_global_discount'        => (bool) $config->enable_global_discount,
            'enable_price_control'          => (bool) $config->enable_price_control,
            'enable_customer_required'      => (bool) $config->enable_customer_required,
            'enable_receipt_print'          => (bool) $config->enable_receipt_print,
            'enable_receipt_auto_print'     => (bool) $config->enable_receipt_auto_print,
            'enable_price_list'             => (bool) $config->enable_price_list,
            'enable_fiscal_position'        => (bool) $config->enable_fiscal_position,
            'enable_tip'                    => (bool) $config->enable_tip,
            'enable_ship_later'             => (bool) $config->enable_ship_later,
            'enable_cash_rounding'          => (bool) $config->enable_cash_rounding,
            'enable_only_round_cash_method' => (bool) $config->enable_only_round_cash_method,
            'enable_split_bill'             => (bool) $config->enable_split_bill,
            'enable_print_bill'             => (bool) $config->enable_print_bill,
            'enable_takeaway'               => (bool) $config->enable_takeaway,
            'show_product_images'           => (bool) $config->show_product_images,
            'show_category_images'          => (bool) $config->show_category_images,
            'cash_rounding'                 => $this->cashRounding($config),
            'can_edit_price'                => $this->canEditPrice($config),
            'product_endpoint'              => route('point-of-sale.till.configs.products.store', ['config' => $config->id]),
            'tracking_options'              => $this->trackingOptions(),
        ];
    }

    protected function trackingOptions(): array
    {
        return collect(ProductTracking::cases())
            ->map(fn (ProductTracking $tracking): array => [
                'value' => $tracking->value,
                'label' => $tracking->getLabel(),
            ])
            ->all();
    }

    protected function canEditPrice(Config $config): bool
    {
        if (! $config->enable_price_control) {
            return true;
        }

        return (bool) Auth::user()?->can('update', $config);
    }

    protected function cashRounding(Config $config): ?array
    {
        $rounding = $config->cashRounding;

        if (! $config->enable_cash_rounding || ! $rounding) {
            return null;
        }

        return [
            'id'              => $rounding->id,
            'name'            => $rounding->name,
            'rounding'        => (float) $rounding->rounding,
            'rounding_method' => $rounding->rounding_method,
            'strategy'        => $rounding->strategy,
        ];
    }

    protected function session(Session $session): array
    {
        return [
            'id'                          => $session->id,
            'name'                        => $session->name,
            'state'                       => $session->state?->value,
            'config_id'                   => $session->config_id,
            'user_id'                     => $session->user_id,
            'user_name'                   => $session->user?->name,
            'opened_at'                   => $session->started_at?->toIso8601String(),
            'cash_register_balance_start' => (float) $session->cash_balance_start,
            'sequence_number'             => (int) ($session->order_count ?? 0),
            'login_number'                => (int) ($session->login_number ?? 0),
        ];
    }

    protected function currencies(Config $config): array
    {
        return Currency::query()
            ->whereIn('id', array_filter([$config->currency_id, $config->company?->currency_id]))
            ->get()
            ->map(fn (Currency $currency): array => [
                'id'             => $currency->id,
                'name'           => $currency->name,
                'symbol'         => $currency->symbol,
                'rounding'       => (float) $currency->rounding,
                'decimal_places' => (int) $currency->decimal_places,
                'position'       => $currency->position,
            ])
            ->values()
            ->all();
    }

    protected function uoms(): array
    {
        return UOM::query()
            ->get()
            ->map(fn (UOM $uom): array => [
                'id'       => $uom->id,
                'name'     => $uom->name,
                'rounding' => (float) ($uom->rounding ?? 0.01),
            ])
            ->values()
            ->all();
    }

    protected function categories(Config $config): array
    {
        $query = Category::query()->orderBy('sort');

        if ($config->limit_categories && $config->categories->isNotEmpty()) {
            $query->whereIn('id', $config->categories->pluck('id'));
        }

        return $query->get()->map(fn (Category $category): array => [
            'id'        => $category->id,
            'name'      => $category->name,
            'color'     => $category->color,
            'parent_id' => $category->parent_id,
            'image'     => filled($category->image) ? Storage::url($category->image) : null,
        ])->values()->all();
    }

    protected function products(Config $config, ?Session $session = null): array
    {
        $products = $this->productQuery($config, $session)->get();

        $priceList = $this->prices->priceListForConfig($config);

        return $products
            ->map(fn (Product $product): array => array_merge(
                $this->productRow($product, $config),
                ['price' => $this->prices->resolve($product, $priceList)],
            ))
            ->values()
            ->all();
    }

    protected function productRow(Product $product, Config $config): array
    {
        return [
            'id'              => $product->id,
            'name'            => $product->name,
            'reference'       => $product->reference,
            'barcode'         => $product->barcode,
            'uom_id'          => $product->uom_id,
            'price'           => (float) ($product->price ?? 0),
            'cost'            => (float) ($product->cost ?? 0),
            'tax_ids'         => $this->taxIdsFor($product, $config),
            'category_ids'    => $product->posCategories?->pluck('id')->all() ?? [],
            'is_storable'     => (bool) $product->is_storable,
            'is_configurable' => (bool) $product->is_configurable,
            'image'           => $this->imageUrl($product),
        ];
    }

    protected function imageUrl(Product $product): ?string
    {
        $image = collect($product->images)->first();

        return $image ? Storage::url($image) : null;
    }

    protected function stock(Config $config): array
    {
        $locationIds = $this->stockLocationIds($config);

        if ($locationIds === []) {
            return [];
        }

        return ProductQuantity::withoutGlobalScopes()
            ->whereIn('location_id', $locationIds)
            ->selectRaw('product_id, SUM(quantity - reserved_quantity) AS free_qty')
            ->groupBy('product_id')
            ->pluck('free_qty', 'product_id')
            ->map(fn ($quantity): float => (float) $quantity)
            ->all();
    }

    protected function stockLocationIds(Config $config): array
    {
        $sourceId = $config->operationType?->source_location_id
            ?? $config->warehouse?->lot_stock_location_id;

        if (! $sourceId) {
            return [];
        }

        $parentPath = Location::withoutGlobalScopes()->whereKey($sourceId)->value('parent_path');

        if (! $parentPath) {
            return [(int) $sourceId];
        }

        return Location::withoutGlobalScopes()
            ->where('parent_path', 'like', $parentPath.'%')
            ->pluck('id')
            ->all();
    }

    public function productPayload(Config $config, int $productId): ?array
    {
        $product = Product::query()->with('posCategories')->find($productId);

        if (! $product) {
            return null;
        }

        return array_merge($this->productRow($product, $config), [
            'resolved_price' => $this->prices->resolve($product, $this->prices->priceListForConfig($config)),
        ]);
    }

    protected function productQuery(Config $config, ?Session $session)
    {
        $referenced = $session
            ? OrderLine::query()
                ->whereIn('order_id', Order::query()
                    ->where('session_id', $session->id)
                    ->where('state', 'draft')
                    ->select('id'))
                ->pluck('product_id')
                ->unique()
                ->filter()
                ->all()
            : [];

        return Product::query()
            ->with('posCategories')
            ->where(fn ($query) => $query
                ->where(fn ($catalogue) => $catalogue
                    ->where('available_in_pos', true)
                    ->whereNull('parent_id'))
                ->when($referenced, fn ($builder) => $builder->orWhereIn('id', $referenced)))
            ->orderBy('name')
            ->limit(($config->limited_products_amount ?: 500) + count($referenced));
    }

    protected function taxIdsFor(Product $product, Config $config): array
    {
        $accountProduct = AccountProduct::withoutGlobalScopes()->find($product->id);

        if (! $accountProduct) {
            return [];
        }

        return TaxModel::forProduct($accountProduct, TypeTaxUse::SALE, $config->company_id);
    }

    protected function collectTaxes(Config $config): array
    {
        $taxes = TaxModel::query()
            ->withoutGlobalScopes()
            ->where(fn ($query) => $query
                ->where('company_id', $config->company_id)
                ->orWhereNull('company_id'))
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return $taxes->map(fn (TaxModel $tax): array => [
            'id'                  => $tax->id,
            'name'                => $tax->name,
            'invoice_label'       => $tax->invoice_label,
            'sort'                => (int) $tax->sort,
            'amount'              => (float) $tax->amount,
            'amount_type'         => $tax->amount_type?->value,
            'formula'             => $tax->formula,
            'price_include'       => (bool) $tax->price_include,
            'include_base_amount' => (bool) $tax->include_base_amount,
            'is_base_affected'    => (bool) $tax->is_base_affected,
            'has_negative_factor' => (bool) $tax->has_negative_factor,
            'tax_group_id'        => $tax->tax_group_id,
            'children_tax_ids'    => $tax->amount_type === AmountType::GROUP
                ? $tax->childrenTaxes()->orderBy('sort')->orderBy('id')->pluck('accounts_taxes.id')->all()
                : [],
        ])->values()->all();
    }

    protected function fiscalPositions(Config $config): array
    {
        if (! $config->enable_fiscal_position) {
            return [];
        }

        $positions = $config->fiscalPositions;

        foreach (array_filter([$config->fiscal_position_id, $config->takeaway_fiscal_position_id]) as $id) {
            if (! $positions->contains('id', $id)) {
                $extra = FiscalPosition::find($id);

                if ($extra) {
                    $positions->push($extra);
                }
            }
        }

        $mappings = FiscalPositionTax::query()
            ->whereIn('fiscal_position_id', $positions->pluck('id'))
            ->get()
            ->groupBy('fiscal_position_id');

        return $positions->map(fn (FiscalPosition $position): array => [
            'id'       => $position->id,
            'name'     => $position->name,
            'tax_map'  => ($mappings[$position->id] ?? collect())
                ->map(fn (FiscalPositionTax $mapping): array => [
                    'source_id'      => $mapping->tax_source_id,
                    'destination_id' => $mapping->tax_destination_id,
                ])->values()->all(),
        ])->values()->all();
    }

    protected function priceLists(Config $config): array
    {
        if (! $config->enable_price_list) {
            return [];
        }

        $priceLists = $config->priceLists;

        if ($config->price_list_id && ! $priceLists->contains('id', $config->price_list_id)) {
            $extra = PriceList::find($config->price_list_id);

            if ($extra) {
                $priceLists->push($extra);
            }
        }

        $items = PriceRuleItem::query()
            ->whereIn('price_list_id', $priceLists->pluck('id'))
            ->orderBy('sort')
            ->get()
            ->groupBy('price_list_id');

        return $priceLists->map(fn (PriceList $priceList): array => [
            'id'          => $priceList->id,
            'name'        => $priceList->name,
            'currency_id' => $priceList->currency_id,
            'items'       => ($items[$priceList->id] ?? collect())
                ->map(fn (PriceRuleItem $item): array => [
                    'id'                 => $item->id,
                    'apply_to'           => $item->apply_to?->value ?? $item->apply_to,
                    'product_id'         => $item->product_id,
                    'category_id'        => $item->category_id,
                    'base'               => $item->base?->value ?? $item->base,
                    'type'               => $item->type?->value ?? $item->type,
                    'fixed_price'        => $item->fixed_price === null ? null : (float) $item->fixed_price,
                    'price_discount'     => $item->price_discount === null ? null : (float) $item->price_discount,
                    'price_round'        => $item->price_round === null ? null : (float) $item->price_round,
                    'price_surcharge'    => $item->price_surcharge === null ? null : (float) $item->price_surcharge,
                    'price_markup'       => $item->price_markup === null ? null : (float) $item->price_markup,
                    'price_min_margin'   => $item->price_min_margin === null ? null : (float) $item->price_min_margin,
                    'price_max_margin'   => $item->price_max_margin === null ? null : (float) $item->price_max_margin,
                    'min_quantity'       => $item->min_quantity === null ? null : (float) $item->min_quantity,
                    'starts_at'          => $item->starts_at?->toIso8601String(),
                    'ends_at'            => $item->ends_at?->toIso8601String(),
                    'sort'               => (int) $item->sort,
                ])->values()->all(),
        ])->values()->all();
    }

    protected function prices(Config $config, ?Session $session = null): array
    {
        $products = $this->productQuery($config, $session)->get();

        $matrix = ['0' => []];

        foreach ($products as $product) {
            $matrix['0'][(string) $product->id] = $this->prices->resolve($product, null);
        }

        if (! $config->enable_price_list) {
            return $matrix;
        }

        $priceLists = $config->priceLists;

        if ($config->price_list_id && ! $priceLists->contains('id', $config->price_list_id)) {
            $extra = PriceList::find($config->price_list_id);

            if ($extra) {
                $priceLists->push($extra);
            }
        }

        foreach ($priceLists as $priceList) {
            $matrix[(string) $priceList->id] = [];

            foreach ($products as $product) {
                $matrix[(string) $priceList->id][(string) $product->id] = $this->prices->resolve($product, $priceList);
            }
        }

        return $matrix;
    }

    protected function paymentMethods(Config $config): array
    {
        return $config->paymentMethods->map(fn (PaymentMethod $method): array => [
            'id'                 => $method->id,
            'name'               => $method->name,
            'type'               => $method->type?->value ?? $method->type,
            'is_cash_count'      => (bool) $method->is_cash_count,
            'split_transactions' => (bool) $method->split_transactions,
            'sort'               => (int) $method->sort,
        ])->values()->all();
    }

    protected function bills(Config $config): array
    {
        return Bill::query()
            ->where(fn ($query) => $query
                ->where('is_for_all_configs', true)
                ->orWhereHas('configs', fn ($configs) => $configs->whereKey($config->id)))
            ->orderBy('value')
            ->get()
            ->map(fn (Bill $bill): array => [
                'id'    => $bill->id,
                'name'  => $bill->name,
                'value' => (float) $bill->value,
            ])->values()->all();
    }

    protected function notes(): array
    {
        return Note::query()
            ->orderBy('sort')
            ->get()
            ->map(fn (Note $note): array => [
                'id'    => $note->id,
                'name'  => $note->name,
                'color' => $note->color,
            ])->values()->all();
    }

    protected function partners(Config $config): array
    {
        return Partner::query()
            ->orderBy('name')
            ->limit(static::PARTNER_LIMIT)
            ->get()
            ->map(fn (Partner $partner): array => [
                'id'      => $partner->id,
                'name'    => $partner->name,
                'email'   => $partner->email,
                'phone'   => $partner->phone,
                'mobile'  => $partner->mobile,
                'street1' => $partner->street1,
                'street2' => $partner->street2,
                'city'    => $partner->city,
                'zip'     => $partner->zip,
                'barcode' => $partner->barcode ?? null,
            ])->values()->all();
    }

    protected function openOrders(Session $session): array
    {
        return Order::query()
            ->with(['lines', 'payments'])
            ->where('session_id', $session->id)
            ->where('state', 'draft')
            ->get()
            ->map(fn (Order $order): array => [
                'id'            => $order->id,
                'uuid'          => $order->uuid,
                'pos_reference' => $order->reference,
                'partner_id'    => $order->partner_id,
                'state'         => $order->state?->value,
                'note'          => $order->note,
                'is_takeaway'   => (bool) $order->is_takeaway,
                'to_invoice'    => (bool) $order->is_to_invoice,
                'lines'         => $order->lines->map(fn ($line): array => [
                    'uuid'       => $line->uuid,
                    'product_id' => $line->product_id,
                    'qty'        => (float) $line->qty,
                    'price_unit' => (float) $line->price_unit,
                    'discount'   => (float) $line->discount,
                    'note'       => $line->note,
                    'tax_ids'    => $line->taxes->pluck('id')->all(),
                ])->values()->all(),
                'payments'      => $order->payments->map(fn ($payment): array => [
                    'uuid'              => $payment->uuid,
                    'payment_method_id' => $payment->payment_method_id,
                    'amount'            => (float) $payment->amount,
                    'is_change'         => (bool) $payment->is_change,
                ])->values()->all(),
            ])->values()->all();
    }
}
