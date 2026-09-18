@if ($screen === 'products' && ! empty($cart))
    <p @class(['pos-hint', 'pos-hint--hidden' => $activeLineKey])>
        {{ __('point-of-sale::filament/pos/pages/terminal.numpad.hint') }}
    </p>

    <div class="pos-actions">
        <button type="button" class="pos-key pos-key--muted" wire:click="openCustomers">
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.customer') }}
        </button>

        <button
            type="button"
            class="pos-key pos-key--muted"
            wire:click="openNotes"
            @disabled(! $activeLineKey)
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.note') }}
        </button>

        <button
            type="button"
            class="pos-key pos-key--muted"
            wire:click="removeActiveLine"
            @disabled(! $activeLineKey)
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.remove-line') }}
        </button>
    </div>

    <div @class(['pos-keypad', 'pos-keypad--idle' => ! $activeLineKey])>
        @foreach ([['1', '2', '3', 'qty'], ['4', '5', '6', 'discount'], ['7', '8', '9', 'price']] as [$a, $b, $c, $mode])
            @foreach ([$a, $b, $c] as $digit)
                <button type="button" class="pos-key" wire:click="pressNumpad('{{ $digit }}')">
                    {{ $digit }}
                </button>
            @endforeach

            <button
                type="button"
                class="pos-key pos-key--mode"
                aria-pressed="{{ $numpadMode === $mode ? 'true' : 'false' }}"
                wire:click="setNumpadMode('{{ $mode }}')"
            >
                {{ __('point-of-sale::filament/pos/pages/terminal.numpad.'.$mode) }}
            </button>
        @endforeach

        <button type="button" class="pos-key" wire:click="pressNumpad('0')">0</button>

        <button type="button" class="pos-key" wire:click="pressNumpad('.')">.</button>

        <button type="button" class="pos-key pos-key--muted" wire:click="pressNumpad('backspace')">
            <x-filament::icon icon="heroicon-m-backspace" class="h-5 w-5" />
        </button>

        <button type="button" class="pos-key pos-key--muted" wire:click="pressNumpad('clear')">
            {{ __('point-of-sale::filament/pos/pages/terminal.numpad.clear') }}
        </button>
    </div>

    <button type="button" class="pos-pay" wire:click="goToPayment">
        <x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5" />

        {{ __('point-of-sale::filament/pos/pages/terminal.actions.payment') }}
    </button>
@endif
