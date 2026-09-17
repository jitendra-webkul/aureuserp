<div class="pos-panes">
    <div class="pos-pane">
        @if ($screen === 'payment')
            @include('point-of-sale::filament.pos.partials.panes.payment-sidebar')
        @else
            @include('point-of-sale::filament.pos.partials.panes.cart')

            @include('point-of-sale::filament.pos.partials.panes.keypad')
        @endif
    </div>

    <div class="pos-pane">
        @include('point-of-sale::filament.pos.partials.panes.screens')
    </div>
</div>
