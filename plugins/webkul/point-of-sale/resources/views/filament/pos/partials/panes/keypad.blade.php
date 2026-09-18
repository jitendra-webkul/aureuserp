@if ($screen === 'products' && ! empty($cart))
    <p @class([
        'flex-none px-1 py-2 text-center text-[0.8125rem] text-gray-500 dark:text-gray-400',
        'invisible' => $activeLineKey,
    ])>
        {{ __('point-of-sale::filament/pos/pages/terminal.numpad.hint') }}
    </p>

    <div class="grid flex-none grid-cols-3 gap-1.5">
        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" wire:click="openCustomers">
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.customer') }}
        </button>

        <button
            type="button"
            class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
            wire:click="openNotes"
            @disabled(! $activeLineKey)
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.note') }}
        </button>

        <button
            type="button"
            class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
            wire:click="removeActiveLine"
            @disabled(! $activeLineKey)
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.remove-line') }}
        </button>
    </div>

    <div @class(['grid flex-none grid-cols-4 gap-1.5', 'opacity-45' => ! $activeLineKey])>
        @foreach ([['1', '2', '3', 'qty'], ['4', '5', '6', 'discount'], ['7', '8', '9', 'price']] as [$a, $b, $c, $mode])
            @foreach ([$a, $b, $c] as $digit)
                <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800" wire:click="pressNumpad('{{ $digit }}')">
                    {{ $digit }}
                </button>
            @endforeach

            <button
                type="button"
                @class([
                    'flex min-h-13 items-center justify-center rounded-lg border text-[0.9375rem] transition-colors',
                    'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => $numpadMode === $mode,
                    'border-gray-200 bg-white font-medium text-gray-600 hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800' => $numpadMode !== $mode,
                ])
                aria-pressed="{{ $numpadMode === $mode ? 'true' : 'false' }}"
                wire:click="setNumpadMode('{{ $mode }}')"
            >
                {{ __('point-of-sale::filament/pos/pages/terminal.numpad.'.$mode) }}
            </button>
        @endforeach

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800" wire:click="pressNumpad('0')">0</button>

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800" wire:click="pressNumpad('.')">.</button>

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" wire:click="pressNumpad('backspace')">
            <x-filament::icon icon="heroicon-m-backspace" class="h-5 w-5" />
        </button>

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" wire:click="pressNumpad('clear')">
            {{ __('point-of-sale::filament/pos/pages/terminal.numpad.clear') }}
        </button>
    </div>

    <button type="button" class="flex min-h-14 flex-none items-center justify-center gap-2 rounded-lg bg-primary-600 text-base font-semibold text-white transition-colors hover:bg-primary-700 disabled:bg-gray-200 disabled:text-gray-400" wire:click="goToPayment">
        <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5" />

        {{ __('point-of-sale::filament/pos/pages/terminal.actions.payment') }}
    </button>
@endif
