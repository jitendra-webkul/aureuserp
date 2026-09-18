@php($drafts = $this->draftSummaries())
@php($visible = $drafts->take(3))

<div class="pos-orders-bar">
    @foreach ($visible as $draft)
        <button
            type="button"
            @class(['pos-order-card', 'pos-order-card--active' => $draft['is_active']])
            wire:click="switchOrder({{ $draft['id'] }})"
        >
            <span class="pos-order-card__name">
                @if ($draft['is_active'])
                    <span class="pos-order-card__dot"></span>
                @endif

                {{ $draft['label'] }}
            </span>

            <span class="pos-order-card__meta pos-figure">
                {{ trans_choice('point-of-sale::filament/pos/pages/terminal.parked.items', $draft['quantity'], ['count' => $draft['quantity'] + 0]) }}
                &middot;
                {{ $this->money($draft['total']) }}
            </span>
        </button>
    @endforeach

    @if ($drafts->count() > $visible->count())
        <button type="button" class="pos-order-card pos-order-card--more" wire:click="openParkedOrders">
            <span class="pos-order-card__name">
                {{ __('point-of-sale::filament/pos/pages/terminal.parked.count', ['count' => $drafts->count()]) }}
            </span>

            <span class="pos-order-card__meta">
                {{ __('point-of-sale::filament/pos/pages/terminal.parked.view-all') }}
            </span>
        </button>
    @endif

    <button
        type="button"
        class="pos-order-card pos-order-card--new"
        wire:click="newOrder"
        title="{{ __('point-of-sale::filament/pos/pages/terminal.tabs.new') }}"
        aria-label="{{ __('point-of-sale::filament/pos/pages/terminal.tabs.new') }}"
    >
        <x-filament::icon icon="heroicon-m-plus" class="h-5 w-5" />
    </button>
</div>
