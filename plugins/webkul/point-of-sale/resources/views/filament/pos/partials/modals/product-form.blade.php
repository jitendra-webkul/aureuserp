<x-filament::modal id="pos-product-form" width="lg">
    <x-slot name="heading">
        {{ __('point-of-sale::filament/pos/pages/terminal.product-form.heading') }}
    </x-slot>

    <x-filament::input.wrapper>
        <x-filament::input type="text" wire:model="newProduct.name" :placeholder="__('point-of-sale::filament/pos/pages/terminal.product-form.name')" />
    </x-filament::input.wrapper>

    <x-filament::input.wrapper>
        <x-filament::input type="text" wire:model="newProduct.barcode" :placeholder="__('point-of-sale::filament/pos/pages/terminal.product-form.barcode')" />
    </x-filament::input.wrapper>

    <x-filament::input.wrapper>
        <x-filament::input type="number" step="0.01" wire:model="newProduct.price" :placeholder="__('point-of-sale::filament/pos/pages/terminal.product-form.price')" />
    </x-filament::input.wrapper>

    <x-filament::input.wrapper>
        <x-filament::input.select wire:model="newProduct.category_id">
            <option value="">{{ __('point-of-sale::filament/pos/pages/terminal.product-form.category') }}</option>

            @foreach ($this->getCategories() as $category)
                <option value="{{ $category->getKey() }}">{{ $category->name }}</option>
            @endforeach
        </x-filament::input.select>
    </x-filament::input.wrapper>

    <x-filament::button size="lg" wire:click="createProduct">
        {{ __('point-of-sale::filament/pos/pages/terminal.product-form.confirm') }}
    </x-filament::button>
</x-filament::modal>
