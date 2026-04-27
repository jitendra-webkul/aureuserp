<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Manufacturing\Database\Factories\OrderFactory;
use Webkul\Manufacturing\Enums\BillOfMaterialConsumption;
use Webkul\Manufacturing\Enums\ManufacturingOrderPriority;
use Webkul\Manufacturing\Enums\ManufacturingOrderReservationState;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

class Order extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_orders';

    protected $fillable = [
        'reference',
        'priority',
        'origin',
        'state',
        'reservation_state',
        'consumption',
        'quantity',
        'quantity_in_progress',
        'deadline_at',
        'started_at',
        'finished_at',
        'production_location_id',
        'procurement_group_id',
        'product_id',
        'uom_id',
        'producing_lot_id',
        'operation_type_id',
        'source_location_id',
        'destination_location_id',
        'final_location_id',
        'bill_of_material_id',
        'assigned_user_id',
        'company_id',
        'order_point_id',
        'creator_id',
    ];

    protected $casts = [
        'priority'             => ManufacturingOrderPriority::class,
        'state'                => ManufacturingOrderState::class,
        'reservation_state'    => ManufacturingOrderReservationState::class,
        'consumption'          => BillOfMaterialConsumption::class,
        'quantity'             => 'decimal:4',
        'quantity_in_progress' => 'decimal:4',
        'deadline_at'          => 'datetime',
        'started_at'           => 'datetime',
        'finished_at'          => 'datetime',
    ];

    public function getModelTitle(): string
    {
        return __('manufacturing::models/order.title');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class)->withTrashed();
    }

    public function producingLot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'producing_lot_id');
    }

    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class, 'operation_type_id')->withTrashed();
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id')->withTrashed();
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id')->withTrashed();
    }

    public function finalLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'final_location_id')->withTrashed();
    }

    public function productionLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'production_location_id')->withTrashed();
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id')->withTrashed();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function orderPoint(): BelongsTo
    {
        return $this->belongsTo(OrderPoint::class, 'order_point_id')->withTrashed();
    }

    public function procurementGroup(): BelongsTo
    {
        return $this->belongsTo(ProcurementGroup::class, 'procurement_group_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'manufacturing_order_id');
    }

    public function rawMaterialMoves(): HasMany
    {
        return $this->hasMany(Move::class, 'raw_material_order_id');
    }

    public function finishedMoves(): HasMany
    {
        return $this->hasMany(Move::class, 'order_id');
    }

    public function moveDestinations(): HasMany
    {
        return $this->hasMany(Move::class, 'created_order_id');
    }

    public function unbuildOrders(): HasMany
    {
        return $this->hasMany(UnbuildOrder::class, 'manufacturing_order_id');
    }

    public function computeInventoryOperations()
    {
        $operations = Operation::where('procurement_group_id', $this->procurement_group_id)
            ->whereNotNull('procurement_group_id')
            ->get();

        $operations = $operations->merge(
            $this->rawMaterialMoves->flatMap->moveOrigins->pluck('operation')->filter()->unique('id')
        );

        return [
            $operations,
            $operations->count(),
        ];
    }

    public function getInventoryOperationsAttribute()
    {
        $operations = Operation::where('procurement_group_id', $this->procurement_group_id)
            ->whereNotNull('procurement_group_id')
            ->get();

        $operations = $operations->merge(
            $this->rawMaterialMoves->flatMap->moveOrigins->pluck('operation')->filter()->unique('id')
        );

        [$operations, $deliveryCount] = $this->computeInventoryOperations();

        return $operations;
    }

    public function getDeliveryCountAttribute()
    {
        [$operations, $deliveryCount] = $this->computeInventoryOperations();

        return $deliveryCount;
    }

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $order): void {
            $authUser = Auth::user();

            $order->creator_id ??= $authUser?->id;

            $order->company_id ??= $authUser?->default_company_id;

            $order->priority ??= ManufacturingOrderPriority::NORMAL;

            $order->state ??= ManufacturingOrderState::DRAFT;
            
            $order->consumption ??= BillOfMaterialConsumption::FLEXIBLE;

            $order->started_at ??= now();

            $order->computeName();

            if (! $order->procurement_group_id) {
                $order->procurement_group_id = $order->procurementGroup()->create([
                    'name' => $order->name,
                ])->id;
            }

            $order->computeProductionLocationId();

            $order->computeFinishedMoves();
        });

        static::saving(function ($order) {
            $order->computeName();
        });

        static::created(function ($order) {
            $order->update(['name' => $order->name]);
        });
    }

    public function computeName()
    {
        $this->name = 'MO/'.$this->id;
    }

    public function computeProductionLocationId()
    {
        $this->production_location_id = Location::where('type', 'production')->where('company_id', $this->company_id)->first()?->id;
    }

    public function computeFinishedMoves(): void
    {
        if ($this->state !== ManufacturingOrderState::DRAFT) {
            $updatedValues = [];

            if ($this->finished_at) {
                $updatedValues['date'] = $this->finished_at;
            }

            if ($this->deadline_at) {
                $updatedValues['deadline_at'] = $this->deadline_at;
            }

            if (! empty($updatedValues)) {
                $this->finishedMoves->each->update($updatedValues);
            }

            return;
        }

        $this->finishedMoves()->delete();

        if ($this->product_id) {
            $this->createUpdateMoveFinished();
        } else {
            $this->finishedMoves()
                ->whereNotNull('bom_line_id')
                ->delete();
        }
    }

    public function getMoveFinishedValues(
        int $productId,
        float $productUomQty,
        int $productUomId,
        ?int $operationId = null,
        ?int $byproductId = null,
        float $costShare = 0
    ): array {
        $groupOrders = $this->procurementGroup?->orders ?? collect();

        $moveDestinationIds = $this->moveDestinations->pluck('id')->all();

        if ($groupOrders->count() > 1) {
            $additionalDestinationIds = $groupOrders->first()
                ->finishedMoves
                ->filter(fn($move) => $move->product_id === $this->product_id)
                ->flatMap->moveDestinations
                ->pluck('id')
                ->all();

            $moveDestinationIds = array_unique(array_merge($moveDestinationIds, $additionalDestinationIds));
        }

        return [
            'product_id'              => $productId,
            'product_uom_qty'         => $productUomQty,
            'uom_id'                  => $productUomId,
            'operation_id'            => $operationId,
            'byproduct_id'            => $byproductId,
            'name'                    => 'New',
            'scheduled_at'            => $this->finished_at,
            'deadline'                => $this->deadline_at,
            'operation_type_id'       => $this->operation_type_id,
            'source_location_id'      => $this->production_location_id,
            'destination_location_id' => $this->destination_location_id,
            'company_id'              => $this->company_id,
            'order_id'                => $this->id,
            'warehouse_id'            => $this->destinationLocation->warehouse_id,
            'origin'                  => $this->product->partner_ref,
            'procurement_group_id'    => $this->procurementGroup?->id,
            'propagate_cancel'        => $this->propagate_cancel,
            'move_destination_ids'    => ! $byproductId ? $moveDestinationIds : [],
            'cost_share'              => $costShare,
        ];
    }

    public function getMovesFinishedValues(): array
    {
        $moves = [];

        $byproductProductIds = $this->billOfMaterial->byproducts->pluck('product_id')->all();

        if (in_array($this->product_id, $byproductProductIds)) {
            throw new \Exception(__('You cannot have :product as the finished product and in the Byproducts', [
                'product' => $this->product->name,
            ]));
        }

        $finishedMoveValues = $this->getMoveFinishedValues($this->product_id, $this->quantity, $this->uom_id);

        $finishedMoveValues['final_location_id'] = $this->final_location_id;

        $moves[] = $finishedMoveValues;

        foreach ($this->billOfMaterial->byproducts as $byproduct) {
            if ($byproduct->skipByproductLine($this->product)) {
                continue;
            }

            $productUomFactor = $this->uom->computeQuantity($this->quantity, $this->billOfMaterial->uom);

            $qty = $byproduct->quantity * ($productUomFactor / $this->billOfMaterial->quantity);

            $moves[] = $this->getMoveFinishedValues(
                $byproduct->product_id,
                $qty,
                $byproduct->uom_id,
                $byproduct->operation_id,
                $byproduct->id,
                $byproduct->cost_share
            );
        }

        return $moves;
    }

    public function createUpdateMoveFinished(): void
    {
        $movesFinishedValues = $this->getMovesFinishedValues();

        $movesByproductDict = $this->finishedMoves
            ->filter(fn($move) => $move->byproduct_id)
            ->keyBy('byproduct_id');

        $moveFinished = $this->finishedMoves
            ->filter(fn($move) => $move->product_id === $this->product_id)
            ->first();

        foreach ($movesFinishedValues as $moveFinishedValues) {
            if (isset($moveFinishedValues['byproduct_id']) && $movesByproductDict->has($moveFinishedValues['byproduct_id'])) {
                $movesByproductDict->get($moveFinishedValues['byproduct_id'])->update($moveFinishedValues);
            } elseif (isset($moveFinishedValues['product_id']) && $moveFinishedValues['product_id'] === $this->product_id && $moveFinished) {
                $moveFinished->update($moveFinishedValues);
            } else {
                $this->finishedMoves()->create($moveFinishedValues);
            }
        }
    }

    public function linkWorkOrdersAndMoves(): void
    {
        if ($this->workorders->isEmpty()) {
            return;
        }

        $workOrderPerOperation = $this->workorders->keyBy('operation_id');

        $workOrderBoms = $this->workOrders->pluck('operation.bom_id')->unique()->filter();

        $lastWorkOrderPerBom   = [];

        $allowWorkOrderDependencies = $this->billOfMaterial->allow_operation_dependencies;

        $workOrderOrder = fn($wo) => [$wo->sort, $wo->id];

        if ($allowWorkOrderDependencies) {
            foreach ($this->workOrders->sortBy($workOrderOrder) as $workOrder) {
                $blockedByIds = $workOrder->operation->blockedByOperations
                    ->filter(fn($operationId) => $workOrderPerOperation->has($operationId))
                    ->map(fn($operationId) => $workOrderPerOperation->get($operationId)->id)
                    ->all();

                $workOrder->blockedByWorkOrders()->syncWithoutDetaching($blockedByIds);

                if ($workOrder->dependentWorkOrders->isEmpty()) {
                    $lastWorkOrderPerBom[$workOrder->operation->bom_id] = $workOrder;
                }
            }
        } else {
            $previousWorkOrder = null;

            foreach ($this->workOrders->sortBy($workOrderOrder) as $workOrder) {
                if ($previousWorkOrder) {
                    $workOrder->blockedByWorkOrders()->syncWithoutDetaching([$previousWorkOrder->id]);
                }

                $previousWorkOrder = $workOrder;

                $lastWorkOrderPerBom[$workOrder->operation->bom_id] = $workOrder;
            }
        }

        $allMoves = $this->rawMaterialMoves->merge($this->finishedMoves);

        foreach ($allMoves as $move) {
            if ($move->operation_id) {
                $move->update([
                    'work_order_id' => $workOrderPerOperation->has($move->operation_id)
                        ? $workOrderPerOperation->get($move->operation_id)->id
                        : null,
                ]);
            } else {
                $bom = ($move->bomLine && $workOrderBoms->contains($move->bomLine->bom_id))
                    ? $move->bomLine->bom_id
                    : $this->bom_id;

                $move->update([
                    'work_order_id' => isset($lastWorkOrderPerBom[$bom]) ? $lastWorkOrderPerBom[$bom]->id : null,
                ]);
            }
        }
    }
}
