<x-filament::modal id="pos-variants" width="lg">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.variants.heading') }}
    </x-slot>

    @if ($variantProductId)
        @php($variant = $this->resolvedVariant())

        <x-filament::section compact>
            <div class="flex items-center justify-between gap-4">
                <span class="font-semibold text-gray-950 dark:text-white">
                    {{ $variant?->name ?? $this->configurableProductName() }}
                </span>

                <span class="font-mono tabular-nums font-semibold text-gray-950 dark:text-white">
                    {{ $variant ? $this->money($variant->price) : '—' }}
                </span>
            </div>
        </x-filament::section>

        @unless ($variant)
            <x-filament::badge color="warning">
                {{ __('point-of-sale::filament/pos/pages/terminal.variants.unavailable') }}
            </x-filament::badge>
        @endunless

        <div class="flex flex-col gap-4">
            @foreach ($this->variantAttributes() as $attribute)
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $attribute['name'] }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($attribute['options'] as $option)
                            <x-filament::badge
                                tag="button"
                                size="lg"
                                :color="($variantSelection[$attribute['id']] ?? null) === $option->attribute_option_id ? 'primary' : 'gray'"
                                wire:click="selectVariantOption({{ $attribute['id'] }}, {{ $option->attribute_option_id }})"
                            >
                                {{ $option->attributeOption?->name }}

                                @if ((float) $option->extra_price > 0)
                                    &nbsp;+{{ $this->money($option->extra_price) }}
                                @endif
                            </x-filament::badge>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <x-slot name="footerActions">
        <x-filament::button wire:click="confirmVariant" :disabled="! $this->resolvedVariant()">
            {{ __('point-of-sale::filament/pos/pages/terminal.variants.confirm') }}
        </x-filament::button>

        <x-filament::button color="gray" wire:click="discardVariant">
            {{ __('point-of-sale::filament/pos/pages/terminal.variants.discard') }}
        </x-filament::button>
    </x-slot>
</x-filament::modal>
