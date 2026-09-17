<x-filament::dropdown placement="bottom-end" width="xs">
    <x-slot name="trigger">
        <x-filament::icon-button
            icon="heroicon-o-bars-3"
            color="gray"
            size="lg"
            :label="__('point-of-sale::filament/pos/pages/terminal.menu.label')"
        />
    </x-slot>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item icon="heroicon-m-receipt-percent" wire:click="goToOrders" :badge="$this->getSessionOrders()->count()">
            {{ __('point-of-sale::filament/pos/pages/terminal.menu.orders') }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item icon="heroicon-m-banknotes" wire:click="openCashMovement('in')">
            {{ __('point-of-sale::filament/pos/pages/terminal.menu.cash-in-out') }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item icon="heroicon-m-plus-circle" wire:click="openProductForm">
            {{ __('point-of-sale::filament/pos/pages/terminal.menu.create-product') }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item icon="heroicon-m-arrow-top-right-on-square" :href="$this->backUrl()" tag="a">
            {{ __('point-of-sale::filament/pos/pages/terminal.menu.back-office') }}
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item icon="heroicon-m-lock-closed" wire:click="goToClosing">
            {{ __('point-of-sale::filament/pos/pages/terminal.menu.close-register') }}
        </x-filament::dropdown.list.item>

        <form method="POST" action="{{ filament()->getLogoutUrl() }}">
            @csrf

            <x-filament::dropdown.list.item icon="heroicon-m-arrow-right-start-on-rectangle" type="submit">
                {{ __('point-of-sale::components/header.logout') }}
            </x-filament::dropdown.list.item>
        </form>
    </x-filament::dropdown.list>
</x-filament::dropdown>
