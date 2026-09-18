<div class="pos-panes">
    <div class="pos-pane pos-pane--catalogue">
        @include('point-of-sale::filament.pos.partials.panes.screens')
    </div>

    <div class="pos-pane pos-pane--order">
        @if ($screen === 'payment')
            @include('point-of-sale::filament.pos.partials.panes.payment-sidebar')
        @else
            @include('point-of-sale::filament.pos.partials.panes.cart')

            @include('point-of-sale::filament.pos.partials.panes.keypad')
        @endif
    </div>
</div>
