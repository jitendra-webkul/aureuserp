<x-filament::modal id="pos-price-lists" width="md" :heading="__('point-of-sale::filament/pos/pages/terminal.price-lists.heading')">
    <div class="flex flex-col gap-2">
        @foreach ($this->availablePriceLists() as $priceList)
            <button
                type="button"
                @class(['pos-key', 'pos-key--muted', 'pos-key--active' => $this->selectedPriceList()?->id === $priceList->id])
                wire:click="selectPriceList({{ $priceList->id }})"
            >
                {{ $priceList->name }}
            </button>
        @endforeach

        <button
            type="button"
            @class(['pos-key', 'pos-key--muted', 'pos-key--active' => ! $priceListId])
            wire:click="selectPriceList(null)"
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.price-lists.default') }}
        </button>
    </div>
</x-filament::modal>
