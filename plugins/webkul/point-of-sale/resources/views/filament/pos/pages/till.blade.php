<x-filament-panels::page>
    @vite('plugins/webkul/point-of-sale/resources/js/till/main.js')

    <div
        id="pos-till"
        wire:ignore
        data-boot="{{ json_encode($this->bootPayload()) }}"
        data-sync-endpoint="{{ $this->syncEndpoint() }}"
        data-access-token="{{ $this->config->access_token }}"
    ></div>

    <livewire:point-of-sale-register-controls :session="$session" />
</x-filament-panels::page>
