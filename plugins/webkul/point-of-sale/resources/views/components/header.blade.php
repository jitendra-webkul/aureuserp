@php
    $actions ??= null;
    $nav ??= null;
@endphp

<header class="mb-4 flex flex-none items-center gap-4 border-b border-gray-200 bg-white px-4 py-3 dark:border-white/10 dark:bg-gray-900">
    @if ($nav)
        <div class="min-w-0 flex-auto">
            @include($nav)
        </div>
    @endif

    <div class="flex shrink-0 items-center gap-2">
        @php($cashier = filament()->auth()->user())

        @if ($cashier)
            <div class="inline-flex flex-none items-center gap-2 pe-1">
                <x-filament::avatar
                    size="sm"
                    :src="filament()->getUserAvatarUrl($cashier)"
                    :alt="$cashier->name"
                />

                <span class="whitespace-nowrap text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $cashier->name }}</span>
            </div>
        @endif

        @isset($actions)
            @include($actions)
        @endisset
    </div>
</header>
