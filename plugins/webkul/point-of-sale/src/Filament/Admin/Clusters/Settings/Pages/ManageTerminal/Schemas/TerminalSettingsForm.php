<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Settings\Pages\ManageTerminal\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Enums\JournalType;
use Webkul\Account\Models\Account;
use Webkul\Account\Models\CashRounding;
use Webkul\Account\Models\FiscalPosition;
use Webkul\Account\Models\Journal;
use Webkul\Inventory\Enums\OperationType as OperationTypeEnum;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Route;
use Webkul\Inventory\Models\Warehouse;
use Webkul\PointOfSale\Enums\InvoicePaymentMode;
use Webkul\PointOfSale\Enums\PickingPolicy;
use Webkul\PointOfSale\Enums\StockUpdateMode;
use Webkul\PointOfSale\Enums\TaxDisplay;
use Webkul\PointOfSale\Models\Bill;
use Webkul\PointOfSale\Models\Category;
use Webkul\PointOfSale\Models\Config;
use Webkul\PointOfSale\Models\Floor;
use Webkul\PointOfSale\Models\PaymentMethod;
use Webkul\PointOfSale\Models\Printer;
use Webkul\Product\Models\PriceList;
use Webkul\Product\Models\Product;

class TerminalSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('config_id')
                            ->label(static::label('terminal'))
                            ->helperText(static::label('terminal-helper-text'))
                            ->options(fn (): array => Config::query()->orderBy('sort')->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, $livewire) => $livewire->loadConfig($state ? (int) $state : null)),

                        Toggle::make('is_active')
                            ->label(static::label('is-active'))
                            ->visible(fn (Get $get): bool => filled($get('config_id'))),

                        Hidden::make('company_id'),
                    ])
                    ->columns(1),

                Text::make(static::label('empty'))
                    ->visible(fn (Get $get): bool => blank($get('config_id'))),

                Section::make(static::label('sections.restaurant.title'))
                    ->schema([
                        Toggle::make('is_restaurant')
                            ->label(static::label('sections.restaurant.fields.is-restaurant'))
                            ->live(),

                        Toggle::make('enable_split_bill')
                            ->label(static::label('sections.restaurant.fields.enable-split-bill'))
                            ->visible(fn (Get $get): bool => (bool) $get('is_restaurant')),

                        Toggle::make('enable_print_bill')
                            ->label(static::label('sections.restaurant.fields.enable-print-bill'))
                            ->visible(fn (Get $get): bool => (bool) $get('is_restaurant')),

                        Toggle::make('enable_takeaway')
                            ->label(static::label('sections.restaurant.fields.enable-takeaway'))
                            ->live(),

                        Select::make('takeaway_fiscal_position_id')
                            ->label(static::label('sections.restaurant.fields.takeaway-fiscal-position'))
                            ->options(fn (Get $get): array => static::scoped(FiscalPosition::query(), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_takeaway')),

                        Select::make('floors')
                            ->label(static::label('sections.restaurant.fields.floors'))
                            ->options(fn (Get $get): array => static::scoped(Floor::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('is_restaurant'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.payment.title'))
                    ->schema([
                        Select::make('paymentMethods')
                            ->label(static::label('sections.payment.fields.payment-methods'))
                            ->options(fn (Get $get): array => static::scoped(PaymentMethod::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->columnSpanFull(),

                        Toggle::make('enable_cash_control')
                            ->label(static::label('sections.payment.fields.enable-cash-control')),

                        Toggle::make('enable_maximum_difference')
                            ->label(static::label('sections.payment.fields.enable-maximum-difference'))
                            ->live(),

                        TextInput::make('amount_authorized_diff')
                            ->label(static::label('sections.payment.fields.amount-authorized-diff'))
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_maximum_difference')),

                        Toggle::make('enable_cash_rounding')
                            ->label(static::label('sections.payment.fields.enable-cash-rounding'))
                            ->live(),

                        Select::make('cash_rounding_id')
                            ->label(static::label('sections.payment.fields.cash-rounding'))
                            ->options(fn (): array => CashRounding::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_cash_rounding')),

                        Toggle::make('enable_only_round_cash_method')
                            ->label(static::label('sections.payment.fields.enable-only-round-cash-method'))
                            ->visible(fn (Get $get): bool => (bool) $get('enable_cash_rounding')),

                        Toggle::make('enable_tip')
                            ->label(static::label('sections.payment.fields.enable-tip'))
                            ->live(),

                        Select::make('tip_product_id')
                            ->label(static::label('sections.payment.fields.tip-product'))
                            ->options(fn (): array => Product::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_tip')),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.interface.title'))
                    ->schema([
                        Toggle::make('enable_customer_required')
                            ->label(static::label('sections.interface.fields.enable-customer-required')),

                        Toggle::make('show_product_images')
                            ->label(static::label('sections.interface.fields.show-product-images')),

                        Toggle::make('show_category_images')
                            ->label(static::label('sections.interface.fields.show-category-images')),

                        TextInput::make('limited_products_amount')
                            ->label(static::label('sections.interface.fields.limited-products-amount'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.products.title'))
                    ->schema([
                        Toggle::make('limit_categories')
                            ->label(static::label('sections.products.fields.limit-categories'))
                            ->live(),

                        Select::make('categories')
                            ->label(static::label('sections.products.fields.categories'))
                            ->options(fn (Get $get): array => static::scoped(Category::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('limit_categories')),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.accounting.title'))
                    ->schema([
                        Select::make('journal_id')
                            ->label(static::label('sections.accounting.fields.journal'))
                            ->options(fn (Get $get): array => static::scoped(Journal::query()->where('type', JournalType::SALE), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),

                        Select::make('invoice_journal_id')
                            ->label(static::label('sections.accounting.fields.invoice-journal'))
                            ->options(fn (Get $get): array => static::scoped(Journal::query()->where('type', JournalType::SALE), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),

                        Select::make('invoice_payment_mode')
                            ->label(static::label('sections.accounting.fields.invoice-payment-mode'))
                            ->options(InvoicePaymentMode::class)
                            ->native(false)
                            ->required(),

                        Select::make('receivable_account_id')
                            ->label(static::label('sections.accounting.fields.receivable-account'))
                            ->options(fn (): array => Account::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),

                        Select::make('cash_movement_account_id')
                            ->label(static::label('sections.accounting.fields.cash-movement-account'))
                            ->options(fn (): array => Account::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),

                        Select::make('balancing_account_id')
                            ->label(static::label('sections.accounting.fields.balancing-account'))
                            ->options(fn (): array => Account::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),

                        Toggle::make('enable_cogs')
                            ->label(static::label('sections.accounting.fields.enable-cogs'))
                            ->live(),

                        Select::make('cogs_journal_id')
                            ->label(static::label('sections.accounting.fields.cogs-journal'))
                            ->options(fn (Get $get): array => static::scoped(Journal::query(), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_cogs')),

                        Select::make('stock_output_account_id')
                            ->label(static::label('sections.accounting.fields.stock-output-account'))
                            ->options(fn (): array => Account::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_cogs')),

                        Toggle::make('is_closing_entry_by_product')
                            ->label(static::label('sections.accounting.fields.is-closing-entry-by-product')),

                        Toggle::make('enable_fiscal_position')
                            ->label(static::label('sections.accounting.fields.enable-fiscal-position'))
                            ->live(),

                        Select::make('fiscal_position_id')
                            ->label(static::label('sections.accounting.fields.fiscal-position'))
                            ->options(fn (Get $get): array => static::scoped(FiscalPosition::query(), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_fiscal_position')),

                        Select::make('fiscalPositions')
                            ->label(static::label('sections.accounting.fields.fiscal-positions'))
                            ->options(fn (Get $get): array => static::scoped(FiscalPosition::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_fiscal_position')),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.pricing.title'))
                    ->schema([
                        Select::make('tax_display')
                            ->label(static::label('sections.pricing.fields.tax-display'))
                            ->options(TaxDisplay::class)
                            ->native(false)
                            ->required(),

                        Toggle::make('enable_price_list')
                            ->label(static::label('sections.pricing.fields.enable-price-list'))
                            ->live(),

                        Select::make('price_list_id')
                            ->label(static::label('sections.pricing.fields.price-list'))
                            ->options(fn (Get $get): array => static::scoped(PriceList::query(), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_price_list')),

                        Select::make('priceLists')
                            ->label(static::label('sections.pricing.fields.price-lists'))
                            ->options(fn (Get $get): array => static::scoped(PriceList::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_price_list')),

                        Toggle::make('enable_price_control')
                            ->label(static::label('sections.pricing.fields.enable-price-control')),

                        Toggle::make('enable_line_discount')
                            ->label(static::label('sections.pricing.fields.enable-line-discount')),

                        Toggle::make('enable_global_discount')
                            ->label(static::label('sections.pricing.fields.enable-global-discount'))
                            ->live(),

                        Select::make('discount_product_id')
                            ->label(static::label('sections.pricing.fields.discount-product'))
                            ->options(fn (): array => Product::query()->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_global_discount')),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.receipt.title'))
                    ->schema([
                        Toggle::make('enable_receipt_print')
                            ->label(static::label('sections.receipt.fields.enable-receipt-print')),

                        Toggle::make('enable_receipt_auto_print')
                            ->label(static::label('sections.receipt.fields.enable-receipt-auto-print')),

                        Textarea::make('receipt_header')
                            ->label(static::label('sections.receipt.fields.receipt-header'))
                            ->rows(2),

                        Textarea::make('receipt_footer')
                            ->label(static::label('sections.receipt.fields.receipt-footer'))
                            ->rows(2),

                        Select::make('bills')
                            ->label(static::label('sections.receipt.fields.bills'))
                            ->options(fn (Get $get): array => static::scoped(Bill::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.preparation.title'))
                    ->schema([
                        Select::make('printers')
                            ->label(static::label('sections.preparation.fields.printers'))
                            ->options(fn (Get $get): array => static::scoped(Printer::query(), $get)->pluck('name', 'id')->all())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),

                Section::make(static::label('sections.inventory.title'))
                    ->schema([
                        Select::make('warehouse_id')
                            ->label(static::label('sections.inventory.fields.warehouse'))
                            ->options(fn (Get $get): array => static::scoped(Warehouse::query(), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->required(),

                        Select::make('operation_type_id')
                            ->label(static::label('sections.inventory.fields.operation-type'))
                            ->options(fn (Get $get): array => static::scoped(OperationType::query()->where('type', OperationTypeEnum::OUTGOING), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->required(),

                        Select::make('return_operation_type_id')
                            ->label(static::label('sections.inventory.fields.return-operation-type'))
                            ->options(fn (Get $get): array => static::scoped(OperationType::query()->where('type', OperationTypeEnum::INCOMING), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false),

                        Select::make('stock_update_mode')
                            ->label(static::label('sections.inventory.fields.stock-update-mode'))
                            ->options(StockUpdateMode::class)
                            ->native(false)
                            ->required(),

                        Toggle::make('enable_ship_later')
                            ->label(static::label('sections.inventory.fields.enable-ship-later'))
                            ->live(),

                        Select::make('ship_later_route_id')
                            ->label(static::label('sections.inventory.fields.ship-later-route'))
                            ->options(fn (Get $get): array => static::scoped(Route::query(), $get)->pluck('name', 'id')->all())
                            ->searchable()
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_ship_later')),

                        Select::make('picking_policy')
                            ->label(static::label('sections.inventory.fields.picking-policy'))
                            ->options(PickingPolicy::class)
                            ->native(false)
                            ->visible(fn (Get $get): bool => (bool) $get('enable_ship_later')),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get): bool => filled($get('config_id'))),
            ])
            ->columns(1)
            ->statePath('data');
    }

    protected static function scoped(Builder $query, Get $get): Builder
    {
        return $query->where(owned_by_company($get('company_id')));
    }

    protected static function label(string $key): string
    {
        return __("point-of-sale::filament/admin/clusters/settings/pages/manage-terminal.form.{$key}");
    }
}
