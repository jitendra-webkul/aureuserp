@if ($screen === 'products')
    <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass" class="shrink-0">
        <x-filament::input
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="{{ __('point-of-sale::filament/pos/pages/terminal.catalogue.search') }}"
        />
    </x-filament::input.wrapper>

    @php($categories = $this->getCategories())

    @if ($categories->isNotEmpty())
        <div class="flex shrink-0 flex-wrap gap-2">
            @foreach ($categories as $category)
                @php($categoryImage = $config->show_category_images ? $this->categoryImageUrl($category) : null)

                <button
                    type="button"
                    @class([
                        'pos-category',
                        'pos-category--active' => $selectedCategoryId === $category->getKey(),
                    ])
                    wire:click="selectCategory({{ $category->getKey() }})"
                >
                    @if ($categoryImage)
                        <img src="{{ $categoryImage }}" alt="{{ $category->name }}" class="pos-category__image" />
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

        <div class="pos-products">
            @foreach ($products as $product)
                <div class="pos-tile">
                    <button
                        type="button"
                        class="pos-tile__info"
                        wire:click="openProductInfo({{ $product->id }})"
                        title="{{ __('point-of-sale::filament/pos/pages/terminal.product-info.heading') }}"
                    >
                        <x-filament::icon icon="heroicon-m-information-circle" class="h-4 w-4" />
                    </button>

                    <button
                        type="button"
                        wire:click="selectProduct({{ $product->id }})"
                        class="pos-tile__add"
                    >
                    <div class="pos-tile__media">
                        @if ($config->show_product_images && filled($product->images))
                            <img
                                src="{{ $this->imageUrl($product) }}"
                                alt="{{ $product->name }}"
                            />
                        @else
                            <x-filament::icon
                                icon="heroicon-o-cube"
                                class="h-8 w-8 text-gray-300 dark:text-gray-600"
                            />
                        @endif
                    </div>

                    <div class="pos-tile__body">
                        <span class="pos-tile__name line-clamp-2 text-sm font-medium text-gray-950 dark:text-white">
                            {{ $product->name }}
                        </span>

                        <div class="pos-tile__foot">
                            <span class="pos-figure text-xs text-gray-500 dark:text-gray-400">
                                {{ $this->money($product->price) }}
                            </span>

                            @if (($cartQuantities[$product->id] ?? 0) > 0)
                                <span class="pos-tile__qty">{{ $cartQuantities[$product->id] + 0 }}</span>
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
