<?php

namespace Webkul\Purchase\Livewire\Customer;

use Webkul\Purchase\Models\OrderLine;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;  
use Filament\Tables\Table;
use Livewire\Component;

class ListProducts extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    
    public $record;
    
    public function mount($record)
    {
        $this->record = $record;
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderLine::query()->where('order_id', $this->record->id)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product'),
                TextColumn::make('product_qty')
                    ->label('Quantity')
                    ->formatStateUsing(fn (string $state): string => $state . ' Units'),
                TextColumn::make('price_unit')
                    ->label('Unit Price')
                    ->money(fn (OrderLine $record) => $record->currency->code),
                TextColumn::make('taxes.name')
                    ->label('Taxes')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('discount')
                    ->label('Discount %')
                    ->suffix('%'),
                TextColumn::make('price_subtotal')
                    ->label('Amount')
                    ->money(fn (OrderLine $record) => $record->currency->code),
            ])
            ->paginated(false);
    }
    
    public function render()
    {
        return view('purchases::livewire.customer.account.clusters.order.pages.view-record.products');
    }
}