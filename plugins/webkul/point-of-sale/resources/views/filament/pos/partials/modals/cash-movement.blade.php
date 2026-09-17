<x-filament::modal id="pos-cash-movement" width="md">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.cash-movement.heading') }}
    </x-slot>

    <div class="grid grid-cols-2 gap-2">
        <button type="button" class="pos-key pos-key--mode" aria-pressed="{{ $cashMovementType === 'in' ? 'true' : 'false' }}" wire:click="$set('cashMovementType', 'in')">
            {{ __('point-of-sale::filament/pos/pages/terminal.cash-movement.in') }}
        </button>

        <button type="button" class="pos-key pos-key--mode" aria-pressed="{{ $cashMovementType === 'out' ? 'true' : 'false' }}" wire:click="$set('cashMovementType', 'out')">
            {{ __('point-of-sale::filament/pos/pages/terminal.cash-movement.out') }}
        </button>
    </div>

    <x-filament::input.wrapper>
        <x-filament::input type="number" step="0.01" wire:model="cashMovementAmount" :placeholder="__('point-of-sale::filament/pos/pages/terminal.cash-movement.amount')" />
    </x-filament::input.wrapper>

    <x-filament::input.wrapper>
        <x-filament::input type="text" wire:model="cashMovementReason" :placeholder="__('point-of-sale::filament/pos/pages/terminal.cash-movement.reason')" />
    </x-filament::input.wrapper>

    <x-filament::button size="lg" wire:click="recordCashMovement">
        {{ __('point-of-sale::filament/pos/pages/terminal.cash-movement.confirm') }}
    </x-filament::button>
</x-filament::modal>
