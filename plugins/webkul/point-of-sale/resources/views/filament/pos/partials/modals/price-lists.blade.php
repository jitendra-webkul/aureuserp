<x-filament::modal id="pos-price-lists" width="md" :heading="__('point-of-sale::filament/pos/pages/terminal.price-lists.heading')">
    <div class="flex flex-col gap-2">
        @foreach ($this->availablePriceLists() as $priceList)
            <button
                type="button"
                @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => $this->selectedPriceList()?->id === $priceList->id])
                wire:click="selectPriceList({{ $priceList->id }})"
            >
                {{ $priceList->name }}
            </button>
        @endforeach

        <button
            type="button"
            @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => ! $priceListId])
            wire:click="selectPriceList(null)"
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.price-lists.default') }}
        </button>
    </div>
</x-filament::modal>
