<div class="grid h-full min-h-0 flex-auto grid-cols-[1fr] grid-rows-[minmax(0,1fr)] gap-4 p-4 lg:grid-cols-[1fr_minmax(22rem,32%)]">
    <div class="flex min-h-0 flex-col gap-3 overflow-hidden">
        @include('point-of-sale::filament.pos.partials.panes.screens')
    </div>

    <div class="flex min-h-0 flex-col gap-3 overflow-hidden">
        @include('point-of-sale::filament.pos.partials.tabs')

        @if ($screen === 'payment')
            @include('point-of-sale::filament.pos.partials.panes.payment-sidebar')
        @else
            @include('point-of-sale::filament.pos.partials.panes.cart')

            @include('point-of-sale::filament.pos.partials.panes.keypad')
        @endif
    </div>
</div>
