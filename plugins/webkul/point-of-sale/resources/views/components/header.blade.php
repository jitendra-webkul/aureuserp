@php
    $actions ??= null;
    $nav ??= null;
@endphp

<header class="pos-header">
    @if ($nav)
        <div class="pos-header__nav">
            @include($nav)
        </div>
    @endif

    <div class="flex shrink-0 items-center gap-2">
        @php($cashier = filament()->auth()->user())

        @if ($cashier)
            <div class="pos-cashier">
                <x-filament::avatar
                    size="sm"
                    :src="filament()->getUserAvatarUrl($cashier)"
                    :alt="$cashier->name"
                />

                <span class="pos-cashier__name">{{ $cashier->name }}</span>
            </div>
        @endif

        @isset($actions)
            @include($actions)
        @endisset
    </div>
</header>
