@if ($screen === 'products')
    <div class="flex flex-none items-center gap-2">
        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass" class="flex-1">
            <x-filament::input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="{{ __('point-of-sale::filament/pos/pages/terminal.catalogue.search') }}"
            />
        </x-filament::input.wrapper>

        <x-filament::button
            icon="heroicon-m-plus"
            wire:click="openProductForm"
            class="shrink-0"
            :tooltip="__('point-of-sale::filament/pos/pages/terminal.menu.create-product')"
        >
            <span class="sr-only">
                {{ __('point-of-sale::filament/pos/pages/terminal.menu.create-product') }}
            </span>
        </x-filament::button>
    </div>

    @php($categories = $this->getCategories())

    @if ($categories->isNotEmpty())
        <div class="flex shrink-0 flex-wrap gap-2">
            @foreach ($categories as $category)
                @php($categoryImage = $config->show_category_images ? $this->categoryImageUrl($category) : null)

                <button
                    type="button"
                    @class([
                        'inline-flex min-h-10 flex-none items-center gap-2 whitespace-nowrap rounded-lg border px-3.5 py-1.5 text-sm font-semibold transition-colors',
                        'border-primary-600 bg-primary-600 text-white' => $selectedCategoryId === $category->getKey(),
                        'border-gray-200 bg-white text-gray-950 hover:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white' => $selectedCategoryId !== $category->getKey(),
                    ])
                    wire:click="selectCategory({{ $category->getKey() }})"
                >
                    @if ($categoryImage)
                        <img src="{{ $categoryImage }}" alt="{{ $category->name }}" class="size-6 flex-none rounded object-cover" />
                    @endif

                    <span>{{ $category->name }}</span>
                </button>
            @endforeach
        </div>
    @endif

    @php($products = $this->getProducts())

    @if ($products->isEmpty())
        <x-filament::empty-state
            icon="heroicon-o-cube"
            :heading="__('point-of-sale::filament/pos/pages/terminal.catalogue.empty.heading')"
            :description="__('point-of-sale::filament/pos/pages/terminal.catalogue.empty.description')"
        />
    @else
        @php($cartQuantities = $this->cartQuantityByProduct())
        @php($stockLevels = $this->stockLevels())

        <div class="grid min-h-0 flex-auto auto-rows-auto grid-cols-[repeat(2,minmax(0,1fr))] content-start items-stretch gap-3 overflow-y-auto md:grid-cols-[repeat(3,minmax(0,1fr))] xl:grid-cols-[repeat(5,minmax(0,1fr))]">
            @foreach ($products as $product)
                <div class="relative flex flex-col overflow-hidden rounded-[0.875rem] border border-gray-200 bg-white text-left shadow-[0_1px_2px_rgb(0_0_0/0.04)] transition-[border-color,box-shadow,transform] duration-150 hover:-translate-y-0.5 hover:border-primary-500 hover:shadow-[0_6px_16px_rgb(0_0_0/0.08)] dark:border-gray-700 dark:bg-gray-900">
                    <button
                        type="button"
                        class="absolute right-0 top-0 z-[1] flex size-6 items-center justify-center rounded-bl-lg bg-gray-950/45 text-white"
                        wire:click="openProductInfo({{ $product->id }})"
                        title="{{ __('point-of-sale::filament/pos/pages/terminal.product-info.heading') }}"
                    >
                        <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        wire:click="selectProduct({{ $product->id }})"
                        class="flex min-h-0 flex-auto flex-col text-left"
                    >
                    @php($free = $stockLevels[$product->id] ?? null)

                    @if ($product->is_storable && $free !== null)
                        <span @class([
                            'absolute left-1.5 top-1.5 z-[1] rounded-md px-2 py-0.5 text-[0.6875rem] font-semibold uppercase leading-tight tracking-wide text-white shadow-sm',
                            'bg-danger-600' => $free <= 0,
                            'bg-warning-600' => $free > 0 && $free <= 5,
                            'bg-success-600' => $free > 5,
                        ])>
                            @if ($free <= 0)
                                {{ __('point-of-sale::filament/pos/pages/terminal.catalogue.stock.out') }}
                            @elseif ($free <= 5)
                                {{ __('point-of-sale::filament/pos/pages/terminal.catalogue.stock.low', ['quantity' => $free + 0]) }}
                            @else
                                {{ __('point-of-sale::filament/pos/pages/terminal.catalogue.stock.available') }}
                            @endif
                        </span>
                    @endif

                    <div @class([
                        'flex h-36 w-full flex-none items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-800',
                        'opacity-40 grayscale' => $product->is_storable && $free !== null && $free <= 0,
                    ])>
                        @if ($config->show_product_images && filled($product->images))
                            <img
                                src="{{ $this->imageUrl($product) }}"
                                alt="{{ $product->name }}"
                                class="size-full object-cover"
                            />
                        @else
                            <x-filament::icon
                                icon="heroicon-o-cube"
                                class="h-8 w-8 text-gray-300 dark:text-gray-600"
                            />
                        @endif
                    </div>

                    <div class="flex flex-auto flex-col gap-1 px-3 pb-3 pt-2.5">
                        <span class="line-clamp-2 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $product->name }}
                        </span>

                        <div class="mt-auto flex min-h-6 items-end justify-between gap-2">
                            <span class="font-mono text-[0.9375rem] font-bold leading-tight tabular-nums text-primary-600 dark:text-primary-400">
                                {{ $this->money($this->displayUnitPrice($product->id, (float) $product->price)) }}
                            </span>

                            @if (($cartQuantities[$product->id] ?? 0) > 0)
                                <span class="text-2xl font-bold leading-none text-gray-400 dark:text-gray-500">{{ $cartQuantities[$product->id] + 0 }}</span>
                            @endif
                        </div>
                    </div>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
