<div class="pos-payment-side">
    <div class="pos-payment-methods">
        @foreach ($this->getPaymentMethods() as $method)
            <button
                type="button"
                class="pos-payment-method"
                wire:click="addPayment({{ $method->id }})"
            >
                <x-filament::icon :icon="$method->type->getIcon()" class="h-6 w-6 text-gray-500 dark:text-gray-400" />

                <span>{{ $method->name }}</span>
            </button>
        @endforeach
    </div>

    <div class="pos-payment-buttons">
        <button
            type="button"
            @class(['pos-key', 'pos-key--muted', 'pos-key--active' => filled($this->selectedCustomerName())])
            wire:click="openCustomers"
        >
            <x-filament::icon icon="heroicon-o-user" class="me-2 h-5 w-5" />

            {{ $this->selectedCustomerName() ?? __('point-of-sale::filament/pos/pages/terminal.actions.customer') }}
        </button>

        <button
            type="button"
            @class(['pos-key', 'pos-key--muted', 'pos-key--active' => $toInvoice])
            wire:click="toggleToInvoice"
        >
            <x-filament::icon icon="heroicon-o-document-text" class="me-2 h-5 w-5" />

            <span class="me-auto">{{ __('point-of-sale::filament/pos/pages/terminal.payment.invoice') }}</span>

            <x-filament::icon
                :icon="$toInvoice ? 'heroicon-m-check-circle' : 'heroicon-o-stop'"
                @class(['h-5 w-5', 'text-primary-600' => $toInvoice, 'text-gray-400' => ! $toInvoice])
            />
        </button>
    </div>

    <div class="pos-keypad">
        @foreach ([['1', '2', '3', '+10'], ['4', '5', '6', '+20'], ['7', '8', '9', '+50']] as [$a, $b, $c, $tender])
            @foreach ([$a, $b, $c] as $digit)
                <button type="button" class="pos-key" wire:click="pressPaymentNumpad('{{ $digit }}')">
                    {{ $digit }}
                </button>
            @endforeach

            <button type="button" class="pos-key pos-key--tender" wire:click="pressPaymentNumpad('{{ $tender }}')">
                {{ $tender }}
            </button>
        @endforeach

        <button type="button" class="pos-key pos-key--sign" wire:click="pressPaymentNumpad('-')">
            +/-
        </button>

        <button type="button" class="pos-key" wire:click="pressPaymentNumpad('0')">0</button>

        <button type="button" class="pos-key pos-key--decimal" wire:click="pressPaymentNumpad('.')">.</button>

        <button type="button" class="pos-key pos-key--backspace" wire:click="pressPaymentNumpad('backspace')">
            <x-filament::icon icon="heroicon-m-backspace" class="h-5 w-5" />
        </button>
    </div>

    <div class="pos-payment-footer">
        <button type="button" class="pos-key pos-key--muted" wire:click="goToProducts">
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.back') }}
        </button>

        <button
            type="button"
            class="pos-pay"
            x-on:click="validate()"
            @disabled($this->hasRemainingDue())
        >
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.validate') }}
        </button>
    </div>
</div>
