<div class="flex min-h-0 flex-auto flex-col gap-2">
    <div class="flex min-h-0 flex-auto flex-col gap-2 overflow-y-auto">
        @foreach ($this->getPaymentMethods() as $method)
            <button
                type="button"
                class="flex min-h-14 items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 text-start text-base font-medium text-gray-950 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-800"
                wire:click="addPayment({{ $method->id }})"
            >
                <x-filament::icon :icon="$method->type->getIcon()" class="h-6 w-6 text-gray-500 dark:text-gray-400" />

                <span>{{ $method->name }}</span>
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-2 gap-2">
        <button
            type="button"
            @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => filled($this->selectedCustomerName())])
            wire:click="openCustomers"
        >
            <x-filament::icon icon="heroicon-o-user" class="me-2 h-5 w-5" />

            {{ $this->selectedCustomerName() ?? __('point-of-sale::filament/pos/pages/terminal.actions.customer') }}
        </button>

        <button
            type="button"
            @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => $toInvoice])
            wire:click="toggleToInvoice"
        >
            <x-filament::icon icon="heroicon-o-document-text" class="me-2 h-5 w-5" />

            <span class="me-auto">{{ __('point-of-sale::filament/pos/pages/terminal.payment.invoice') }}</span>

            <x-filament::icon
                :icon="$toInvoice ? 'heroicon-m-check-circle' : 'heroicon-o-stop'"
                @class(['h-5 w-5', 'text-primary-600' => $toInvoice, 'text-gray-400' => ! $toInvoice])
            />
        </button>

        @if ($config->enable_price_list && $this->availablePriceLists()->isNotEmpty())
            <button
                type="button"
                @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => filled($priceListId)])
                wire:click="openPriceLists"
            >
                <x-filament::icon icon="heroicon-o-tag" class="me-2 h-5 w-5" />

                {{ $this->selectedPriceListName() ?? __('point-of-sale::filament/pos/pages/terminal.price-lists.label') }}
            </button>
        @endif

        @if ($config->enable_tip && $config->tip_product_id)
            <button
                type="button"
                @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => $this->tipAmount() > 0])
                wire:click="addTip({{ max(0, $this->changeDue()) }})"
            >
                <x-filament::icon icon="heroicon-o-banknotes" class="me-2 h-5 w-5" />

                <span class="me-auto">{{ __('point-of-sale::filament/pos/pages/terminal.actions.tip') }}</span>

                @if ($this->tipAmount() > 0)
                    <span class="font-mono tabular-nums">{{ $this->money($this->tipAmount()) }}</span>
                @endif
            </button>
        @endif

        @if ($config->enable_takeaway)
            <button
                type="button"
                @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => $isTakeaway])
                wire:click="toggleTakeaway"
            >
                <x-filament::icon :icon="$isTakeaway ? 'heroicon-o-shopping-bag' : 'heroicon-o-home'" class="me-2 h-5 w-5" />

                {{ $isTakeaway
                    ? __('point-of-sale::filament/pos/pages/terminal.actions.takeaway')
                    : __('point-of-sale::filament/pos/pages/terminal.actions.dine-in') }}
            </button>
        @endif

        @if ($config->enable_ship_later)
            <label @class(['flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800', 'border-primary-600 bg-primary-50 font-semibold text-primary-700 dark:border-primary-500 dark:bg-primary-500/20 dark:text-primary-300' => filled($shippedAt)])>
                <x-filament::icon icon="heroicon-o-truck" class="me-2 h-5 w-5" />

                <span class="me-auto">{{ __('point-of-sale::filament/pos/pages/terminal.actions.ship-later') }}</span>

                <input
                    type="date"
                    class="bg-transparent text-sm outline-none"
                    wire:model.live="shippedAt"
                />
            </label>
        @endif
    </div>

    <div class="grid grid-cols-4 gap-1.5">
        @foreach ([['1', '2', '3', '+10'], ['4', '5', '6', '+20'], ['7', '8', '9', '+50']] as [$a, $b, $c, $tender])
            @foreach ([$a, $b, $c] as $digit)
                <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800" wire:click="pressPaymentNumpad('{{ $digit }}')">
                    {{ $digit }}
                </button>
            @endforeach

            <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800 px-3" wire:click="pressPaymentNumpad('{{ $tender }}')">
                {{ $tender }}
            </button>
        @endforeach

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800 px-3" wire:click="pressPaymentNumpad('-')">
            +/-
        </button>

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800" wire:click="pressPaymentNumpad('0')">0</button>

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800 px-3" wire:click="pressPaymentNumpad('.')">.</button>

        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-lg font-medium text-gray-950 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800 px-3" wire:click="pressPaymentNumpad('backspace')">
            <x-filament::icon icon="heroicon-m-backspace" class="h-5 w-5" />
        </button>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <button type="button" class="flex min-h-13 items-center justify-center rounded-lg border border-gray-200 bg-white text-[0.9375rem] font-medium text-gray-600 transition-colors hover:bg-gray-50 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" wire:click="goToProducts">
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.back') }}
        </button>

        <button
            type="button"
            class="flex min-h-14 flex-none items-center justify-center gap-2 rounded-lg bg-primary-600 text-base font-semibold text-white transition-colors hover:bg-primary-700 disabled:bg-gray-200 disabled:text-gray-400"
            x-on:click="validate()"
            @disabled($this->hasRemainingDue())
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.validate') }}
        </button>
    </div>
</div>
