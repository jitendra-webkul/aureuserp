<div class="pos-receipt">
    @if (filled($cart))
        <div class="pos-receipt__head">
            <span class="pos-receipt__count">
                {{ trans_choice('point-of-sale::filament/pos/pages/terminal.cart.item-count', $this->cartLineCount(), ['count' => $this->cartLineCount()]) }}
            </span>

            <button type="button" class="pos-receipt__clear" wire:click="clearCart">
                {{ __('point-of-sale::filament/pos/pages/terminal.cart.clear-all') }}
            </button>
        </div>
    @endif

    <div class="pos-receipt__lines">
    @forelse ($cart as $key => $line)
        <button
            type="button"
            wire:click="selectLine('{{ $key }}')"
            @class([
                'pos-line',
                'pos-line--active' => (string) $activeLineKey === (string) $key,
            ])
        >
            @if ($config->show_product_images)
                @php($lineImage = $this->lineImageUrl($line['product_id']))

                <span class="pos-line__media">
                    @if ($lineImage)
                        <img src="{{ $lineImage }}" alt="{{ $line['name'] }}" />
                    @else
                        <x-filament::icon icon="heroicon-o-cube" class="h-4 w-4 text-gray-300 dark:text-gray-600" />
                    @endif
                </span>
            @endif

            <div class="min-w-0">
                <p class="pos-line__name truncate text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $line['name'] }}
                </p>

                <p class="pos-figure text-xs text-gray-500 dark:text-gray-400">
                    {{ $line['qty'] }} &times; {{ $this->money($this->displayUnitPrice($line['product_id'], $line['price_unit'])) }}
                    @if ($line['discount'] > 0)
                        &middot; {{ __('point-of-sale::filament/pos/pages/terminal.cart.discount', ['percentage' => $line['discount'] + 0]) }}
                    @endif
                </p>

                @if (filled($line['note'] ?? null))
                    <div class="pos-line__notes">
                        @foreach (array_filter(explode("\n", $line['note'])) as $note)
                            <x-filament::badge size="sm" :color="$this->noteColor($note)">
                                {{ $note }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <span class="pos-figure text-sm font-semibold text-gray-950 dark:text-white">
                {{ $this->money($line['qty'] * $this->displayUnitPrice($line['product_id'], $line['price_unit']) * (1 - $line['discount'] / 100)) }}
            </span>
        </button>
    @empty
        <div class="flex h-full flex-col items-center justify-center gap-2 py-16 text-center">
            <x-filament::icon
                icon="heroicon-o-shopping-cart"
                class="h-10 w-10 text-gray-400 dark:text-gray-500"
            />

            <p class="text-base font-semibold text-gray-500 dark:text-gray-400">
                {{ __('point-of-sale::filament/pos/pages/terminal.cart.empty.heading') }}
            </p>
        </div>
    @endforelse
    </div>

    <div class="pos-receipt__foot flex flex-col gap-1">
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ __('point-of-sale::filament/pos/pages/terminal.cart.subtotal') }}</span>
            <span class="pos-figure">{{ $this->money($this->cartSubtotal()) }}</span>
        </div>

        @if ($this->cartDiscount() > 0)
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ __('point-of-sale::filament/pos/pages/terminal.cart.discount-total', ['percentage' => $globalDiscount + 0]) }}</span>
                <span class="pos-figure text-danger-600 dark:text-danger-400">-{{ $this->money($this->cartDiscount()) }}</span>
            </div>
        @endif

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ __('point-of-sale::filament/pos/pages/terminal.cart.tax') }}</span>
            <span class="pos-figure">{{ $this->money($this->cartTax()) }}</span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('point-of-sale::filament/pos/pages/terminal.cart.total') }}
            </span>

            <span class="pos-figure text-2xl font-bold text-gray-950 dark:text-white">
                {{ $this->money($this->cartTotal()) }}
            </span>
        </div>
    </div>
</div>
