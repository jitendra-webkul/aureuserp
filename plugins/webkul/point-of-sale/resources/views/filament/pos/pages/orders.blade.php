<x-filament-panels::page>
    @php($orders = $this->getOrders())

    @if ($orders->isEmpty())
        <x-filament::empty-state
            icon="heroicon-o-clipboard-document-list"
            :heading="__('point-of-sale::filament/pos/pages/orders.empty.heading')"
            :description="__('point-of-sale::filament/pos/pages/orders.empty.description')"
        />
    @else
        <x-filament::section>
            <div class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($orders as $order)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $order->name ?? $order->reference }}
                            </p>

                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $order->partner?->name ?? __('point-of-sale::filament/pos/pages/orders.walk-in') }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <x-filament::badge :color="$order->state->getColor()">
                                {{ $order->state->getLabel() }}
                            </x-filament::badge>

                            <span class="pos-figure text-sm font-semibold text-gray-950 dark:text-white">
                                {{ money($order->amount_total, $order->currency?->name) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
