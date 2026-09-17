<x-filament-panels::page>
    <div
        x-data="{
            pending: @js($pendingSyncCount),
            online: navigator.onLine,
            endpoint: @js($this->syncEndpoint()),
            init() {
                window.addEventListener('online', () => { this.online = true })
                window.addEventListener('offline', () => { this.online = false })
                window.addEventListener('point-of-sale:queue-flushed', (event) => {
                    this.pending = event.detail.pending
                })

                if (window.pointOfSaleQueue) {
                    this.pending = window.pointOfSaleQueue.size()
                    window.pointOfSaleQueue.watch(this.endpoint)
                }
            },
            async validate() {
                if (this.online) {
                    $wire.validateOrder()

                    return
                }

                const payload = await $wire.queueOrderPayload()

                const pending = window.pointOfSaleQueue.push(payload)

                this.pending = pending

                $wire.markQueued(pending)
            },
        }"
        class="pos-terminal"
    >
        @include('point-of-sale::components.header', [
            'tabs' => 'point-of-sale::filament.pos.partials.tabs',
            'actions' => 'point-of-sale::filament.pos.partials.menu-button',
        ])

        <div class="mb-3 flex flex-wrap items-center gap-2" x-cloak x-show="! online || pending > 0">
            <x-filament::badge color="warning" icon="heroicon-o-signal-slash" x-show="! online">
                {{ __('point-of-sale::filament/pos/pages/terminal.offline.offline') }}
            </x-filament::badge>

            <x-filament::badge color="info" icon="heroicon-o-arrow-path" x-show="pending > 0">
                <span x-text="@js(__('point-of-sale::filament/pos/pages/terminal.offline.pending', ['count' => ':count'])).replace(':count', pending)"></span>
            </x-filament::badge>
        </div>

        @include('point-of-sale::filament.pos.partials.modals.opening-control')
        @include('point-of-sale::filament.pos.partials.modals.customers')
        @include('point-of-sale::filament.pos.partials.modals.notes')
        @include('point-of-sale::filament.pos.partials.modals.cash-movement')
        @include('point-of-sale::filament.pos.partials.modals.product-form')
        @include('point-of-sale::filament.pos.partials.modals.product-info')
        @include('point-of-sale::filament.pos.partials.modals.variants')
        @include('point-of-sale::filament.pos.partials.modals.orders')
        @include('point-of-sale::filament.pos.partials.modals.closing')
        @include('point-of-sale::filament.pos.partials.modals.money-details')

        @unless ($this->needsOpeningControl())
            @include('point-of-sale::filament.pos.partials.panes')
        @endunless

    </div>
</x-filament-panels::page>
