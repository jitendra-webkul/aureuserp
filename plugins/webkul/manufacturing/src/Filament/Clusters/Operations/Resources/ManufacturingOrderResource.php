<?php

namespace Webkul\Manufacturing\Filament\Clusters\Operations\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Field\Filament\Forms\Components\ProgressStepper as FormProgressStepper;
use Webkul\Field\Filament\Infolists\Components\ProgressStepper as InfolistProgressStepper;
use Webkul\Inventory\Models\OperationType;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Filament\Clusters\Operations;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages\CreateManufacturingOrder;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages\EditManufacturingOrder;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages\ListManufacturingOrders;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages\OverviewManufacturingOrder;
use Webkul\Manufacturing\Filament\Clusters\Operations\Resources\ManufacturingOrderResource\Pages\ViewManufacturingOrder;
use Webkul\Manufacturing\Models\BillOfMaterial;
use Webkul\Manufacturing\Models\BillOfMaterialLine;
use Webkul\Manufacturing\Models\Operation;
use Webkul\Manufacturing\Models\Order;
use Webkul\Manufacturing\Models\Product;
use Webkul\Manufacturing\Models\WorkCenter;
use Webkul\Product\Enums\ProductType;
use Webkul\Support\Filament\Forms\Components\Repeater;
use Webkul\Support\Filament\Forms\Components\Repeater\TableColumn as RepeaterTableColumn;
use Webkul\Support\Models\UOM;

class ManufacturingOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $cluster = Operations::class;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function getModelLabel(): string
    {
        return __('manufacturing::models/order.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('manufacturing::filament/clusters/operations/resources/manufacturing-order.navigation.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('manufacturing::filament/clusters/operations/resources/manufacturing-order.navigation.group');
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(ManufacturingOrderState::options())
                    ->options(function ($record): array {
                        $options = ManufacturingOrderState::options();

                        unset(
                            $options[ManufacturingOrderState::PROGRESS->value],
                            $options[ManufacturingOrderState::TO_CLOSE->value],
                            $options[ManufacturingOrderState::CANCEL->value],
                        );

                        if ($record?->state === ManufacturingOrderState::CANCEL) {
                            $options[ManufacturingOrderState::CANCEL->value] = ManufacturingOrderState::CANCEL->getLabel();
                        }

                        return $options;
                    })
                    ->default(ManufacturingOrderState::DRAFT)
                    ->disabled()
                    ->dehydrated(),

                Section::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.title'))
                    ->columns(2)
                    ->schema([
                        Group::make()
                            ->columns(1)
                            ->schema([
                                Select::make('product_id')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.fields.product'))
                                    ->relationship(
                                        'product',
                                        'name',
                                        fn (Builder $query) => $query
                                            ->withTrashed()
                                            ->where('type', ProductType::GOODS)
                                            ->whereNull('is_configurable')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->name)
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->wrapOptionLabels(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                        $product = Product::query()->withTrashed()->find($state);

                                        if (! $product) {
                                            $set('uom_id', null);
                                            $set('bill_of_material_id', null);
                                            $set('rawMaterialMoves', []);
                                            $set('workOrders', []);

                                            return;
                                        }

                                        $set('uom_id', $product->uom_id ?: static::getDefaultUomId());

                                        $set('company_id', $product->company_id ?? Auth::user()?->default_company_id);

                                        $billOfMaterialId = static::getDefaultBillOfMaterialId($product);

                                        if (! $billOfMaterialId) {
                                            $set('bill_of_material_id', null);
                                            $set('rawMaterialMoves', []);
                                            $set('workOrders', []);

                                            return;
                                        }

                                        if ($get('bill_of_material_id') !== $billOfMaterialId) {
                                            $set('bill_of_material_id', $billOfMaterialId, shouldCallUpdatedHooks: true);

                                            return;
                                        }

                                        static::applyBillOfMaterialDefaults(
                                            $set,
                                            BillOfMaterial::query()->withTrashed()->find($billOfMaterialId),
                                            $product,
                                            (float) ($get('quantity') ?: 1),
                                        );
                                    })
                                    ->required(),
                                static::getQuantityUomField(),
                                Select::make('bill_of_material_id')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.fields.bill-of-material'))
                                    ->relationship(
                                        'billOfMaterial',
                                        'code',
                                        modifyQueryUsing: function (Get $get, Builder $query): void {
                                            $product = Product::query()->withTrashed()->find($get('product_id'));

                                            if (! $product) {
                                                $query->whereRaw('1 = 0');

                                                return;
                                            }

                                            $productIds = array_filter([$product->id, $product->parent_id]);

                                            $query->withTrashed()->whereIn('product_id', $productIds);
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (BillOfMaterial $record): string => static::getBillOfMaterialLabel($record))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->wrapOptionLabels(false)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                        $product = Product::query()->withTrashed()->find($get('product_id'));

                                        static::applyBillOfMaterialDefaults(
                                            $set,
                                            BillOfMaterial::query()->withTrashed()->find($state),
                                            $product,
                                            (float) ($get('quantity') ?: 1),
                                        );
                                    }),
                            ]),
                        Group::make()
                            ->columns(1)
                            ->schema([
                                DateTimePicker::make('deadline_at')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.fields.scheduled-date'))
                                    ->native(false)
                                    ->default(now())
                                    ->seconds(false)
                                    ->required(),
                                Select::make('assigned_user_id')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.fields.responsible'))
                                    ->relationship('assignedUser', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->default(Auth::id()),
                            ]),
                    ]),

                Tabs::make('manufacturing-order-tabs')
                    ->tabs([
                        Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.title'))
                            ->schema([
                                static::getComponentsRepeater(),
                            ]),
                        Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.title'))
                            ->schema([
                                static::getWorkOrdersRepeater(),
                            ]),
                        Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.by-products.title'))
                            ->schema([
                                Placeholder::make('by_products_process_note')
                                    ->hiddenLabel()
                                    ->content(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.by-products.process-note')),
                            ]),
                        Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.title'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('operation_type_id')
                                            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.fields.operation-type'))
                                            ->relationship(
                                                'operationType',
                                                'name',
                                                fn (Builder $query) => $query
                                                    ->withTrashed()
                                                    ->where('type', 'manufacture')
                                            )
                                            ->getOptionLabelFromRecordUsing(fn (OperationType $record): string => static::getOperationTypeLabel($record))
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->wrapOptionLabels(false)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                $operationType = OperationType::query()->withTrashed()->find($state);

                                                $set('source_location_id', $operationType?->source_location_id);
                                                $set('destination_location_id', $operationType?->destination_location_id);
                                            }),
                                        Select::make('source_location_id')
                                            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.fields.source'))
                                            ->relationship('sourceLocation', 'full_name', fn (Builder $query) => $query->withTrashed())
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->wrapOptionLabels(false),
                                        Select::make('destination_location_id')
                                            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.fields.finished-products-location'))
                                            ->relationship('destinationLocation', 'full_name', fn (Builder $query) => $query->withTrashed())
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->wrapOptionLabels(false)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                                $set('destination_location_id', $state);
                                            }),
                                        Select::make('company_id')
                                            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.fields.company'))
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->default(Auth::user()?->default_company_id),
                                    ]),
                            ]),
                    ]),

                Hidden::make('destination_location_id'),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->columns([
                TextColumn::make('reference')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.reference'))
                    ->searchable(),
                TextColumn::make('product.name')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.product'))
                    ->searchable(),
                TextColumn::make('bill_of_material_id')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.bill-of-material'))
                    ->formatStateUsing(fn (mixed $state, Order $record): string => static::getBillOfMaterialLabel($record->billOfMaterial))
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.quantity'))
                    ->numeric(decimalPlaces: 4),
                TextColumn::make('deadline_at')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.scheduled-date'))
                    ->dateTime(),
                TextColumn::make('assignedUser.name')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.responsible'))
                    ->placeholder('—'),
                TextColumn::make('state')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.table.columns.state'))
                    ->badge(),
            ])
            ->recordTitleAttribute('reference')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                InfolistProgressStepper::make('state')
                    ->hiddenLabel()
                    ->inline()
                    ->options(function (Order $record): array {
                        $options = ManufacturingOrderState::options();

                        unset(
                            $options[ManufacturingOrderState::PROGRESS->value],
                            $options[ManufacturingOrderState::TO_CLOSE->value],
                            $options[ManufacturingOrderState::CANCEL->value],
                        );

                        if ($record->state === ManufacturingOrderState::CANCEL) {
                            $options[ManufacturingOrderState::CANCEL->value] = ManufacturingOrderState::CANCEL->getLabel();
                        }

                        return $options;
                    }),

                Section::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.sections.general.title'))
                    ->columns(2)
                    ->schema([
                        Group::make()
                            ->columns(1)
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.sections.general.entries.product'))
                                    ->size(TextSize::Large)
                                    ->placeholder('—'),
                                TextEntry::make('quantity')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.sections.general.entries.quantity'))
                                    ->numeric(decimalPlaces: 4)
                                    ->suffix(fn (Order $record): string => ' '.($record->uom?->name ?? '—')),
                                TextEntry::make('bill_of_material_id')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.sections.general.entries.bill-of-material'))
                                    ->state(fn (Order $record): string => static::getBillOfMaterialLabel($record->billOfMaterial)),
                            ]),
                        Group::make()
                            ->columns(1)
                            ->schema([
                                TextEntry::make('deadline_at')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.sections.general.entries.scheduled-date'))
                                    ->dateTime(),
                                TextEntry::make('assignedUser.name')
                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.sections.general.entries.responsible'))
                                    ->placeholder('—'),
                            ]),

                        Tabs::make('manufacturing-order-details')
                            ->tabs([
                                Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.components.title'))
                                    ->schema([
                                        TextEntry::make('components_process_note')
                                            ->hiddenLabel()
                                            ->state(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.components.process-note')),
                                    ]),
                                Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.work-orders.title'))
                                    ->schema([
                                        TextEntry::make('work_orders_process_note')
                                            ->hiddenLabel()
                                            ->state(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.work-orders.process-note')),
                                    ]),
                                Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.by-products.title'))
                                    ->schema([
                                        TextEntry::make('by_products_process_note')
                                            ->hiddenLabel()
                                            ->state(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.by-products.process-note')),
                                    ]),
                                Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.miscellaneous.title'))
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('operationType.name')
                                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.miscellaneous.entries.operation-type'))
                                                    ->formatStateUsing(fn (mixed $state, Order $record): string => static::getOperationTypeLabel($record->operationType)),
                                                TextEntry::make('sourceLocation.full_name')
                                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.miscellaneous.entries.source'))
                                                    ->placeholder('—'),
                                                TextEntry::make('finalLocation.full_name')
                                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.miscellaneous.entries.finished-products-location'))
                                                    ->placeholder('—'),
                                                TextEntry::make('company.name')
                                                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.infolist.tabs.miscellaneous.entries.company'))
                                                    ->placeholder('—'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewManufacturingOrder::class,
            EditManufacturingOrder::class,
            OverviewManufacturingOrder::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'    => ListManufacturingOrders::route('/'),
            'create'   => CreateManufacturingOrder::route('/create'),
            'view'     => ViewManufacturingOrder::route('/{record}'),
            'edit'     => EditManufacturingOrder::route('/{record}/edit'),
            'overview' => OverviewManufacturingOrder::route('/{record}/overview'),
        ];
    }

    protected static function getDefaultBillOfMaterialId(Product $product): ?int
    {
        $productIds = array_filter([$product->id, $product->parent_id]);

        return BillOfMaterial::query()
            ->withTrashed()
            ->whereIn('product_id', $productIds)
            ->orderByDesc('product_id')
            ->value('id');
    }

    protected static function applyBillOfMaterialDefaults(Set $set, ?BillOfMaterial $billOfMaterial, ?Product $product = null, float $quantity = 1): void
    {
        if (! $billOfMaterial) {
            $set('rawMaterialMoves', []);
            $set('workOrders', []);

            return;
        }

        if ($billOfMaterial->operation_type_id) {
            $operationType = OperationType::query()->withTrashed()->find($billOfMaterial->operation_type_id);
        } else {
            $operationType = OperationType::query()->withTrashed()->where('type', 'manufacture')->first();
        }

        $set('operation_type_id', $operationType->id);

        $set('source_location_id', $operationType?->source_location_id);

        $set('destination_location_id', $operationType?->destination_location_id);

        $set('uom_id', $billOfMaterial->uom_id ?: static::getDefaultUomId());

        $set('company_id', $billOfMaterial->company_id);

        $set('rawMaterialMoves', static::getComponentRepeaterState($billOfMaterial, $quantity));

        $set('workOrders', static::getWorkOrderRepeaterState(
            $billOfMaterial,
            $product ?? $billOfMaterial->product,
            $quantity,
        ));
    }

    protected static function getBillOfMaterialLabel(?BillOfMaterial $billOfMaterial): string
    {
        if (! $billOfMaterial) {
            return '—';
        }

        $reference = $billOfMaterial->code ?: (string) $billOfMaterial->id;
        $productName = $billOfMaterial->product?->name;

        if (! $productName) {
            return $reference;
        }

        return $reference.': '.$productName;
    }

    protected static function getOperationTypeLabel(?OperationType $operationType): string
    {
        if (! $operationType) {
            return '—';
        }

        if (! $operationType->warehouse) {
            return $operationType->name;
        }

        return $operationType->warehouse->name.': '.$operationType->name;
    }

    protected static function getQuantityUomField(): FusedGroup
    {
        return FusedGroup::make([
            TextInput::make('quantity')
                ->numeric()
                ->minValue(0.0001)
                ->step('0.0001')
                ->default(1)
                ->live(debounce: 300)
                ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                    $billOfMaterial = BillOfMaterial::query()->withTrashed()->find($get('bill_of_material_id'));
                    $product = Product::query()->withTrashed()->find($get('product_id'));

                    static::applyBillOfMaterialDefaults(
                        $set,
                        $billOfMaterial,
                        $product,
                        (float) ($state ?: 1),
                    );
                })
                ->required()
                ->columnSpan(2),
            Select::make('uom_id')
                ->hiddenLabel()
                ->native(false)
                ->required()
                ->searchable()
                ->preload()
                ->options(function (Get $get): array {
                    $product = Product::query()->withTrashed()->find($get('product_id'));
                    $categoryId = $product?->uom?->category_id;

                    return UOM::query()
                        ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                        ->pluck('name', 'id')
                        ->all();
                })
                ->placeholder('UoM'),
        ])
            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.fields.quantity'))
            ->columns(3);
    }

    protected static function getComponentsRepeater(): Repeater
    {
        return Repeater::make('rawMaterialMoves')
            ->hiddenLabel()
            ->defaultItems(0)
            ->addable(false)
            ->deletable(true)
            ->reorderable(false)
            ->compact()
            ->table([
                RepeaterTableColumn::make('product_id')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.columns.component')),
                RepeaterTableColumn::make('rendered_display_from')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.columns.from')),
                RepeaterTableColumn::make('product_uom_qty')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.columns.to-consume')),
                RepeaterTableColumn::make('uom_id')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.columns.uom')),
                RepeaterTableColumn::make('rendered_display_forecast')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.columns.forecast'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->schema([
                Hidden::make('name'),
                Hidden::make('source_location_id'),
                Hidden::make('display_from'),
                Hidden::make('display_forecast'),
                Select::make('product_id')
                    ->hiddenLabel()
                    ->options(fn (): array => Product::query()
                        ->withTrashed()
                        ->where('type', ProductType::GOODS)
                        ->whereNull('is_configurable')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->wrapOptionLabels(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        $product = Product::query()->withTrashed()->find($state);

                        $uomId = $product?->uom_id ?: static::getDefaultUomId();

                        $set('uom_id', $uomId);
                    })
                    ->required(),
                Placeholder::make('rendered_display_from')
                    ->hiddenLabel()
                    ->content(fn (Get $get): string => (string) ($get('display_from') ?: '—')),
                TextInput::make('product_uom_qty')
                    ->hiddenLabel()
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true)
                    ->required(),
                Select::make('uom_id')
                    ->hiddenLabel()
                    ->options(function (Get $get): array {
                        $product = Product::query()->withTrashed()->find($get('product_id'));
                        
                        $categoryId = $product?->uom?->category_id;

                        return UOM::query()
                            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->wrapOptionLabels(false)
                    ->required(),
                Placeholder::make('rendered_display_forecast')
                    ->hiddenLabel()
                    ->content(fn (Get $get): string => (string) ($get('display_forecast') ?: '—')),
            ]);
    }

    protected static function getWorkOrdersRepeater(): Repeater
    {
        return Repeater::make('workOrders')
            ->hiddenLabel()
            ->defaultItems(0)
            ->addable(false)
            ->deletable(true)
            ->reorderable(false)
            ->compact()
            ->table([
                RepeaterTableColumn::make('operation_id')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.operation')),
                RepeaterTableColumn::make('work_center_id')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.work-center')),
                RepeaterTableColumn::make('rendered_display_product')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.product')),
                RepeaterTableColumn::make('rendered_display_quantity_remaining')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.quantity-remaining')),
                RepeaterTableColumn::make('expected_duration')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.expected-duration')),
                RepeaterTableColumn::make('rendered_display_real_duration')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.real-duration')),
                RepeaterTableColumn::make('started_at')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.start'))
                    ->toggleable(isToggledHiddenByDefault: true),
                RepeaterTableColumn::make('finished_at')
                    ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.columns.end'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->schema([
                Hidden::make('name'),
                Hidden::make('product_id'),
                Hidden::make('duration'),
                Hidden::make('quantity_remaining'),
                Hidden::make('display_product'),
                Select::make('operation_id')
                    ->hiddenLabel()
                    ->options(fn (): array => Operation::query()->withTrashed()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->wrapOptionLabels(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        $operation = Operation::query()->withTrashed()->find($state);

                        $set('name', $operation?->name);
                        $set('work_center_id', $operation?->work_center_id);
                    })
                    ->required(),
                Select::make('work_center_id')
                    ->hiddenLabel()
                    ->options(fn (): array => WorkCenter::query()->withTrashed()->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->wrapOptionLabels(false)
                    ->required(),
                Placeholder::make('rendered_display_product')
                    ->hiddenLabel()
                    ->content(fn (Get $get): string => (string) ($get('display_product') ?: '—')),
                Placeholder::make('rendered_display_quantity_remaining')
                    ->hiddenLabel()
                    ->content(fn (Get $get): string => number_format((float) ($get('quantity_remaining') ?: 0), 4)),
                TextInput::make('expected_duration')
                    ->hiddenLabel()
                    ->default('00:00')
                    ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                        $component->state(format_float_time((float) ($state ?: 0), 'minutes'));
                    })
                    ->dehydrateStateUsing(fn (?string $state): float => parse_float_time($state, 'minutes'))
                    ->required(),
                Placeholder::make('rendered_display_real_duration')
                    ->hiddenLabel()
                    ->content(fn (Get $get): string => format_float_time((float) ($get('duration') ?: 0), 'minutes')),
                DateTimePicker::make('started_at')
                    ->hiddenLabel()
                    ->native(false)
                    ->seconds(false),
                DateTimePicker::make('finished_at')
                    ->hiddenLabel()
                    ->native(false)
                    ->seconds(false),
            ]);
    }

    protected static function getComponentRepeaterState(BillOfMaterial $billOfMaterial, float $quantity): array
    {
        $quantityMultiplier = $billOfMaterial->getQuantityMultiplier($quantity);

        if ($billOfMaterial->operation_type_id) {
            $operationType = $billOfMaterial->operationType;
        } else {
            $operationType = OperationType::query()->withTrashed()->where('type', 'manufacture')->first();
        }

        return $billOfMaterial->lines()
            ->with(['product', 'uom'])
            ->orderBy('sort')
            ->get()
            ->map(fn (BillOfMaterialLine $line): array => [
                'product_id'         => $line->product_id,
                'uom_id'             => $line->uom_id,
                'product_uom_qty'    => round((float) $line->quantity * $quantityMultiplier, 4),
                'source_location_id' => $operationType->source_location_id,
                'display_from'       => $operationType?->sourceLocation?->full_name ?? '—',
                'display_forecast'   => '—',
            ])
            ->values()
            ->all();
    }

    protected static function getWorkOrderRepeaterState(BillOfMaterial $billOfMaterial, ?Product $product, float $quantity): array
    {
        $product ??= $billOfMaterial->product;

        return $billOfMaterial->operations()
            ->with(['workCenter'])
            ->orderBy('sort')
            ->get()
            ->map(fn (Operation $operation): array => [
                'operation_id'              => $operation->id,
                'work_center_id'            => $operation->work_center_id,
                'product_id'                => $product?->id,
                'expected_duration'         => format_float_time($operation->getExpectedDuration($product, $quantity), 'minutes'),
                'duration'                  => 0,
                'quantity_remaining'        => round($quantity, 4),
                'display_product'           => $product?->name ?? '—',
            ])
            ->values()
            ->all();
    }

    protected static function getDefaultUomId(): ?int
    {
        return UOM::query()
            ->where('name', 'Units')
            ->value('id')
            ?? UOM::query()->value('id');
    }
}
