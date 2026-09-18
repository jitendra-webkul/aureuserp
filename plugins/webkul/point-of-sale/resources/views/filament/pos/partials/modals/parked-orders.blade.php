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
                'flex items-center gap-3 rounded-[0.625rem] border px-3 py-2.5',
                'border-primary-600 bg-primary-50 dark:border-primary-500 dark:bg-primary-500/15' => $draft['is_active'],
                'border-warning-300 bg-warning-500/10 dark:border-warning-600 dark:bg-warning-500/15' => $draft['is_stale'] && ! $draft['is_active'],
                'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => ! $draft['is_active'] && ! $draft['is_stale'],
            ])>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $draft['label'] }}
                    </p>

                    <p class="truncate font-mono text-xs tabular-nums text-gray-500 dark:text-gray-400">
                        {{ trans_choice('point-of-sale::filament/pos/pages/terminal.parked.items', $draft['quantity'], ['count' => $draft['quantity'] + 0]) }}
                        &middot;
                        {{ __('point-of-sale::filament/pos/pages/terminal.parked.parked-ago', ['time' => $draft['parked']]) }}
                    </p>
                </div>

                <span class="shrink-0 font-mono text-sm tabular-nums font-semibold text-gray-950 dark:text-white">
                    {{ $this->money($draft['total']) }}
                </span>

                <div class="flex shrink-0 items-center gap-2">
                    @if ($draft['is_active'])
                        <x-filament::badge color="primary">
                            {{ __('point-of-sale::filament/pos/pages/terminal.parked.open') }}
                        </x-filament::badge>
                    @else
                        <x-filament::button
                            size="sm"
                            :color="$draft['is_stale'] ? 'warning' : 'gray'"
                            outlined
                            wire:click="switchOrder({{ $draft['id'] }})"
                        >
                            {{ __('point-of-sale::filament/pos/pages/terminal.parked.resume') }}
                        </x-filament::button>
                    @endif

                    {{ ($this->discardOrderAction)(['order' => $draft['id']]) }}
                </div>
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
