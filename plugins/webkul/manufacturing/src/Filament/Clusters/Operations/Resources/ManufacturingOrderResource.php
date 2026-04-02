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
use Webkul\Manufacturing\Models\Order;
use Webkul\Manufacturing\Models\Product;
use Webkul\Product\Enums\ProductType;
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

                                            return;
                                        }

                                        $set('uom_id', $product->uom_id ?: static::getDefaultUomId());
                                        $set('company_id', $product->company_id ?? Auth::user()?->default_company_id);
                                        $set('bill_of_material_id', static::getDefaultBillOfMaterialId($product));

                                        static::applyBillOfMaterialDefaults(
                                            $set,
                                            BillOfMaterial::query()->withTrashed()->find($get('bill_of_material_id') ?: static::getDefaultBillOfMaterialId($product)),
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
                                        static::applyBillOfMaterialDefaults(
                                            $set,
                                            BillOfMaterial::query()->withTrashed()->find($state),
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
                                Placeholder::make('components_process_note')
                                    ->hiddenLabel()
                                    ->content(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.components.process-note')),
                            ]),
                        Tab::make(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.title'))
                            ->schema([
                                Placeholder::make('work_orders_process_note')
                                    ->hiddenLabel()
                                    ->content(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.work-orders.process-note')),
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
                                                    ->where('type', 'manufacturing')
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
                                                $set('final_location_id', $operationType?->destination_location_id);
                                            }),
                                        Select::make('source_location_id')
                                            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.fields.source'))
                                            ->relationship('sourceLocation', 'full_name', fn (Builder $query) => $query->withTrashed())
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->wrapOptionLabels(false),
                                        Select::make('final_location_id')
                                            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.tabs.miscellaneous.fields.finished-products-location'))
                                            ->relationship('finalLocation', 'full_name', fn (Builder $query) => $query->withTrashed())
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

    protected static function applyBillOfMaterialDefaults(Set $set, ?BillOfMaterial $billOfMaterial): void
    {
        if (! $billOfMaterial) {
            return;
        }

        $set('uom_id', $billOfMaterial->uom_id ?: static::getDefaultUomId());
        $set('company_id', $billOfMaterial->company_id);

        if (! $billOfMaterial->operation_type_id) {
            return;
        }

        $set('operation_type_id', $billOfMaterial->operation_type_id);

        $operationType = OperationType::query()->withTrashed()->find($billOfMaterial->operation_type_id);

        $set('source_location_id', $operationType?->source_location_id);
        $set('destination_location_id', $operationType?->destination_location_id);
        $set('final_location_id', $operationType?->destination_location_id);
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
                ->required()
                ->columnSpan(2),
            Select::make('uom_id')
                ->hiddenLabel()
                ->native(false)
                ->required()
                ->searchable()
                ->preload()
                ->options(UOM::query()->pluck('name', 'id'))
                ->placeholder('UoM'),
        ])
            ->label(__('manufacturing::filament/clusters/operations/resources/manufacturing-order.form.sections.general.fields.quantity'))
            ->columns(3);
    }

    protected static function getDefaultUomId(): ?int
    {
        return UOM::query()
            ->where('name', 'Units')
            ->value('id')
            ?? UOM::query()->value('id');
    }
}
