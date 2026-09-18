@php($drafts = $this->draftSummaries())
@php($visible = $drafts->take(3))

<div class="flex flex-none items-stretch gap-2">
    @foreach ($visible as $draft)
        <button
            type="button"
            @class([
                'flex min-w-0 flex-1 flex-col gap-1 rounded-xl border px-2.5 py-2 text-left transition-colors',
                'border-primary-600 bg-primary-50 dark:border-primary-500 dark:bg-primary-500/15' => $draft['is_active'],
                'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900' => ! $draft['is_active'],
            ])
            wire:click="switchOrder({{ $draft['id'] }})"
        >
            <span class="flex items-center gap-1.5 truncate text-[0.8125rem] font-semibold text-gray-950 dark:text-white">
                @if ($draft['is_active'])
                    <span class="size-1.5 flex-none rounded-full bg-primary-600"></span>
                @endif

                {{ $draft['label'] }}
            </span>

            <span @class([
                'truncate font-mono text-[0.6875rem] tabular-nums',
                'text-primary-700 dark:text-primary-300' => $draft['is_active'],
                'text-gray-500 dark:text-gray-400' => ! $draft['is_active'],
            ])>
                {{ trans_choice('point-of-sale::filament/pos/pages/terminal.parked.items', $draft['quantity'], ['count' => $draft['quantity'] + 0]) }}
                &middot;
                {{ $this->money($draft['total']) }}
            </span>
        </button>
    @endforeach

    @if ($drafts->count() > $visible->count())
        <button
            type="button"
            class="flex flex-none flex-col gap-1 rounded-xl border border-gray-200 bg-white px-2.5 py-2 text-left transition-colors hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900"
            wire:click="openParkedOrders"
        >
            <span class="truncate text-[0.8125rem] font-semibold text-gray-950 dark:text-white">
                {{ __('point-of-sale::filament/pos/pages/terminal.parked.count', ['count' => $drafts->count()]) }}
            </span>

            <span class="truncate text-[0.6875rem] text-gray-500 dark:text-gray-400">
                {{ __('point-of-sale::filament/pos/pages/terminal.parked.view-all') }}
            </span>
        </button>
    @elseif ($drafts->isNotEmpty())
        <button
            type="button"
            class="flex w-13 flex-none items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition-colors hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
            wire:click="openParkedOrders"
            title="{{ __('point-of-sale::filament/pos/pages/terminal.parked.heading') }}"
            aria-label="{{ __('point-of-sale::filament/pos/pages/terminal.parked.heading') }}"
        >
            <x-filament::icon icon="heroicon-m-queue-list" class="h-5 w-5" />
        </button>
    @endif

    <button
        type="button"
        class="flex w-13 flex-none items-center justify-center rounded-xl border border-dashed border-gray-200 bg-transparent text-gray-600 transition-colors hover:border-gray-300 dark:border-gray-700 dark:text-gray-300"
        wire:click="newOrder"
        title="{{ __('point-of-sale::filament/pos/pages/terminal.tabs.new') }}"
        aria-label="{{ __('point-of-sale::filament/pos/pages/terminal.tabs.new') }}"
    >
        <x-filament::icon icon="heroicon-m-plus" class="h-5 w-5" />
    </button>
</div>
