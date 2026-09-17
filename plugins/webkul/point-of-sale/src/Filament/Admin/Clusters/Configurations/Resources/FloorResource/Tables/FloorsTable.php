<?php

namespace Webkul\PointOfSale\Filament\Admin\Clusters\Configurations\Resources\FloorResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Webkul\PointOfSale\Models\Floor;

class FloorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderableColumns()
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tables_count')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.table.columns.tables'))
                    ->counts('tables'),
                ColorColumn::make('background_color')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.table.columns.background-color')),
                TextColumn::make('company.name')
                    ->label(__('point-of-sale::filament/admin/clusters/configurations/resources/floor.table.columns.company'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordTitleAttribute('name')
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (Floor $record): bool => $record->trashed()),
                RestoreAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
