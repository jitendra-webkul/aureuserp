<main class="barcode-page adjustment-screen {{ $editingQuantity ? 'is-editing-move' : '' }}" x-data="barcodeScanner('search', 'scan')">
    @include('barcode::components.header.web', [
        'title' => __('barcode::app.adjustments.title'),
        'breadcrumbs' => [
            ['label' => __('barcode::app.title'), 'href' => route('barcode.dashboard')],
            ['label' => __('barcode::app.adjustments.title')],
        ],
        'showBarcode' => $editingQuantity ? null : true,
        'showCancel' => $editingQuantity ? true : null,
        'cancelAction' => 'discardQuantityEdit',
    ])

    @if ($editingQuantity)
        @php
            $productImages = $editingQuantity->product?->images ?? [];
            $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
            $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
            $currentCountedQuantity = $editingQuantity->inventory_quantity_set ? (float) $editingQuantity->counted_quantity : 0.0;
            $remainingToFullCount = max((float) $editingQuantity->quantity - $currentCountedQuantity, 0);
        @endphp

        <section class="move-editor">
            <x-filament::section compact class="editor-summary-section">
                <div class="editor-product">
                    <div class="editor-product-info">
                        <strong>{{ $editingQuantity->location?->full_name ?? $editingQuantity->location?->name }}</strong>
                        <span>{{ $editingQuantity->product?->name }}</span>
                        @if ($editingQuantity->product?->reference)
                            <span>[{{ $editingQuantity->product->reference }}]</span>
                        @endif
                        @if ($editingQuantity->lot?->name)
                            <span>{{ __('barcode::app.adjustments.lot-serial') }}: {{ $editingQuantity->lot->name }}</span>
                        @endif
                    </div>

                    <div class="product-thumb product-thumb-large">
                        @if ($productImageUrl)
                            <img src="{{ $productImageUrl }}" alt="">
                        @else
                            <span>{{ mb_substr((string) $editingQuantity->product?->name, 0, 1) }}</span>
                        @endif
                    </div>
                </div>
            </x-filament::section>

            <form class="editor-form" wire:submit="confirmQuantityEdit">
                <x-filament::section compact class="editor-details-section">
                    <x-slot name="heading">
                        {{ __('barcode::app.adjustments.editor-title') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('barcode::app.adjustments.editor-subtitle') }}
                    </x-slot>

                    <div class="editor-quantity-row">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="number"
                                min="0"
                                step="0.01"
                                wire:model="editingCountedQuantity"
                            />
                        </x-filament::input.wrapper>
                        <div class="editor-uom">{{ $editingQuantity->product?->uom?->name }}</div>
                    </div>

                    <div class="editor-controls">
                        <button type="button" wire:click="$set('editingCountedQuantity', 0)">0</button>
                        <button type="button" wire:click="$set('editingCountedQuantity', {{ max($currentCountedQuantity - 1, 0) }})">-1</button>
                        <button type="button" wire:click="$set('editingCountedQuantity', {{ $currentCountedQuantity + 1 }})">+1</button>
                        <button type="button" class="confirm-inline" wire:click="$set('editingCountedQuantity', {{ $currentCountedQuantity + $remainingToFullCount }})">
                            +{{ number_format($remainingToFullCount, 0) }}
                        </button>
                    </div>

                    <div class="adjustment-info-grid">
                        <div class="adjustment-info-card">
                            <span>{{ __('barcode::app.adjustments.location') }}</span>
                            <strong>{{ $editingQuantity->location?->full_name ?? '—' }}</strong>
                        </div>
                        <div class="adjustment-info-card">
                            <span>{{ __('barcode::app.adjustments.lot-serial') }}</span>
                            <strong>{{ $editingQuantity->lot?->name ?? '—' }}</strong>
                        </div>
                        <div class="adjustment-info-card">
                            <span>{{ __('barcode::app.adjustments.on-hand') }}</span>
                            <strong>{{ number_format((float) $editingQuantity->quantity, 2) }} {{ $editingQuantity->product?->uom?->name }}</strong>
                        </div>
                        <div class="adjustment-info-card">
                            <span>{{ __('barcode::app.adjustments.counted') }}</span>
                            <strong>{{ number_format($currentCountedQuantity, 2) }} {{ $editingQuantity->product?->uom?->name }}</strong>
                        </div>
                    </div>
                </x-filament::section>
            </form>
        </section>

        <footer class="action-bar editor-action-bar">
            <x-filament::button color="gray" style="width:100%;display:flex;justify-content:center;" wire:click="discardQuantityEdit">
                {{ __('barcode::app.operation.discard') }}
            </x-filament::button>
            <x-filament::button color="primary" style="width:100%;display:flex;justify-content:center;" wire:click="confirmQuantityEdit">
                {{ __('barcode::app.operation.confirm') }}
            </x-filament::button>
        </footer>
    @else
        <div id="barcode-reader" class="barcode-reader" x-show="active" x-cloak></div>

        <div class="scanner-notice" x-show="scannerError" x-cloak>
            <x-filament::callout icon="heroicon-o-exclamation-triangle" color="warning">
                <x-slot name="heading">
                    Camera unavailable
                </x-slot>

                <x-slot name="description">
                    <span x-text="scannerError"></span>
                </x-slot>
            </x-filament::callout>
        </div>

        <form class="scan-form" wire:submit="scan">
            <x-filament::input.wrapper class="scan-field">
                <x-slot name="suffix">
                    <x-filament::icon-button
                        color="primary"
                        icon="heroicon-m-arrow-right"
                        :label="__('barcode::app.operation-search.open')"
                        type="submit"
                        size="sm"
                        class="scan-submit-button"
                    />
                </x-slot>

                <x-filament::input
                    type="search"
                    wire:model.live.debounce.250ms="search"
                    :placeholder="__('barcode::app.adjustments.search')"
                    autocomplete="off"
                />
            </x-filament::input.wrapper>
        </form>

        @if ($selectedLocation || $selectedProduct || $selectedLot)
            <x-filament::callout icon="heroicon-o-funnel" color="info" class="notice">
                <x-slot name="heading">
                    {{ __('barcode::app.adjustments.title') }}
                </x-slot>

                <x-slot name="description">
                    <div class="adjustment-filter-copy">
                        @if ($selectedLocation)
                            <span>{{ __('barcode::app.adjustments.location') }}: <strong>{{ $selectedLocation->full_name ?? $selectedLocation->name }}</strong></span>
                        @endif
                        @if ($selectedProduct)
                            <span>{{ __('barcode::app.adjustments.product') }}: <strong>{{ $selectedProduct->name }}</strong></span>
                        @endif
                        @if ($selectedLot)
                            <span>{{ __('barcode::app.adjustments.lot-serial') }}: <strong>{{ $selectedLot->name }}</strong></span>
                        @endif

                        <button type="button" class="adjustment-clear-button" wire:click="clearFilters">
                            {{ __('barcode::app.adjustments.clear-filters') }}
                        </button>
                    </div>
                </x-slot>
            </x-filament::callout>
        @elseif ($notice)
            <x-filament::callout icon="heroicon-o-information-circle" :color="$noticeColor" class="notice">
                <x-slot name="heading">
                    {{ __('barcode::app.adjustments.title') }}
                </x-slot>

                <x-slot name="description">
                    {{ $notice }}
                </x-slot>
            </x-filament::callout>
        @endif

        <section class="moves-list">
            <div class="section-title">{{ __('barcode::app.adjustments.title') }}</div>

            @forelse ($quantities as $quantity)
                @php
                    $productImages = $quantity->product?->images ?? [];
                    $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
                    $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
                    $countedQuantity = $quantity->inventory_quantity_set ? (float) $quantity->counted_quantity : 0.0;
                    $onHandQuantity = (float) $quantity->quantity;
                    $countState = ! $quantity->inventory_quantity_set
                        ? ''
                        : ($countedQuantity === $onHandQuantity ? 'is-complete' : 'is-partial');
                @endphp

                <article
                    id="quantity-{{ $quantity->id }}"
                    class="move-row {{ $selectedQuantityId === $quantity->id ? 'is-selected' : '' }} {{ $countState }}"
                    wire:key="quantity-{{ $quantity->id }}"
                >
                    <div class="move-open">
                        <div class="move-main">
                            <strong>{{ $quantity->location?->full_name ?? $quantity->location?->name }}</strong>
                            <span>{{ $quantity->product?->name }}</span>
                            @if ($quantity->lot?->name)
                                <span>{{ __('barcode::app.adjustments.lot-serial') }}: {{ $quantity->lot->name }}</span>
                            @endif
                            <div class="move-quantity move-quantity--{{ $countState !== '' ? str_replace('is-', '', $countState) : 'idle' }}">
                                <strong>{{ number_format($countedQuantity, 0) }} / {{ number_format($onHandQuantity, 0) }}</strong>
                                <span>{{ $quantity->product?->uom?->name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="move-controls">
                        <div class="move-tools">
                            <div class="product-thumb">
                                @if ($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="">
                                @else
                                    <span>{{ mb_substr((string) $quantity->product?->name, 0, 1) }}</span>
                                @endif
                            </div>

                            <button
                                type="button"
                                class="edit-button"
                                wire:click="editQuantity({{ $quantity->id }})"
                                aria-label="{{ 'Edit ' . ($quantity->product?->name ?? 'inventory quantity') }}"
                            >
                                <x-filament::icon icon="heroicon-m-pencil-square" />
                            </button>
                        </div>

                        <div class="step-actions">
                            @if (! $quantity->inventory_quantity_set || $countedQuantity <= 0)
                                <button type="button" class="step-button" wire:click="quickCountQuantity({{ $quantity->id }})">
                                    +{{ number_format($onHandQuantity, 0) }}
                                </button>
                            @else
                                <x-filament::icon-button
                                    color="success"
                                    icon="heroicon-m-check"
                                    :label="__('barcode::app.adjustments.apply')"
                                    wire:click="applyQuantityCount({{ $quantity->id }})"
                                    class="step-button"
                                />
                                <x-filament::icon-button
                                    color="danger"
                                    icon="heroicon-m-x-mark"
                                    :label="__('barcode::app.adjustments.clear')"
                                    wire:click="clearQuantityCount({{ $quantity->id }})"
                                    class="step-button"
                                />
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <x-filament::icon icon="heroicon-o-inbox" class="empty-state-icon" />
                    <div>{{ __('barcode::app.adjustments.empty') }}</div>
                </div>
            @endforelse
        </section>
    @endif
</main>
