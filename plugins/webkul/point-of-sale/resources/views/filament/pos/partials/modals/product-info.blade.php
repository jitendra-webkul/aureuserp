@if ($productInfoId && ($info = $this->productInfo()))
    <x-filament::modal id="pos-product-info" width="2xl">
        <x-slot name="heading">
            {{ __('point-of-sale::filament/pos/pages/terminal.product-info.heading') }}
        </x-slot>

        <div class="flex flex-col gap-1">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('point-of-sale::filament/pos/pages/terminal.product-info.inventory') }}
            </p>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span class="font-mono tabular-nums font-semibold text-gray-950 dark:text-white">{{ number_format($info['available'], 2) }}</span>
                {{ __('point-of-sale::filament/pos/pages/terminal.product-info.available') }},
                <span class="font-mono tabular-nums font-semibold text-gray-950 dark:text-white">{{ number_format($info['forecasted'], 2) }}</span>
                {{ __('point-of-sale::filament/pos/pages/terminal.product-info.forecasted') }}
            </p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('point-of-sale::filament/pos/pages/terminal.product-info.financials') }}
                </p>

                @foreach ([['price', $info['price']], ['cost', $info['cost']]] as [$key, $value])
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('point-of-sale::filament/pos/pages/terminal.product-info.'.$key) }}</span>
                        <span class="font-mono tabular-nums text-gray-950 dark:text-white">{{ $this->money($value) }}</span>
                    </div>
                @endforeach

                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('point-of-sale::filament/pos/pages/terminal.product-info.margin') }}</span>
                    <span class="font-mono tabular-nums text-gray-950 dark:text-white">
                        {{ $this->money($info['margin']) }} ({{ number_format($info['margin_ratio'], 2) }}%)
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('point-of-sale::filament/pos/pages/terminal.product-info.order') }}
                </p>

                @foreach ([['total-price', $info['total_price']], ['total-cost', $info['total_cost']], ['total-margin', $info['total_margin']]] as [$key, $value])
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">{{ __('point-of-sale::filament/pos/pages/terminal.product-info.'.$key) }}</span>
                        <span class="font-mono tabular-nums text-gray-950 dark:text-white">{{ $this->money($value) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <x-filament::button size="lg" wire:click="closeProductInfo">
            {{ __('point-of-sale::filament/pos/pages/terminal.product-info.close') }}
        </x-filament::button>
    </x-filament::modal>
@endif
