<div class="pos-payment-due">
    {{ $this->money($this->cartTotal()) }}
</div>

<div class="pos-payment-summary">
    @if (empty($payments))
        <p class="pos-payment-empty">
            {{ __('point-of-sale::filament/pos/pages/terminal.payment.select-method') }}
        </p>
    @else
        <div @class(['pos-payment-status', 'pos-payment-status--due' => $this->hasRemainingDue()])>
            <span>
                {{ $this->hasRemainingDue()
                    ? __('point-of-sale::filament/pos/pages/terminal.payment.remaining')
                    : __('point-of-sale::filament/pos/pages/terminal.payment.change') }}
            </span>

            <span class="pos-figure">
                {{ $this->money($this->hasRemainingDue() ? $this->remainingDue() : $this->changeDue()) }}
            </span>
        </div>

        <div class="pos-payment-lines">
            @foreach ($payments as $index => $payment)
                <div @class(['pos-payment-line', 'pos-payment-line--active' => $selectedPaymentIndex === $index])>
                    <button type="button" class="pos-payment-line__body" wire:click="selectPayment({{ $index }})">
                        <span>{{ $payment['name'] }}</span>

                        <span class="pos-figure">{{ $this->money($payment['amount']) }}</span>
                    </button>

                    <x-filament::icon-button
                        icon="heroicon-o-x-mark"
                        color="danger"
                        wire:click="removePayment({{ $index }})"
                        :label="__('point-of-sale::filament/pos/pages/terminal.payment.remove')"
                    />
                </div>
            @endforeach
        </div>
    @endif
</div>
