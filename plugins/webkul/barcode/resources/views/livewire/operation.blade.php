@php
    $editingMoveLine = $editingMoveLineId ? $operation->moveLines->firstWhere('id', $editingMoveLineId) : null;
    $allMoveLinesCounted = $moveLines->isNotEmpty()
        && $moveLines->every(fn ($moveLine) => (float) ($countedMoveLineQuantities[$moveLine->id] ?? 0) >= (float) $moveLine->qty);
    $hasAnyCountedMoveLine = $moveLines->contains(fn ($moveLine) => (float) ($countedMoveLineQuantities[$moveLine->id] ?? 0) > 0);
@endphp

<main class="barcode-page operation-screen {{ $editingMoveLine ? 'is-editing-move' : '' }}" x-data="barcodeScanner('barcode', 'scan')">
    @include('barcode::components.header.web', [
        'title' => $operation->name,
        'subtitle' => $operation->partner?->name ?? $operation->origin,
        'breadcrumbs' => [
            ['label' => __('barcode::app.title'), 'href' => route('barcode.dashboard')],
            ['label' => $operationType->name, 'href' => route('barcode.transfers', $operationType)],
        ],
        'showCancel' => $editingMoveLine ? true : null,
        'showBarcode' => $editingMoveLine ? null : true,
    ])

    @if ($editingMoveLine)
        @php
            $productImages = $editingMoveLine->product?->images ?? [];
            $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
            $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
            $tracking = $editingMoveLine->product?->tracking;
        @endphp

        <section class="move-editor">
            <x-filament::section compact class="editor-summary-section">
                <div class="editor-product">
                    <div class="editor-product-info">
                        <strong>⌁ {{ $editingMoveLine->product?->reference ?? $editingMoveLine->reference }}</strong>

                        <span>{{ $editingMoveLine->product?->name }}</span>

                        @if ($editingMoveLine->product?->barcode)
                            <span>[{{ $editingMoveLine->product->barcode }}]</span>
                        @endif

                        <span>{{ __('barcode::app.operation.source') }}: {{ $editingMoveLine->sourceLocation?->full_name ?? $editingMoveLine->sourceLocation?->name }}</span>
                    </div>

                    <div class="product-thumb product-thumb-large">
                        @if ($productImageUrl)
                            <img src="{{ $productImageUrl }}" alt="">
                        @else
                            <span>{{ mb_substr((string) $editingMoveLine->product?->name, 0, 1) }}</span>
                        @endif
                    </div>
                </div>
            </x-filament::section>

            <form class="editor-form" wire:submit="confirmMoveLineEdit">
                <x-filament::section compact class="editor-details-section">
                    <x-slot name="heading">
                        Move details
                    </x-slot>

                    <div class="editor-quantity-row">
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="number"
                                min="0"
                                max="{{ (float) $editingMoveLine->qty }}"
                                step="0.01"
                                wire:model="countedMoveLineQuantities.{{ $editingMoveLine->id }}"
                            />
                        </x-filament::input.wrapper>
                        <div class="editor-uom">{{ $editingMoveLine->uom?->name }}</div>
                    </div>

                    <div class="editor-controls">
                        <button type="button" wire:click="setMoveLineQuantity({{ $editingMoveLine->id }}, 0)">0</button>
                        <button type="button" wire:click="adjustMoveLineQuantity({{ $editingMoveLine->id }}, -1)">-1</button>
                        <button type="button" wire:click="adjustMoveLineQuantity({{ $editingMoveLine->id }}, 1)">+1</button>
                        <button
                            type="button"
                            class="confirm-inline"
                            wire:click="adjustMoveLineQuantity({{ $editingMoveLine->id }}, {{ max((float) $editingMoveLine->qty - (float) ($countedMoveLineQuantities[$editingMoveLine->id] ?? 0), 0) }})"
                        >
                            +{{ number_format(max((float) $editingMoveLine->qty - (float) ($countedMoveLineQuantities[$editingMoveLine->id] ?? 0), 0), 0) }}
                        </button>
                    </div>

                    <x-filament::fieldset :contained="false" class="editor-fields-card">
                        <x-slot name="label">
                            Move settings
                        </x-slot>

                        <div class="editor-fields-grid">
                            @if ($editingMoveLine->sourceLocation?->type === \Webkul\Inventory\Enums\LocationType::INTERNAL)
                                <label class="lot-field">
                                    <span>Pick From</span>
                                    <x-filament::input.wrapper style="height:40px !important;">
                                        <x-filament::input.select wire:model.live="editingMoveLineQuantityId">
                                            @foreach ($editingMoveLineQuantityOptions as $quantityId => $quantityLabel)
                                                <option value="{{ $quantityId }}">{{ $quantityLabel }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </label>
                            @endif

                            @if ($tracking && $tracking !== \Webkul\Inventory\Enums\ProductTracking::QTY)
                                <label class="lot-field">
                                    <span>Serial/Lot Number</span>
                                    <x-filament::input.wrapper style="height:40px !important;">
                                        <x-filament::input type="text" wire:model="editingMoveLineLotName" />
                                    </x-filament::input.wrapper>
                                </label>
                            @endif

                            <label class="lot-field">
                                <span>Destination Location</span>
                                <x-filament::input.wrapper style="height:40px !important;">
                                    <x-filament::input.select wire:model.live="editingMoveLineDestinationLocationId">
                                        @foreach ($editingMoveLineDestinationLocationOptions as $locationId => $locationLabel)
                                            <option value="{{ $locationId }}">{{ $locationLabel }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </label>

                            @if ($editingMoveLineResultPackageOptions !== [])
                                <label class="lot-field">
                                    <span>Destination Package</span>
                                    <x-filament::input.wrapper style="height:40px !important;">
                                        <x-filament::input.select wire:model="editingMoveLineResultPackageId">
                                            <option value="">Select package</option>
                                            @foreach ($editingMoveLineResultPackageOptions as $packageId => $packageLabel)
                                                <option value="{{ $packageId }}">{{ $packageLabel }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </label>
                            @endif
                        </div>
                    </x-filament::fieldset>
                </x-filament::section>
            </form>

            @if ($editingMoveLine->sourceLocation?->type === \Webkul\Inventory\Enums\LocationType::INTERNAL)
                <x-filament::section compact class="editor-stock-section">
                    <x-slot name="heading">
                        Quantity in Stock
                    </x-slot>

                    <x-slot name="description">
                        Select where else to pick the product from
                    </x-slot>

                    <div class="stock-options">
                        @forelse ($moveLineSourceLocationOptions as $option)
                            <button
                                type="button"
                                class="stock-card {{ (string) $editingMoveLineQuantityId === (string) $option['quantity_id'] ? 'is-active' : '' }}"
                                wire:click="selectEditingMoveLineSourceQuantity({{ $option['quantity_id'] }})"
                            >
                                <strong>{{ $option['location'] }}</strong>
                                @if ($option['lot'] || $option['package'])
                                    <span>{{ collect([$option['lot'], $option['package']])->filter()->implode(' - ') }}</span>
                                @endif
                                <span>Available: {{ number_format((float) $option['available'], 2) }} / {{ number_format((float) $option['quantity'], 2) }} {{ $option['uom'] }}</span>
                            </button>
                        @empty
                            <div class="empty-state">No stock locations found.</div>
                        @endforelse
                    </div>
                </x-filament::section>
            @endif
        </section>

        <footer class="action-bar editor-action-bar">
            <x-filament::button color="gray" style="width:100%;display:flex;justify-content:center;" wire:click="discardMoveLineEdit">
                Discard
            </x-filament::button>
            <x-filament::button color="primary" style="width:100%;display:flex;justify-content:center;" wire:click="confirmMoveLineEdit">
                Confirm
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
                        label="Submit scan"
                        type="submit"
                        size="sm"
                        class="scan-submit-button"
                    />
                </x-slot>

                <x-filament::input
                    type="search"
                    wire:model.live.debounce.250ms="barcode"
                    :placeholder="__('barcode::app.operation.manual-scan')"
                    autocomplete="off"
                />
            </x-filament::input.wrapper>
        </form>

        @if ($notice)
            <x-filament::callout icon="heroicon-o-information-circle" color="info" class="notice">
                <x-slot name="heading">
                    {{ __('barcode::app.title') }}
                </x-slot>

                <x-slot name="description">
                    {{ $notice }}
                </x-slot>
            </x-filament::callout>
        @endif

        <section class="moves-list">
            <div class="section-title">{{ __('barcode::app.operation.moves') }}</div>

            @forelse ($moveLines as $moveLine)
                @php
                    $productImages = $moveLine->product?->images ?? [];
                    $productImage = is_array($productImages) ? ($productImages[0] ?? null) : null;
                    $productImageUrl = is_string($productImage) && $productImage !== '' ? (str_starts_with($productImage, 'http') || str_starts_with($productImage, '/') ? $productImage : asset('storage/'.$productImage)) : null;
                    $countedQuantity = (float) ($countedMoveLineQuantities[$moveLine->id] ?? 0);
                    $demandQuantity = (float) $moveLine->qty;
                    $countState = $countedQuantity >= $demandQuantity && $demandQuantity > 0 ? 'is-complete' : ($countedQuantity > 0 ? 'is-partial' : '');
                @endphp

                <article
                    id="line-{{ $moveLine->id }}"
                    class="move-row {{ $selectedMoveLineId === $moveLine->id ? 'is-selected' : '' }} {{ $countState }}"
                    wire:key="line-{{ $moveLine->id }}"
                >
                    <div class="move-open">
                        <div class="move-main">
                            <strong>{{ $moveLine->product?->reference ?? $moveLine->reference }}</strong>
                            <span>{{ $moveLine->product?->name }}</span>
                            <span>{{ __('barcode::app.operation.source') }}: {{ $moveLine->sourceLocation?->full_name ?? $moveLine->sourceLocation?->name }}</span>
                            @if ($moveLine->product?->barcode)
                                <span>[{{ $moveLine->product->barcode }}]</span>
                            @endif
                            <div class="move-quantity move-quantity--{{ $countState !== '' ? str_replace('is-', '', $countState) : 'idle' }}">
                                <strong>{{ number_format($countedQuantity, 0) }} / {{ number_format($demandQuantity, 0) }}</strong>
                                <span>{{ $moveLine->uom?->name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="move-controls">
                        <div class="move-tools">
                            <div class="product-thumb">
                                @if ($productImageUrl)
                                    <img src="{{ $productImageUrl }}" alt="">
                                @else
                                    <span>{{ mb_substr((string) $moveLine->product?->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <button
                                type="button"
                                class="edit-button"
                                wire:click="editMoveLine({{ $moveLine->id }})"
                                aria-label="{{ 'Edit ' . ($moveLine->product?->name ?? 'move line') }}"
                            >
                                <x-filament::icon icon="heroicon-m-pencil-square" />
                            </button>
                        </div>

                        <div class="step-actions">
                            @if ($countedQuantity <= 0)
                                <button type="button" class="step-button" wire:click="setMoveLineQuantity({{ $moveLine->id }}, {{ $demandQuantity }})">+{{ number_format($demandQuantity, 0) }}</button>
                            @elseif ($countedQuantity >= $demandQuantity)
                                <button type="button" class="step-button" wire:click="adjustMoveLineQuantity({{ $moveLine->id }}, -1)">-1</button>
                            @else
                                <button type="button" class="step-button" wire:click="adjustMoveLineQuantity({{ $moveLine->id }}, 1)">+1</button>
                                <button type="button" class="step-button" wire:click="adjustMoveLineQuantity({{ $moveLine->id }}, -1)">-1</button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <x-filament::icon icon="heroicon-o-inbox" class="empty-state-icon" />
                    <div>{{ __('barcode::app.operation.empty-moves') }}</div>
                </div>
            @endforelse
        </section>

        <footer class="action-bar">
            @foreach ($actions as $action)
                @if ($action['key'] === 'validate' || $action['key'] === 'done')
                    <button
                        type="button"
                        class="action-button {{ $allMoveLinesCounted ? 'primary' : '' }}"
                        x-on:click="requestValidate('{{ addslashes($action['label']) }}', {{ Js::from($backorderMoveLines) }}, {{ $hasAnyCountedMoveLine ? 'true' : 'false' }}, {{ $shouldAskBackorder ? 'true' : 'false' }})"
                    >
                        {{ $action['label'] }}
                    </button>
                @elseif ($action['key'] === 'cancel')
                    <button type="button" class="action-button danger" x-on:click="requestAction('{{ $action['key'] }}', '{{ addslashes($action['label']) }}')">
                        {{ $action['label'] }}
                    </button>
                @else
                    <button type="button" class="action-button {{ $action['variant'] }}" x-on:click="requestAction('{{ $action['key'] }}', '{{ addslashes($action['label']) }}')">
                        {{ $action['label'] }}
                    </button>
                @endif
            @endforeach
        </footer>

        <div class="confirm-backdrop"
            x-show="confirmPending"
            x-cloak
            x-transition:enter="confirm-backdrop-enter"
            x-transition:enter-start="confirm-backdrop-enter-start"
            x-transition:enter-end="confirm-backdrop-enter-end"
            x-transition:leave="confirm-backdrop-enter"
            x-transition:leave-start="confirm-backdrop-enter-end"
            x-transition:leave-end="confirm-backdrop-enter-start"
        >
            <div class="confirm-dialog"
                x-show="confirmPending"
                x-transition:enter="confirm-dialog-enter"
                x-transition:enter-start="confirm-dialog-enter-start"
                x-transition:enter-end="confirm-dialog-enter-end"
                x-transition:leave="confirm-dialog-enter"
                x-transition:leave-start="confirm-dialog-enter-end"
                x-transition:leave-end="confirm-dialog-enter-start"
            >
                {{-- Backorder warning --}}
                <template x-if="confirmMode === 'backorder'">
                    <div>
                        <h3 class="confirm-dialog-title">{{ __('barcode::app.actions.backorder-title') }}</h3>
                        <p class="confirm-dialog-subtitle">{{ __('barcode::app.actions.backorder-prompt') }}</p>

                        <table class="backorder-table">
                            <thead>
                                <tr>
                                    <th>{{ __('barcode::app.actions.backorder-col-product') }}</th>
                                    <th>{{ __('barcode::app.actions.backorder-col-done-todo') }}</th>
                                    <th>{{ __('barcode::app.actions.backorder-col-backorder') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in backorderMoveLines" :key="row.id">
                                    <tr>
                                        <td x-text="row.name"></td>
                                        <td class="backorder-qty" x-text="row.counted + ' / ' + row.required + ' ' + row.uom"></td>
                                        <td x-text="row.backorder + ' ' + row.uom"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div class="confirm-buttons confirm-buttons--triple" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;width:100%;">
                            <x-filament::button color="gray" style="width:100%;display:flex;justify-content:center;" x-on:click="cancelAction()">
                                {{ __('barcode::app.actions.stay-on-transfer') }}
                            </x-filament::button>
                            <x-filament::button color="danger" style="width:100%;display:flex;justify-content:center;" x-on:click="$wire.executeAction(confirmPending, true); cancelAction()">
                                No Backorder
                            </x-filament::button>
                            <x-filament::button color="primary" style="width:100%;display:flex;justify-content:center;" x-on:click="$wire.executeAction(confirmPending, false); cancelAction()">
                                {{ __('barcode::app.actions.validate') }}
                            </x-filament::button>
                        </div>
                    </div>
                </template>

                {{-- Simple confirmation --}}
                <template x-if="confirmMode === 'simple'">
                    <div>
                        <p>{{ __('barcode::app.actions.confirm-prompt') }} <strong x-text="confirmLabel"></strong>?</p>
                        <div class="confirm-buttons confirm-buttons--pair" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;width:100%;">
                            <x-filament::button color="gray" style="width:100%;display:flex;justify-content:center;" x-on:click="cancelAction()">
                                {{ __('barcode::app.actions.cancel') }}
                            </x-filament::button>
                            <x-filament::button color="primary" style="width:100%;display:flex;justify-content:center;" x-on:click="$wire.executeAction(confirmPending); cancelAction()">
                                {{ __('barcode::app.actions.confirm') }}
                            </x-filament::button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    @endif
</main>
