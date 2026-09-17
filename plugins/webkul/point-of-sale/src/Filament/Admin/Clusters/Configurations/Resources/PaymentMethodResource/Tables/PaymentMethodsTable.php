<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\PaymentMethodResource\Tables;

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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Grouping\Group as TableGroup;
use Filament\Tables\Table;
use Webkul\PointOfSale\Enums\PaymentMethodType;
use Webkul\PointOfSale\Models\PaymentMethod;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.type'))
                    ->badge(),
                TextColumn::make('journal.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.journal'))
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('terminal_type')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.terminal-type'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('receivableAccount.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.receivable-account'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_cash_count')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.is-cash-count'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.is-active'))
                    ->boolean(),
                TextColumn::make('company.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.columns.company'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->groups([
                TableGroup::make('type')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.groups.type')),
                TableGroup::make('company.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.groups.company')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.filters.type'))
                    ->options(PaymentMethodType::class)
                    ->native(false),
                TrashedFilter::make(),
            ])
            ->recordTitleAttribute('name')
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (PaymentMethod $record): bool => $record->trashed()),
                RestoreAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.record-actions.restore.notification.success.title'))
                            ->body(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.record-actions.restore.notification.success.body')),
                    ),
                DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.record-actions.delete.notification.success.title'))
                            ->body(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.record-actions.delete.notification.success.body')),
                    ),
                ForceDeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.record-actions.force-delete.notification.success.title'))
                            ->body(__('point-of-sale::filament/admin/clusters/configurations/resources/payment-method.table.record-actions.force-delete.notification.success.body')),
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
