<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Employee\Models\CalendarLeaves;
use Webkul\Manufacturing\Database\Factories\WorkOrderFactory;
use Webkul\Manufacturing\Enums\WorkOrderProductionAvailability;
use Webkul\Manufacturing\Enums\WorkOrderState;
use Webkul\Security\Models\User;
use Webkul\Support\Models\UOM;

class WorkOrder extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_work_orders';

    protected $fillable = [
        'name',
        'barcode',
        'production_availability',
        'state',
        'quantity_produced',
        'expected_duration',
        'started_at',
        'finished_at',
        'duration',
        'duration_per_unit',
        'costs_per_hour',
        'work_center_id',
        'product_id',
        'uom_id',
        'manufacturing_order_id',
        'calendar_leave_id',
        'operation_id',
        'creator_id',
    ];

    protected $casts = [
        'production_availability' => WorkOrderProductionAvailability::class,
        'state'                   => WorkOrderState::class,
        'quantity_produced'       => 'decimal:4',
        'expected_duration'       => 'decimal:4',
        'started_at'              => 'datetime',
        'finished_at'             => 'datetime',
        'duration'                => 'decimal:4',
        'duration_per_unit'       => 'decimal:4',
        'costs_per_hour'          => 'decimal:4',
    ];

    public function getModelTitle(): string
    {
        return __('manufacturing::models/work-order.title');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id')->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class)->withTrashed();
    }

    public function manufacturingOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'manufacturing_order_id');
    }

    public function calendarLeave(): BelongsTo
    {
        return $this->belongsTo(CalendarLeaves::class, 'calendar_leave_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operation_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function blockedByWorkOrders(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_work_order_dependencies', 'work_order_id', 'depends_on_work_order_id');
    }

    public function dependentWorkOrders(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_work_order_dependencies', 'depends_on_work_order_id', 'work_order_id');
    }

    protected static function newFactory(): WorkOrderFactory
    {
        return WorkOrderFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $workOrder): void {
            $workOrder->creator_id ??= Auth::id();
            $workOrder->state ??= WorkOrderState::PENDING;
        });
    }
}
