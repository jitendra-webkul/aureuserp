<?php

namespace Webkul\Manufacturing\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Models\Calendar;
use Webkul\Manufacturing\Enums\WorkCenterWorkingState;
use Webkul\Manufacturing\Filament\Clusters\Configurations;
use Webkul\Manufacturing\Filament\Clusters\Configurations\Resources\WorkCenterResource\Pages\CreateWorkCenter;
use Webkul\Manufacturing\Filament\Clusters\Configurations\Resources\WorkCenterResource\Pages\EditWorkCenter;
use Webkul\Manufacturing\Filament\Clusters\Configurations\Resources\WorkCenterResource\Pages\ListWorkCenters;
use Webkul\Manufacturing\Filament\Clusters\Configurations\Resources\WorkCenterResource\Pages\ViewWorkCenter;
use Webkul\Manufacturing\Models\WorkCenter;

class WorkCenterResource extends Resource
{
    protected static ?string $model = WorkCenter::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configurations::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('manufacturing::models/work-center.title');
    }

    public static function getNavigationGroup(): string
    {
        return __('manufacturing::filament/clusters/configurations/resources/work-center.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('manufacturing::filament/clusters/configurations/resources/work-center.navigation.title');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.title'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->autofocus()
                                    ->placeholder(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.name-placeholder'))
                                    ->extraInputAttributes(['style' => 'font-size: 1.5rem;height: 3rem;']),

                                TextInput::make('code')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.code'))
                                    ->maxLength(255)
                                    ->placeholder(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.code-placeholder')),

                                Group::make()
                                    ->schema([
                                        Select::make('company_id')
                                            ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.company'))
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled(fn (): bool => filled(Auth::user()?->default_company_id))
                                            ->default(Auth::user()?->default_company_id),
                                        Select::make('calendar_id')
                                            ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.calendar'))
                                            ->options(fn (): array => Calendar::withTrashed()->pluck('name', 'id')->all())
                                            ->searchable()
                                            ->preload(),
                                    ])
                                    ->columns(2),

                                Textarea::make('note')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.general.fields.note'))
                                    ->rows(6)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.title'))
                            ->schema([
                                Select::make('working_state')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.working-state'))
                                    ->options(WorkCenterWorkingState::class)
                                    ->default(WorkCenterWorkingState::NORMAL)
                                    ->required(),
                                TextInput::make('default_capacity')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.default-capacity'))
                                    ->numeric()
                                    ->minValue(1),
                                TextInput::make('time_efficiency')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.time-efficiency'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                                TextInput::make('oee_target')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.oee-target'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%'),
                                TextInput::make('costs_per_hour')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.costs-per-hour'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('$'),
                                TextInput::make('setup_time')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.setup-time'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.time-suffix')),
                                TextInput::make('cleanup_time')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.cleanup-time'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(__('manufacturing::filament/clusters/configurations/resources/work-center.form.sections.configuration.fields.time-suffix')),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->columns([
                TextColumn::make('name')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.name'))
                    ->searchable(),
                TextColumn::make('code')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.code'))
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('calendar.name')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.calendar'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('working_state')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.working-state'))
                    ->badge()
                    ->formatStateUsing(fn (?WorkCenterWorkingState $state): ?string => $state?->getLabel())
                    ->color(fn (?WorkCenterWorkingState $state): string => $state?->getColor() ?? 'gray'),
                TextColumn::make('default_capacity')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.default-capacity'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('time_efficiency')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.time-efficiency'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('costs_per_hour')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.costs-per-hour'))
                    ->numeric(decimalPlaces: 4)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.deleted-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.created-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.columns.updated-at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.filters.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('working_state')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.filters.working-state'))
                    ->options(WorkCenterWorkingState::options()),
            ])
            ->groups([
                Tables\Grouping\Group::make('company.name')
                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.table.groups.company'))
                    ->collapsible(),
            ])
            ->defaultGroup('company.name')
            ->recordActions([
                ViewAction::make()
                    ->hidden(fn (WorkCenter $record): bool => $record->trashed()),
                EditAction::make()
                    ->hidden(fn (WorkCenter $record): bool => $record->trashed()),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.restore.notification.title'))
                            ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.restore.notification.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.delete.notification.title'))
                            ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.delete.notification.body')),
                    ),
                ForceDeleteAction::make()
                    ->action(function (WorkCenter $record, ForceDeleteAction $action): void {
                        try {
                            $record->forceDelete();
                        } catch (QueryException) {
                            Notification::make()
                                ->danger()
                                ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.force-delete.notification.error.title'))
                                ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.force-delete.notification.error.body'))
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.force-delete.notification.success.title'))
                            ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.actions.force-delete.notification.success.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.restore.notification.title'))
                                ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.restore.notification.body')),
                        ),
                    DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.delete.notification.title'))
                                ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.delete.notification.body')),
                        ),
                    ForceDeleteBulkAction::make()
                        ->action(function (Collection $records, ForceDeleteBulkAction $action): void {
                            try {
                                $records->each(fn (Model $record): ?bool => $record->forceDelete());
                            } catch (QueryException) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.force-delete.notification.error.title'))
                                    ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.force-delete.notification.error.body'))
                                    ->send();

                                $action->cancel();
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.force-delete.notification.success.title'))
                                ->body(__('manufacturing::filament/clusters/configurations/resources/work-center.table.bulk-actions.force-delete.notification.success.body')),
                        ),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.general.title'))
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.general.entries.name'))
                                    ->size(TextSize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-cog-6-tooth'),
                                TextEntry::make('code')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.general.entries.code'))
                                    ->placeholder('—')
                                    ->icon('heroicon-m-hashtag'),
                                Group::make()
                                    ->schema([
                                        TextEntry::make('company.name')
                                            ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.general.entries.company'))
                                            ->placeholder('—')
                                            ->icon('heroicon-o-building-office'),
                                        TextEntry::make('calendar.name')
                                            ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.general.entries.calendar'))
                                            ->placeholder('—')
                                            ->icon('heroicon-o-clock'),
                                    ])
                                    ->columns(2),
                                TextEntry::make('note')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.general.entries.note'))
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.title'))
                            ->schema([
                                TextEntry::make('working_state')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.working-state'))
                                    ->badge()
                                    ->formatStateUsing(fn (?WorkCenterWorkingState $state): ?string => $state?->getLabel())
                                    ->color(fn (?WorkCenterWorkingState $state): string => $state?->getColor() ?? 'gray'),
                                TextEntry::make('default_capacity')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.default-capacity'))
                                    ->placeholder('—'),
                                TextEntry::make('time_efficiency')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.time-efficiency'))
                                    ->suffix('%')
                                    ->placeholder('—'),
                                TextEntry::make('oee_target')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.oee-target'))
                                    ->suffix('%')
                                    ->placeholder('—'),
                                TextEntry::make('costs_per_hour')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.costs-per-hour'))
                                    ->numeric(decimalPlaces: 4)
                                    ->placeholder('—'),
                                TextEntry::make('setup_time')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.setup-time'))
                                    ->suffix(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.time-suffix'))
                                    ->placeholder('—'),
                                TextEntry::make('cleanup_time')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.cleanup-time'))
                                    ->suffix(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.configuration.entries.time-suffix'))
                                    ->placeholder('—'),
                            ])
                            ->columns(1),

                        Section::make(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.record-information.title'))
                            ->schema([
                                TextEntry::make('creator.name')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.record-information.entries.created-by'))
                                    ->placeholder('—')
                                    ->icon('heroicon-o-user'),
                                TextEntry::make('created_at')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.record-information.entries.created-at'))
                                    ->dateTime()
                                    ->icon('heroicon-m-calendar'),
                                TextEntry::make('updated_at')
                                    ->label(__('manufacturing::filament/clusters/configurations/resources/work-center.infolist.sections.record-information.entries.last-updated'))
                                    ->dateTime()
                                    ->icon('heroicon-m-clock'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListWorkCenters::route('/'),
            'create' => CreateWorkCenter::route('/create'),
            'view'   => ViewWorkCenter::route('/{record}'),
            'edit'   => EditWorkCenter::route('/{record}/edit'),
        ];
    }
}
