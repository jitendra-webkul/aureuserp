<x-filament::modal id="pos-customers" width="xl">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.customers.heading') }}
    </x-slot>

    <button type="button" class="pos-line" wire:click="selectCustomer(null)">
        <span class="text-sm font-semibold text-gray-950 dark:text-white">
            {{ __('point-of-sale::filament/pos/pages/terminal.customers.clear') }}
        </span>
    </button>

    @forelse ($this->getCustomers() as $customer)
        <button type="button" class="pos-line" wire:click="selectCustomer({{ $customer->getKey() }})">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $customer->name }}</span>

            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->email }}</span>
        </button>
    @empty
        <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('point-of-sale::filament/pos/pages/terminal.customers.empty') }}
        </p>
    @endforelse
</x-filament::modal>
