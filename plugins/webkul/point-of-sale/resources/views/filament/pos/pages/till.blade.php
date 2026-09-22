<x-filament-panels::page>
    @unless ($this->needsOpeningControl())
        @vite('plugins/webkul/point-of-sale/resources/js/till/main.js')

        <div
            id="pos-till"
            wire:ignore
            data-boot="{{ json_encode($this->bootPayload()) }}"
            data-sync-endpoint="{{ $this->syncEndpoint() }}"
            data-access-token="{{ $this->config->access_token }}"
        ></div>
    @endunless

    @include('point-of-sale::filament.pos.partials.modals.opening-control')
    @include('point-of-sale::filament.pos.partials.modals.closing')
    @include('point-of-sale::filament.pos.partials.modals.cash-movement')
    @include('point-of-sale::filament.pos.partials.modals.money-details')
</x-filament-panels::page>
