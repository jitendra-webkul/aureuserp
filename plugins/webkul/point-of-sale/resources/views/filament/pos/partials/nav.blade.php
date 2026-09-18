<x-filament::tabs contained>
    <x-filament::tabs.item
        icon="heroicon-m-clipboard-document-list"
        :badge="($count = $this->getSessionOrders()->count()) > 0 ? $count : null"
        badge-color="primary"
        wire:click="goToOrders"
    >
        {{ __('point-of-sale::filament/pos/pages/terminal.menu.orders') }}
    </x-filament::tabs.item>
</x-filament::tabs>
