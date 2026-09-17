<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\ConfigResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group as TableGroup;
use Filament\Tables\Table;
use Webkul\PointOfSale\Models\Config;
use Webkul\Support\Models\Company;

class ConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.code'))
                    ->searchable(),
                TextColumn::make('lastClosedSession.stopped_at')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.closing'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('lastClosedSession.cash_balance_end_real')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.balance'))
                    ->money(fn (Config $record): ?string => $record->currency?->code)
                    ->placeholder('—'),
                TextColumn::make('warehouse.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.warehouse'))
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('operationType.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.operation-type'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('journal.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.journal'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stock_update_mode')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.stock-update-mode'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_restaurant')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.is-restaurant'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.is-active'))
                    ->boolean(),
                TextColumn::make('company.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.columns.company'))
                    ->placeholder('—')
                    ->visible(fn (): bool => Company::query()->count() > 1),
            ])
            ->groups([
                TableGroup::make('warehouse.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.groups.warehouse')),
                TableGroup::make('company.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.groups.company')),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordTitleAttribute('name')
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (Config $record): bool => $record->trashed()),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.record-actions.restore.notification.success.title'))
                            ->body(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.record-actions.restore.notification.success.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.record-actions.delete.notification.success.title'))
                            ->body(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.record-actions.delete.notification.success.body')),
                    ),
                ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.record-actions.force-delete.notification.success.title'))
                            ->body(__('point-of-sale::filament/admin/clusters/configurations/resources/config.table.record-actions.force-delete.notification.success.body')),
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
