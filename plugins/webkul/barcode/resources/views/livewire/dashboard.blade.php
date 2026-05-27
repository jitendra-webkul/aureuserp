<div>
    <main class="barcode-page">
        @include('barcode::components.header.web', [
            'title' => __('barcode::app.dashboard.operations'),
            'breadcrumbs' => [
                ['label' => __('barcode::app.title')],
            ],
        ])

        <section class="operation-grid">
            @forelse ($operationTypes as $operationType)
                <a class="operation-card" href="{{ route('barcode.transfers', $operationType) }}" wire:navigate>
                    <div class="operation-card-copy">
                        <span>{{ $operationType->name }}</span>

                        @if ($operationType->warehouse?->name)
                            <small>{{ $operationType->warehouse->name }}</small>
                        @endif
                    </div>

                    <strong>{{ $operationType->waiting_count }}</strong>
                </a>
            @empty
                <div class="empty-state">
                    <x-filament::icon icon="heroicon-o-inbox" class="empty-state-icon" />
                    <div>{{ __('barcode::app.dashboard.empty') }}</div>
                </div>
            @endforelse
        </section>
    </main>
</div>