@elseif ($screen === 'payment')
    @include('point-of-sale::filament.pos.partials.panes.payment')
@else
    <div class="flex flex-col items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
        @if ($lastOrder)
            <div class="flex w-full max-w-sm flex-col gap-2 rounded-lg border border-gray-200 p-4 font-mono text-xs dark:border-white/10">
                <p class="text-center text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $config->name }}
                </p>

                <p class="text-center text-gray-500 dark:text-gray-400">{{ $lastOrder['name'] }}</p>

                @if (filled($lastOrder['header'] ?? null))
                    <p class="whitespace-pre-line text-center text-gray-500 dark:text-gray-400">{{ $lastOrder['header'] }}</p>
                @endif

                <div class="border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                @foreach ($lastOrder['lines'] as $line)
                    <div class="flex justify-between gap-3">
                        <span class="truncate text-gray-950 dark:text-white">{{ $line['qty'] }} &times; {{ $line['name'] }}</span>
                        <span class="tabular-nums text-gray-950 dark:text-white">{{ $this->money($line['total']) }}</span>
                    </div>
                @endforeach

                <div class="border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                <div class="flex justify-between gap-3 text-sm font-semibold">
                    <span class="text-gray-950 dark:text-white">{{ __('point-of-sale::filament/pos/pages/terminal.receipt.total') }}</span>
                    <span class="tabular-nums text-gray-950 dark:text-white">{{ $this->money($lastOrder['amount_total']) }}</span>
                </div>

                <div class="flex justify-between gap-3">
                    <span class="text-gray-500 dark:text-gray-400">{{ __('point-of-sale::filament/pos/pages/terminal.receipt.change') }}</span>
                    <span class="tabular-nums text-gray-950 dark:text-white">{{ $this->money($lastOrder['amount_return']) }}</span>
                </div>

                @if (filled($lastOrder['footer'] ?? null))
                    <div class="border-t border-dashed border-gray-300 dark:border-gray-600"></div>

                    <p class="whitespace-pre-line text-center text-gray-500 dark:text-gray-400">{{ $lastOrder['footer'] }}</p>
                @endif
            </div>
        @endif

        <x-filament::button size="lg" icon="heroicon-o-plus" wire:click="newOrder">
            {{ __('point-of-sale::filament/pos/pages/terminal.actions.new-order') }}
        </x-filament::button>
    </div>
@endif
