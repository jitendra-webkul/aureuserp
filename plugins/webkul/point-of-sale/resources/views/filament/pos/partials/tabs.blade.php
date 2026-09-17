<div class="pos-tabs">
    <button
        type="button"
        class="pos-tab pos-tab--new"
        wire:click="newOrder"
        title="{{ __('point-of-sale::filament/pos/pages/terminal.tabs.new') }}"
        aria-label="{{ __('point-of-sale::filament/pos/pages/terminal.tabs.new') }}"
    >
        <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4" />
    </button>

    @foreach ($this->getDraftOrders() as $draft)
        <button
            type="button"
            @class(['pos-tab', 'pos-tab--active' => $activeOrderId === $draft->getKey()])
            wire:click="switchOrder({{ $draft->getKey() }})"
        >
            {{ $draft->tracking_number }}
        </button>
    @endforeach
</div>
