<div class="flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-6 text-[2.75rem] font-semibold tabular-nums text-success-600 dark:border-gray-800 dark:bg-gray-900">
    {{ $this->money($this->cartTotal()) }}
</div>

<div class="flex min-h-0 flex-auto flex-col gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
    @if (empty($payments))
        <p class="m-auto text-lg text-gray-500 dark:text-gray-400">
            {{ __('point-of-sale::filament/pos/pages/terminal.payment.select-method') }}
        </p>
    @else
        <div @class([
            'flex items-center justify-between rounded-xl border px-4 py-3 text-xl font-semibold',
            'border-danger-300 bg-danger-50 text-danger-700' => $this->hasRemainingDue(),
            'border-success-300 bg-success-50 text-success-700' => ! $this->hasRemainingDue(),
        ])>
            <span>
                {{ $this->hasRemainingDue()
                    ? __('point-of-sale::filament/pos/pages/terminal.payment.remaining')
                    : __('point-of-sale::filament/pos/pages/terminal.payment.change') }}
            </span>

            <span class="font-mono tabular-nums">
                {{ $this->money($this->hasRemainingDue() ? $this->remainingDue() : $this->changeDue()) }}
            </span>
        </div>

        <div class="flex min-h-0 flex-col gap-2 overflow-y-auto">
            @foreach ($payments as $index => $payment)
                <div @class([
                    'flex items-center gap-1 rounded-xl border pe-2 dark:border-gray-800',
                    'border-primary-600 bg-gray-50' => $selectedPaymentIndex === $index,
                    'border-gray-200' => $selectedPaymentIndex !== $index,
                ])>
                    <button type="button" class="flex flex-auto items-center justify-between gap-4 px-4 py-3.5 text-lg text-gray-950 dark:text-white" wire:click="selectPayment({{ $index }})">
                        <span>{{ $payment['name'] }}</span>

                        <span class="font-mono tabular-nums">{{ $this->money($payment['amount']) }}</span>
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
