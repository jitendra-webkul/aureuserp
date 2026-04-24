<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\OrderPoint;
use Webkul\Inventory\Models\ProcurementGroup;
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

    public function unbuildOrders(): HasMany
    {
        return $this->hasMany(UnbuildOrder::class, 'manufacturing_order_id');
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
}
