<x-filament::modal id="pos-customers" width="xl">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.customers.heading') }}
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center gap-2">
            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass" class="min-w-0 flex-1">
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="customerSearch"
                    :placeholder="__('point-of-sale::filament/pos/pages/terminal.customers.search')"
                />
            </x-filament::input.wrapper>

            {{ $this->createCustomerAction }}
        </div>

        <div class="flex flex-col">
            @if ($partnerId)
                <button
                    type="button"
                    class="flex w-full items-center gap-2 border-b border-gray-100 px-4 py-2.5 text-left text-sm font-medium text-danger-600 hover:bg-danger-50 dark:border-gray-800 dark:text-danger-400 dark:hover:bg-danger-500/10"
                    wire:click="selectCustomer(null)"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />

                    {{ __('point-of-sale::filament/pos/pages/terminal.customers.clear') }}
                </button>
            @endif

            @forelse ($this->getCustomers() as $customer)
                <button
                    type="button"
                    class="flex w-full items-start justify-between gap-3 border-b border-gray-100 px-4 py-2.5 text-left dark:border-gray-800"
                    wire:click="selectCustomer({{ $customer->getKey() }})"
                >
                    <span class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $customer->name }}
                    </span>

                    <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                        {{ $customer->email ?? $customer->phone }}
                    </span>
                </button>
            @empty
                <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ filled($customerSearch)
                        ? __('point-of-sale::filament/pos/pages/terminal.customers.no-match', ['search' => $customerSearch])
                        : __('point-of-sale::filament/pos/pages/terminal.customers.empty') }}
                </p>
            @endforelse
        </div>

        @if ($this->getCustomers()->count() >= 50)
            <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                {{ __('point-of-sale::filament/pos/pages/terminal.customers.narrow') }}
            </p>
        @endif
    </div>
</x-filament::modal>
