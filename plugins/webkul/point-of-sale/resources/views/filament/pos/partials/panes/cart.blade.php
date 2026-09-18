<div class="flex min-h-0 flex-auto flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    @if (filled($cart))
        <div class="flex flex-none items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5 dark:border-gray-800">
            <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.04em] text-gray-400 dark:text-gray-500">
                {{ trans_choice('point-of-sale::filament/pos/pages/terminal.cart.item-count', $this->cartLineCount(), ['count' => $this->cartLineCount()]) }}
            </span>

            <button type="button" class="rounded-md px-1.5 py-0.5 text-[0.6875rem] font-semibold text-gray-400 transition-colors hover:bg-danger-500/10 hover:text-danger-600 dark:text-gray-500 dark:hover:text-danger-400" wire:click="clearCart">
                {{ __('point-of-sale::filament/pos/pages/terminal.cart.clear-all') }}
            </button>
        </div>
    @endif

    <div class="min-h-0 flex-auto overflow-y-auto">
    @forelse ($cart as $key => $line)
        <button
            type="button"
            wire:click="selectLine('{{ $key }}')"
            @class([
                'flex w-full items-start justify-between gap-3 border-b border-gray-100 px-4 py-2.5 text-left dark:border-gray-800',
                'bg-primary-50 shadow-[inset_3px_0_0_var(--color-primary-600)] dark:bg-primary-500/15' => (string) $activeLineKey === (string) $key,
            ])
        >
            @if ($config->show_product_images)
                @php($lineImage = $this->lineImageUrl($line['product_id']))

                <span class="flex size-9 flex-none items-center justify-center overflow-hidden rounded-lg bg-gray-50 dark:bg-white/[0.06]">
                    @if ($lineImage)
                        <img src="{{ $lineImage }}" alt="{{ $line['name'] }}" class="size-full object-cover" />
                    @else
                        <x-filament::icon icon="heroicon-o-cube" class="h-4 w-4 text-gray-300 dark:text-gray-600" />
                    @endif
                </span>
            @endif

            <div class="min-w-0 flex-auto">
                <p @class([
                    'truncate text-sm font-semibold',
                    'text-primary-700 dark:text-primary-300' => (string) $activeLineKey === (string) $key,
                    'text-gray-950 dark:text-white' => (string) $activeLineKey !== (string) $key,
                ])>
                    {{ $line['name'] }}
                </p>

                <p class="font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                    {{ $line['qty'] }} &times; {{ $this->money($this->displayUnitPrice($line['product_id'], $line['price_unit'])) }}
                    @if ($line['discount'] > 0)
                        &middot; {{ __('point-of-sale::filament/pos/pages/terminal.cart.discount', ['percentage' => $line['discount'] + 0]) }}
                    @endif
                </p>

                @if (filled($line['note'] ?? null))
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach (array_filter(explode("\n", $line['note'])) as $note)
                            <x-filament::badge size="sm" :color="$this->noteColor($note)">
                                {{ $note }}
                            </x-filament::badge>
                        @endforeach
                    </div>
                @endif
            </div>

            <span class="font-mono text-sm tabular-nums font-semibold text-gray-950 dark:text-white">
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

    <div class="flex flex-none flex-col gap-1 border-t border-dashed border-gray-300 px-4 pb-4 pt-3 dark:border-gray-700">
        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ __('point-of-sale::filament/pos/pages/terminal.cart.subtotal') }}</span>
            <span class="font-mono tabular-nums">{{ $this->money($this->cartSubtotal()) }}</span>
        </div>

        @if ($this->cartDiscount() > 0)
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ __('point-of-sale::filament/pos/pages/terminal.cart.discount-total', ['percentage' => $globalDiscount + 0]) }}</span>
                <span class="font-mono tabular-nums text-danger-600 dark:text-danger-400">-{{ $this->money($this->cartDiscount()) }}</span>
            </div>
        @endif

        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
            <span>{{ __('point-of-sale::filament/pos/pages/terminal.cart.tax') }}</span>
            <span class="font-mono tabular-nums">{{ $this->money($this->cartTax()) }}</span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('point-of-sale::filament/pos/pages/terminal.cart.total') }}
            </span>

            <span class="font-mono text-2xl tabular-nums font-bold text-gray-950 dark:text-white">
                {{ $this->money($this->cartTotal()) }}
            </span>
        </div>
    </div>
</div>
