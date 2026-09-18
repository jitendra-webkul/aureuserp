<x-filament::modal
    id="pos-parked-orders"
    width="lg"
    :heading="__('point-of-sale::filament/pos/pages/terminal.parked.heading')"
>
    @php($parked = $this->parkedSummaries())

    <div class="flex flex-col gap-4">
        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
            <x-filament::input
                type="search"
                wire:model.live.debounce.300ms="parkedSearch"
                :placeholder="__('point-of-sale::filament/pos/pages/terminal.parked.search')"
            />
        </x-filament::input.wrapper>

        @forelse ($parked as $draft)
            <div @class([
                'pos-parked-row',
                'pos-parked-row--active' => $draft['is_active'],
                'pos-parked-row--stale' => $draft['is_stale'] && ! $draft['is_active'],
            ])>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $draft['label'] }}
                    </p>

                    <p class="pos-figure truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ trans_choice('point-of-sale::filament/pos/pages/terminal.parked.items', $draft['quantity'], ['count' => $draft['quantity'] + 0]) }}
                        &middot;
                        {{ __('point-of-sale::filament/pos/pages/terminal.parked.parked-ago', ['time' => $draft['parked']]) }}
                    </p>
                </div>

                <span class="pos-figure shrink-0 text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $this->money($draft['total']) }}
                </span>

                @if ($draft['is_active'])
                    <x-filament::badge color="primary">
                        {{ __('point-of-sale::filament/pos/pages/terminal.parked.open') }}
                    </x-filament::badge>
                @elseif ($draft['is_stale'])
                    <x-filament::button
                        size="sm"
                        color="warning"
                        outlined
                        wire:click="discardOrder({{ $draft['id'] }})"
                    >
                        {{ __('point-of-sale::filament/pos/pages/terminal.parked.discard') }}
                    </x-filament::button>
                @else
                    <x-filament::button
                        size="sm"
                        color="gray"
                        outlined
                        wire:click="switchOrder({{ $draft['id'] }})"
                    >
                        {{ __('point-of-sale::filament/pos/pages/terminal.parked.resume') }}
                    </x-filament::button>
                @endif
            </div>
        @empty
            <x-filament::empty-state
                icon="heroicon-o-inbox"
                :heading="__('point-of-sale::filament/pos/pages/terminal.parked.empty.heading')"
                :description="__('point-of-sale::filament/pos/pages/terminal.parked.empty.description')"
            />
        @endforelse
    </div>
</x-filament::modal>
