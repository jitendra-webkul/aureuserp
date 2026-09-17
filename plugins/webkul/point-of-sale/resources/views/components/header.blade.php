@php
    $actions ??= null;
    $tabs ??= null;
@endphp

<header class="pos-header">
    @if ($tabs)
        <div class="pos-header__tabs">
            @include($tabs)
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
