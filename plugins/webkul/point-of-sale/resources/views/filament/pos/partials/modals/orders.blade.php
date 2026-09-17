<x-filament::modal id="pos-orders" width="5xl">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.orders.heading') }}
    </x-slot>

    @forelse ($this->getSessionOrders() as $order)
        <div class="pos-order-row">
            <span class="pos-figure text-xs text-gray-500 dark:text-gray-400">
                {{ $order->ordered_at?->format('Y-m-d H:i') }}
            </span>

            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ $order->name ?? $order->reference }}
            </span>

            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $order->partner?->name ?? '—' }}
            </span>

            <span class="pos-figure text-sm text-gray-950 dark:text-white">
                {{ $this->money((float) $order->amount_total) }}
            </span>

            <x-filament::badge :color="$order->state->getColor()">
                {{ $order->state->getLabel() }}
            </x-filament::badge>

            <div class="flex items-center gap-2">
                @if ($order->state === \Webkul\PointOfSale\Enums\OrderState::DRAFT)
                    <x-filament::button size="sm" wire:click="switchOrder({{ $order->getKey() }})">
                        {{ __('point-of-sale::filament/pos/pages/terminal.orders.load') }}
                    </x-filament::button>

                    <x-filament::icon-button
                        icon="heroicon-o-trash"
                        color="danger"
                        wire:click="discardOrder({{ $order->getKey() }})"
                        :label="__('point-of-sale::filament/pos/pages/terminal.orders.discard')"
                    />
                @endif
            </div>
        </div>
    @empty
        <p class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('point-of-sale::filament/pos/pages/terminal.orders.empty') }}
        </p>
    @endforelse
</x-filament::modal>
