<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Enums\AccountType;
use Webkul\Account\Enums\JournalType;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\CashRounding;
use Webkul\Account\Models\FiscalPosition;
use Webkul\Account\Models\Journal;
use Webkul\Inventory\Enums\OperationType as OperationTypeEnum;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Route;
use Webkul\Inventory\Models\Warehouse;
use Webkul\PointOfSale\Enums\PickingPolicy;
use Webkul\PointOfSale\Enums\TaxDisplay;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\PriceList;
use Webkul\Product\Models\Product;

class ConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(static::label('sections.general.title'))
                    ->schema([
                        TextInput::make('name')
                            ->label(static::label('sections.general.fields.name'))
                            ->placeholder(static::label('sections.general.fields.name-placeholder'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('code')
                            ->label(static::label('sections.general.fields.code'))
                            ->helperText(static::label('sections.general.fields.code-helper-text'))
                            ->required()
                            ->maxLength(16),

                        Toggle::make('is_active')
                            ->label(static::label('sections.general.fields.is-active'))
                            ->default(true)
                            ->columnSpanFull(),

                        Hidden::make('company_id')
                            ->default(fn (): ?int => current_company_id()),
                    ])
                    ->columns(2),

                Section::make(static::label('sections.configurations.title'))
                    ->schema([
                        Tabs::make()
                            ->tabs([
                                static::restaurantTab(),
                                static::paymentTab(),
                                static::interfaceTab(),
                                static::productsTab(),
                                static::accountingTab(),
                                static::pricingTab(),
                                static::receiptsTab(),
                                static::preparationTab(),
                                static::inventoryTab(),
                            ])
                            ->vertical()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->columns(1);
    }

    protected static function restaurantTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.restaurant.title'))
            ->icon('heroicon-o-building-storefront')
            ->schema([
                Toggle::make('is_restaurant')
                    ->label(static::label('sections.configurations.tabs.restaurant.fields.is-restaurant'))
                    ->helperText(static::label('sections.configurations.tabs.restaurant.fields.is-restaurant-helper-text'))
                    ->live()
                    ->columnSpanFull(),

                Toggle::make('enable_split_bill')
                    ->label(static::label('sections.configurations.tabs.restaurant.fields.enable-split-bill'))
                    ->visible(fn (Get $get): bool => (bool) $get('is_restaurant')),

                Toggle::make('enable_print_bill')
                    ->label(static::label('sections.configurations.tabs.restaurant.fields.enable-print-bill'))
                    ->visible(fn (Get $get): bool => (bool) $get('is_restaurant')),

                Toggle::make('enable_takeaway')
                    ->label(static::label('sections.configurations.tabs.restaurant.fields.enable-takeaway'))
                    ->live(),

                Select::make('takeaway_fiscal_position_id')
                    ->label(static::label('sections.configurations.tabs.restaurant.fields.takeaway-fiscal-position'))
                    ->options(fn (Get $get): array => static::scoped(FiscalPosition::query(), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_takeaway')),

                Select::make('floors')
                    ->label(static::label('sections.configurations.tabs.restaurant.fields.floors'))
                    ->relationship(
                        'floors',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::scoped($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('is_restaurant'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function paymentTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.payment.title'))
            ->icon('heroicon-o-banknotes')
            ->schema([
                Select::make('paymentMethods')
                    ->label(static::label('sections.configurations.tabs.payment.fields.payment-methods'))
                    ->relationship(
                        'paymentMethods',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::scoped($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->columnSpanFull(),

                Toggle::make('enable_cash_control')
                    ->label(static::label('sections.configurations.tabs.payment.fields.enable-cash-control'))
                    ->helperText(static::label('sections.configurations.tabs.payment.fields.enable-cash-control-helper-text')),

                Toggle::make('enable_maximum_difference')
                    ->label(static::label('sections.configurations.tabs.payment.fields.enable-maximum-difference'))
                    ->helperText(static::label('sections.configurations.tabs.payment.fields.enable-maximum-difference-helper-text'))
                    ->live(),

                TextInput::make('amount_authorized_diff')
                    ->label(static::label('sections.configurations.tabs.payment.fields.amount-authorized-diff'))
                    ->numeric()
                    ->minValue(0)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_maximum_difference')),

                Toggle::make('enable_cash_rounding')
                    ->label(static::label('sections.configurations.tabs.payment.fields.enable-cash-rounding'))
                    ->helperText(static::label('sections.configurations.tabs.payment.fields.enable-cash-rounding-helper-text'))
                    ->live(),

                Select::make('cash_rounding_id')
                    ->label(static::label('sections.configurations.tabs.payment.fields.cash-rounding'))
                    ->options(fn (): array => CashRounding::query()->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_cash_rounding')),

                Toggle::make('enable_only_round_cash_method')
                    ->label(static::label('sections.configurations.tabs.payment.fields.enable-only-round-cash-method'))
                    ->visible(fn (Get $get): bool => (bool) $get('enable_cash_rounding')),

                Toggle::make('enable_tip')
                    ->label(static::label('sections.configurations.tabs.payment.fields.enable-tip'))
                    ->helperText(static::label('sections.configurations.tabs.payment.fields.enable-tip-helper-text'))
                    ->live(),

                Select::make('tip_product_id')
                    ->label(static::label('sections.configurations.tabs.payment.fields.tip-product'))
                    ->options(fn (Get $get): array => static::serviceProducts($get))
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_tip')),
            ])
            ->columns(2);
    }

    protected static function interfaceTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.interface.title'))
            ->icon('heroicon-o-computer-desktop')
            ->schema([
                Toggle::make('enable_customer_required')
                    ->label(static::label('sections.configurations.tabs.interface.fields.enable-customer-required')),

                Toggle::make('show_product_images')
                    ->label(static::label('sections.configurations.tabs.interface.fields.show-product-images')),

                Toggle::make('show_category_images')
                    ->label(static::label('sections.configurations.tabs.interface.fields.show-category-images')),

                TextInput::make('limited_products_amount')
                    ->label(static::label('sections.configurations.tabs.interface.fields.limited-products-amount'))
                    ->helperText(static::label('sections.configurations.tabs.interface.fields.limited-products-amount-helper-text'))
                    ->numeric()
                    ->minValue(1)
                    ->default(500)
                    ->required(),
            ])
            ->columns(2);
    }

    protected static function productsTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.products.title'))
            ->icon('heroicon-o-squares-2x2')
            ->schema([
                Toggle::make('limit_categories')
                    ->label(static::label('sections.configurations.tabs.products.fields.limit-categories'))
                    ->helperText(static::label('sections.configurations.tabs.products.fields.limit-categories-helper-text'))
                    ->live()
                    ->columnSpanFull(),

                Select::make('categories')
                    ->label(static::label('sections.configurations.tabs.products.fields.categories'))
                    ->relationship(
                        'categories',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::scoped($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('limit_categories'))
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function accountingTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.accounting.title'))
            ->icon('heroicon-o-calculator')
            ->schema([
                Select::make('journal_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.journal'))
                    ->helperText(static::label('sections.configurations.tabs.accounting.fields.journal-helper-text'))
                    ->options(fn (Get $get): array => static::scoped(Journal::query()->whereIn('type', [JournalType::GENERAL, JournalType::SALE]), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false),

                Select::make('invoice_journal_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.invoice-journal'))
                    ->options(fn (Get $get): array => static::scoped(Journal::query()->where('type', JournalType::SALE), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false),

                Toggle::make('is_closing_entry_by_product')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.is-closing-entry-by-product'))
                    ->helperText(static::label('sections.configurations.tabs.accounting.fields.is-closing-entry-by-product-helper-text')),

                Toggle::make('enable_fiscal_position')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.enable-fiscal-position'))
                    ->helperText(static::label('sections.configurations.tabs.accounting.fields.enable-fiscal-position-helper-text'))
                    ->live()
                    ->columnSpanFull(),

                Select::make('fiscal_position_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.fiscal-position'))
                    ->options(fn (Get $get): array => static::scoped(FiscalPosition::query(), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_fiscal_position')),

                Select::make('fiscalPositions')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.fiscal-positions'))
                    ->relationship(
                        'fiscalPositions',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::scoped($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_fiscal_position')),

                Select::make('receivable_account_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.receivable-account'))
                    ->helperText(static::label('sections.configurations.tabs.accounting.fields.receivable-account-helper-text'))
                    ->options(fn (Get $get): array => static::accounts($get)
                        ->where('account_type', AccountType::ASSET_RECEIVABLE)
                        ->where('reconcile', true)
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->native(false),

                Select::make('cash_movement_account_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.cash-movement-account'))
                    ->options(fn (Get $get): array => static::accounts($get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false),

                Select::make('balancing_account_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.balancing-account'))
                    ->options(fn (Get $get): array => static::accounts($get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false),

                Toggle::make('enable_cogs')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.enable-cogs'))
                    ->helperText(static::label('sections.configurations.tabs.accounting.fields.enable-cogs-helper-text'))
                    ->live()
                    ->columnSpanFull(),

                Select::make('cogs_journal_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.cogs-journal'))
                    ->options(fn (Get $get): array => static::scoped(Journal::query(), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_cogs')),

                Select::make('stock_output_account_id')
                    ->label(static::label('sections.configurations.tabs.accounting.fields.stock-output-account'))
                    ->options(fn (Get $get): array => static::accounts($get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_cogs')),
            ])
            ->columns(2);
    }

    protected static function pricingTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.pricing.title'))
            ->icon('heroicon-o-currency-dollar')
            ->schema([
                Select::make('tax_display')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.tax-display'))
                    ->helperText(static::label('sections.configurations.tabs.pricing.fields.tax-display-helper-text'))
                    ->options(TaxDisplay::class)
                    ->native(false)
                    ->default(TaxDisplay::SUBTOTAL)
                    ->required(),

                Toggle::make('enable_price_control')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.enable-price-control'))
                    ->helperText(static::label('sections.configurations.tabs.pricing.fields.enable-price-control-helper-text')),

                Toggle::make('enable_price_list')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.enable-price-list'))
                    ->helperText(static::label('sections.configurations.tabs.pricing.fields.enable-price-list-helper-text'))
                    ->live()
                    ->columnSpanFull(),

                Select::make('priceLists')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.price-lists'))
                    ->relationship(
                        'priceLists',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::priceListsInCurrency($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->visible(fn (Get $get): bool => (bool) $get('enable_price_list')),

                Select::make('price_list_id')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.price-list'))
                    ->helperText(static::label('sections.configurations.tabs.pricing.fields.price-list-helper-text'))
                    ->options(fn (Get $get): array => static::availablePriceLists($get))
                    ->searchable()
                    ->native(false),

                Toggle::make('enable_line_discount')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.enable-line-discount'))
                    ->helperText(static::label('sections.configurations.tabs.pricing.fields.enable-line-discount-helper-text')),

                Toggle::make('enable_global_discount')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.enable-global-discount'))
                    ->helperText(static::label('sections.configurations.tabs.pricing.fields.enable-global-discount-helper-text'))
                    ->live(),

                Select::make('discount_product_id')
                    ->label(static::label('sections.configurations.tabs.pricing.fields.discount-product'))
                    ->options(fn (Get $get): array => static::serviceProducts($get))
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_global_discount')),
            ])
            ->columns(2);
    }

    protected static function receiptsTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.receipts.title'))
            ->icon('heroicon-o-receipt-percent')
            ->schema([
                Toggle::make('enable_receipt_print')
                    ->label(static::label('sections.configurations.tabs.receipts.fields.enable-receipt-print')),

                Toggle::make('enable_receipt_auto_print')
                    ->label(static::label('sections.configurations.tabs.receipts.fields.enable-receipt-auto-print'))
                    ->helperText(static::label('sections.configurations.tabs.receipts.fields.enable-receipt-auto-print-helper-text')),

                Textarea::make('receipt_header')
                    ->label(static::label('sections.configurations.tabs.receipts.fields.receipt-header'))
                    ->rows(2),

                Textarea::make('receipt_footer')
                    ->label(static::label('sections.configurations.tabs.receipts.fields.receipt-footer'))
                    ->rows(2),

                Select::make('bills')
                    ->label(static::label('sections.configurations.tabs.receipts.fields.bills'))
                    ->helperText(static::label('sections.configurations.tabs.receipts.fields.bills-helper-text'))
                    ->relationship(
                        'bills',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::scoped($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected static function preparationTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.preparation.title'))
            ->icon('heroicon-o-printer')
            ->schema([
                Select::make('printers')
                    ->label(static::label('sections.configurations.tabs.preparation.fields.printers'))
                    ->helperText(static::label('sections.configurations.tabs.preparation.fields.printers-helper-text'))
                    ->relationship(
                        'printers',
                        'name',
                        fn (Builder $query, Get $get): Builder => static::scoped($query, $get),
                    )
                    ->multiple()
                    ->searchable()
                    ->native(false)
                    ->columnSpanFull(),
            ])
            ->columns(1);
    }

    protected static function inventoryTab(): Tab
    {
        return Tab::make(static::label('sections.configurations.tabs.inventory.title'))
            ->icon('heroicon-o-cube')
            ->schema([
                Select::make('warehouse_id')
                    ->label(static::label('sections.configurations.tabs.inventory.fields.warehouse'))
                    ->options(fn (Get $get): array => static::scoped(Warehouse::query(), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->required(fn (string $operation): bool => $operation !== 'create'),

                Select::make('operation_type_id')
                    ->label(static::label('sections.configurations.tabs.inventory.fields.operation-type'))
                    ->helperText(static::label('sections.configurations.tabs.inventory.fields.operation-type-helper-text'))
                    ->options(fn (Get $get): array => static::scoped(OperationType::query()->where('type', OperationTypeEnum::OUTGOING), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->required(fn (string $operation): bool => $operation !== 'create'),

                Select::make('return_operation_type_id')
                    ->label(static::label('sections.configurations.tabs.inventory.fields.return-operation-type'))
                    ->options(fn (Get $get): array => static::scoped(OperationType::query()->where('type', OperationTypeEnum::INCOMING), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false),

                Toggle::make('enable_ship_later')
                    ->label(static::label('sections.configurations.tabs.inventory.fields.enable-ship-later'))
                    ->helperText(static::label('sections.configurations.tabs.inventory.fields.enable-ship-later-helper-text'))
                    ->live(),

                Select::make('ship_later_route_id')
                    ->label(static::label('sections.configurations.tabs.inventory.fields.ship-later-route'))
                    ->options(fn (Get $get): array => static::scoped(Route::query(), $get)->pluck('name', 'id')->all())
                    ->searchable()
                    ->native(false)
                    ->visible(fn (Get $get): bool => (bool) $get('enable_ship_later')),

                Select::make('picking_policy')
                    ->label(static::label('sections.configurations.tabs.inventory.fields.picking-policy'))
                    ->options(PickingPolicy::class)
                    ->native(false)
                    ->default(PickingPolicy::DIRECT)
                    ->required()
                    ->visible(fn (Get $get): bool => (bool) $get('enable_ship_later')),
            ])
            ->columns(2);
    }

    protected static function accounts(Get $get): Builder
    {
        return static::scoped(Account::query(), $get)->where(fn (Builder $query) => $query
            ->where('deprecated', false)
            ->orWhereNull('deprecated'));
    }

    /**
     * @return array<int, string>
     */
    protected static function serviceProducts(Get $get): array
    {
        return static::scoped(Product::query(), $get)
            ->where('type', ProductType::SERVICE)
            ->pluck('name', 'id')
            ->all();
    }

    protected static function priceListsInCurrency(Builder $query, Get $get): Builder
    {
        $currencyId = $get('currency_id');

        return static::scoped($query, $get)
            ->when($currencyId, fn (Builder $lists): Builder => $lists->where('currency_id', $currencyId));
    }

    /**
     * @return array<int, string>
     */
    protected static function availablePriceLists(Get $get): array
    {
        if (! $get('enable_price_list')) {
            return static::scoped(PriceList::query(), $get)->pluck('name', 'id')->all();
        }

        $available = array_filter((array) $get('priceLists'));

        return static::priceListsInCurrency(PriceList::query(), $get)
            ->whereIn('id', $available ?: [0])
            ->pluck('name', 'id')
            ->all();
    }

    protected static function scoped(Builder $query, Get $get): Builder
    {
        return $query->where(owned_by_company($get('company_id')));
    }

    protected static function label(string $key): string
    {
        return __("point-of-sale::filament/admin/clusters/configurations/resources/config.form.{$key}");
    }
}
