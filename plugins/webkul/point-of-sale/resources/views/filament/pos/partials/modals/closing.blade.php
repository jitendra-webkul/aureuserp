@php($control = $this->closingControl())

<x-filament::modal id="pos-closing" width="2xl">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.closing.heading') }}
    </x-slot>

    <x-slot name="description">
        {{ __('point-of-sale::filament/pos/pages/terminal.closing.orders', ['quantity' => $control['orders_details']['quantity']]) }}:
        <span class="pos-figure">{{ $this->money($control['orders_details']['amount']) }}</span>
    </x-slot>

    <div class="flex flex-col gap-5">
        @if ($control['default_cash_details'])
            @php($cash = $control['default_cash_details'])

            <div class="pos-close-block">
                <div class="pos-close-row pos-close-row--head">
                    <span>{{ $cash['name'] }}</span>
                    <span class="pos-figure">{{ $this->money($cash['amount']) }}</span>
                </div>

                <div class="pos-close-row">
                    <span>{{ __('point-of-sale::filament/pos/pages/terminal.closing.opening') }}</span>
                    <span class="pos-figure">{{ $this->money($cash['opening']) }}</span>
                </div>

                <div class="pos-close-row">
                    <span>{{ __('point-of-sale::filament/pos/pages/terminal.closing.payments') }}</span>
                    <span class="pos-figure">{{ $this->money($cash['payment_amount']) }}</span>
                </div>

                <button type="button" class="pos-close-row pos-close-row--toggle" wire:click="toggleCashMoves">
                    <span class="flex items-center gap-1">
                        <x-filament::icon
                            :icon="$showCashMoves ? 'heroicon-m-chevron-down' : 'heroicon-m-chevron-right'"
                            class="h-4 w-4"
                        />

                        {{ __('point-of-sale::filament/pos/pages/terminal.closing.moves') }}
                    </span>

                    <span class="pos-figure">{{ $this->money(collect($cash['moves'])->sum('amount')) }}</span>
                </button>

                @if ($showCashMoves)
                    @foreach ($cash['moves'] as $move)
                        <div class="pos-close-row pos-close-row--nested">
                            <span>{{ $move['name'] }}</span>
                            <span class="pos-figure">{{ $this->money($move['amount']) }}</span>
                        </div>
                    @endforeach
                @endif

                <div class="pos-close-row">
                    <span>{{ __('point-of-sale::filament/pos/pages/terminal.closing.counted') }}</span>
                    <span class="pos-figure">{{ $this->money((float) ($closingCash ?? 0)) }}</span>
                </div>

                <div @class(['pos-close-row', 'pos-close-row--diff' => ! float_is_zero($this->cashDifference($cash), precisionDigits: 2)])>
                    <span>{{ __('point-of-sale::filament/pos/pages/terminal.closing.difference') }}</span>
                    <span class="pos-figure">{{ $this->money($this->cashDifference($cash)) }}</span>
                </div>
            </div>
        @endif

        @foreach ($control['non_cash_payment_methods'] as $method)
            <div class="pos-close-block">
                <div class="pos-close-row pos-close-row--head">
                    <span>{{ $method['name'] }}</span>
                    <span class="pos-figure">{{ $this->money($method['amount']) }}</span>
                </div>

                <div class="pos-close-row">
                    <span>{{ __('point-of-sale::filament/pos/pages/terminal.closing.counted') }}</span>

                    <x-filament::input.wrapper class="w-40">
                        <x-filament::input
                            type="number"
                            step="0.01"
                            wire:model.live.debounce.500ms="paymentCounted.{{ $method['id'] }}"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div @class(['pos-close-row', 'pos-close-row--diff' => ! float_is_zero($this->paymentDifference($method), precisionDigits: 2)])>
                    <span>{{ __('point-of-sale::filament/pos/pages/terminal.closing.difference') }}</span>
                    <span class="pos-figure">{{ $this->money($this->paymentDifference($method)) }}</span>
                </div>
            </div>
        @endforeach

        <div class="flex flex-col gap-2">
            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('point-of-sale::filament/pos/pages/terminal.closing.count') }}
            </p>

            <div class="flex items-center gap-2">
                <x-filament::input.wrapper class="flex-1">
                    <x-filament::input type="number" step="0.01" wire:model.live.debounce.500ms="closingCash" />
                </x-filament::input.wrapper>

                <x-filament::icon-button
                    icon="heroicon-o-x-mark"
                    color="gray"
                    size="lg"
                    wire:click="$set('closingCash', null)"
                    :label="__('point-of-sale::filament/pos/pages/terminal.closing.clear')"
                />

                <x-filament::icon-button
                    icon="heroicon-o-banknotes"
                    color="gray"
                    size="lg"
                    wire:click="openMoneyDetails"
                    :label="__('point-of-sale::filament/pos/pages/terminal.money-details.label')"
                />
            </div>

            @if ($control['amount_authorized_diff'] !== null)
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('point-of-sale::filament/pos/pages/terminal.closing.authorized-diff', ['amount' => $this->money($control['amount_authorized_diff'])]) }}
                </p>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="flex flex-col gap-1">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('point-of-sale::filament/pos/pages/terminal.closing.opening-note') }}
                </p>

                <p class="pos-close-note">{{ $control['opening_notes'] ?: '—' }}</p>
            </div>

            <div class="flex flex-col gap-1">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('point-of-sale::filament/pos/pages/terminal.closing.note') }}
                </p>

                <x-filament::input.wrapper>
                    <textarea
                        rows="3"
                        wire:model="closingNote"
                        class="block w-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 outline-none sm:text-sm dark:text-white"
                    ></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>
    </div>

    <x-slot name="footerActions">
        <x-filament::button color="danger" wire:click="closeRegister">
            {{ __('point-of-sale::filament/pos/pages/terminal.closing.confirm') }}
        </x-filament::button>

        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'pos-closing' })">
            {{ __('point-of-sale::filament/pos/pages/terminal.closing.discard') }}
        </x-filament::button>

        <x-filament::button color="gray" wire:click="openCashMovement('in')">
            {{ __('point-of-sale::filament/pos/pages/terminal.menu.cash-in-out') }}
        </x-filament::button>

        <x-filament::button color="gray" tag="a" :href="$this->salesDetailsUrl()" target="_blank" icon="heroicon-m-arrow-down-tray">
            {{ __('point-of-sale::filament/pos/pages/terminal.closing.daily-sale') }}
        </x-filament::button>
    </x-slot>
</x-filament::modal>
