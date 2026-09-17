<x-filament::modal id="pos-money-details" width="xl">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.money-details.label') }}
    </x-slot>

    @foreach ($this->getBills() as $bill)
        @php($key = (string) (float) $bill->value)

        <div class="flex items-center gap-2">
            <x-filament::icon-button
                icon="heroicon-o-minus"
                color="gray"
                wire:click="stepMoneyDetail('{{ $key }}', -1)"
                :label="__('point-of-sale::filament/pos/pages/terminal.money-details.decrease')"
            />

            <x-filament::input.wrapper class="w-20">
                <x-filament::input
                    type="number"
                    min="0"
                    wire:model.live="moneyDetails.{{ $key }}"
                />
            </x-filament::input.wrapper>

            <x-filament::icon-button
                icon="heroicon-o-plus"
                color="gray"
                wire:click="stepMoneyDetail('{{ $key }}', 1)"
                :label="__('point-of-sale::filament/pos/pages/terminal.money-details.increase')"
            />

            <span class="pos-figure text-sm text-gray-950 dark:text-white">
                {{ $this->money((float) $bill->value) }}
            </span>
        </div>
    @endforeach

    <x-slot name="footerActions">
        <x-filament::button wire:click="confirmMoneyDetails" size="lg">
            {{ __('point-of-sale::filament/pos/pages/terminal.money-details.confirm') }}
        </x-filament::button>

        <p class="pos-figure text-lg font-semibold text-gray-950 dark:text-white">
            {{ __('point-of-sale::filament/pos/pages/terminal.money-details.total', ['total' => $this->money($this->moneyDetailsTotal())]) }}
        </p>
    </x-slot>
</x-filament::modal>
