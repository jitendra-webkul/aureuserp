<?php

namespace Webkul\Manufacturing\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Manufacturing\Database\Factories\WorkOrderFactory;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Enums\WorkCenterWorkingState;
use Webkul\Manufacturing\Enums\WorkOrderProductionAvailability;
use Webkul\Manufacturing\Enums\WorkOrderState;
use Webkul\Security\Models\User;
use Webkul\Support\Models\CalendarLeave;
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
        'duration_percent',
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
        'duration_percent'        => 'integer',
        'costs_per_hour'          => 'decimal:4',
    ];

    protected array $context = [];

    public function setContext(array $context)
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

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
        return $this->belongsTo(CalendarLeave::class, 'calendar_leave_id');
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'operation_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rawMaterialMoves(): HasMany
    {
        return $this->hasMany(Move::class, 'work_order_id');
    }

    public function blockedByWorkOrders(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_work_order_dependencies', 'work_order_id', 'depends_on_work_order_id');
    }

    public function dependentWorkOrders(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_work_order_dependencies', 'depends_on_work_order_id', 'work_order_id');
    }

    public function productivityLogs(): HasMany
    {
        return $this->hasMany(WorkCenterProductivityLog::class, 'work_order_id');
    }

    public function getWorkingStateAttribute()
    {
        return $this->workCenter->working_state;
    }

    public function getProductionStateAttribute()
    {
        return $this->manufacturingOrder->state;
    }

    public function getProductTrackingAttribute()
    {
        return $this->product->tracking;
    }

    public function getQuantityProductionAttribute()
    {
        return $this->manufacturingOrder->quantity;
    }

    public function getQuantityProducingAttribute()
    {
        return $this->manufacturingOrder->quantity_producing;
    }

    public function getQuantityRemainingAttribute()
    {
        if (! $this->manufacturingOrder->uom_id) {
            return 0;
        }

        return max(float_round($this->quantity_production - $this->quantity, precisionRounding: $this->manufacturingOrder->uom->rounding), 0);
    }

    public function getWorkingUsersAttribute()
    {
        [$workingUsers] = $this->computeWorkingUsers();

        return $workingUsers;
    }

    public function getLastWorkingUserAttribute()
    {
        [, $lastWorkingUser] = $this->computeWorkingUsers();

        return $lastWorkingUser;
    }

    public function getIsUserWorkingAttribute()
    {
        [, , $isUserWorking] = $this->computeWorkingUsers();

        return $isUserWorking;
    }

    public function getDisplayNameAttribute()
    {
        $displayName = "{$this->manufacturingOrder->name} - {$this->name}";

        if ($this->context['prefix_product'] ?? false) {
            $displayName = "{$this->product->name} - {$this->manufacturingOrder->name} - {$this->name}";
        }

        return $displayName;
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

        static::saving(function ($workOrder) {
            $workOrder->computeName();

            $workOrder->computeBarcode();

            $workOrder->computeUOMId();

            $workOrder->computeState();

            $workOrder->computeDuration();
        });

        static::created(function ($workOrder) {
            $workOrder->update(['name' => $workOrder->name]);
        });

        static::updated(function ($workOrder) {
            if ($workOrder->wasChanged('state') || $workOrder->wasChanged('production_availability')) {
                $workOrder->dependentWorkOrders->each(function ($dependentWorkOrder) {
                    $dependentWorkOrder->setContext(['no_recursion' => true]);

                    $dependentWorkOrder->computeState();

                    $dependentWorkOrder->save();
                });

                if ($workOrder->wasChanged('state')) {
                    $workOrder->manufacturingOrder->computeState();

                    $workOrder->manufacturingOrder->save();
                }
            }
        });
    }

    public function computeName()
    {
        $this->name = $this->operation->name;
    }

    public function computeBarcode()
    {
        $this->barcode = 'MO/'.$this->manufacturingOrder->id.'/'.$this->id;
    }

    public function computeUOMId()
    {
        $this->uom_id = $this->product?->uom_id;
    }

    public function computeDuration(): void
    {
        $this->duration = $this->productivityLogs->sum('duration');

        $this->duration_per_unit = round($this->duration / max($this->quantity_produced, 1), 2);

        if ($this->expected_duration) {
            $this->duration_percent = max(
                -2147483648,
                min(
                    2147483647,
                    100 * ($this->expected_duration - $this->duration) / $this->expected_duration
                )
            );
        } else {
            $this->duration_percent = 0;
        }
    }

    public function computeState()
    {
        if (! in_array($this->state, [WorkOrderState::PENDING, WorkOrderState::WAITING, WorkOrderState::READY])) {
            return;
        }

        $blockedByWorkOrders = $this->blockedByWorkOrders;

        if ($this->production_availability === WorkOrderProductionAvailability::ASSIGNED) {
            $this->state = $blockedByWorkOrders->every(fn ($wo) => in_array($wo->state, [WorkOrderState::DONE, WorkOrderState::CANCEL]))
                ? WorkOrderState::READY
                : WorkOrderState::PENDING;

            return;
        }

        if ($this->context['no_recursion'] ?? false) {
            return;
        }

        if (
            $blockedByWorkOrders->isNotEmpty()
            && ! $blockedByWorkOrders->every(fn ($wo) => in_array($wo->state, [WorkOrderState::DONE, WorkOrderState::CANCEL]))
        ) {
            $this->state = WorkOrderState::PENDING;
        } else {
            $this->state = WorkOrderState::WAITING;
        }
    }

    public function computeWorkingUsers(): array
    {
        $workingUsers = $this->productivityLogs
            ->filter(fn ($log) => ! $log->finished_at)
            ->sortBy('started_at')
            ->pluck('assignedUser')
            ->unique('id');

        $lastWorkingUser = null;

        if ($workingUsers->isNotEmpty()) {
            $lastWorkingUser = $workingUsers->last();
        } elseif ($this->productivityLogs->isNotEmpty()) {
            $timesWithEnd = $this->productivityLogs->filter(fn ($log) => $log->finished_at);

            $lastWorkingUser = $timesWithEnd->isNotEmpty()
                ? $timesWithEnd->sortBy('finished_at')->last()->assignedUser
                : $this->productivityLogs->last()->assignedUser;
        } else {
            $lastWorkingUser = null;
        }

        $isUserWorking = $this->productivityLogs->some(
            fn ($x) => $x->assigned_user_id === Auth::id()
                && ! $x->finished_at
                && in_array($x->loss_type, ['productive', 'performance'])
        );

        return [
            $workingUsers,
            $lastWorkingUser,
            $isUserWorking,
        ];
    }

    public function calculateDateFinished(?Carbon $startedAt = null): Carbon
    {
        $workCenter = ($this->context['new_work_center_id'] ?? false)
            ? WorkCenter::find($this->context['new_work_center_id'])
            : $this->workCenter;

        $start = $startedAt ?? Carbon::parse($this->started_at);

        if (1 || ! $workCenter->calendar_id) {
            $durationInSeconds = $this->expected_duration * 60;

            return $start->clone()->addSeconds($durationInSeconds);
        }

        return $workCenter->calendar->planHours(
            $this->expected_duration / 60.0,
            $start,
            computeLeaves: true,
            domain: [['time_type', 'in', ['leave', 'other']]]
        );
    }

    public function calculateExpectedDuration(?Carbon $dateStart = null, ?Carbon $dateFinished = null): float
    {
        $start = $dateStart ?? Carbon::parse($this->started_at);

        $finished = $dateFinished ?? Carbon::parse($this->finished_at);

        //TODO: Implement this
        if (true || ! $this->workCenter->calendar_id) {
            return $finished->diffInSeconds($start) / 60;
        }

        $interval = $this->workCenter->calendar->getWorkDurationData(
            $start,
            $finished,
            domain: [['time_type', 'in', ['leave', 'other']]]
        );

        return $interval['hours'] * 60;
    }

    public function shouldStartTimer(): bool
    {
        return true;
    }

    public function start(bool $raiseOnInvalidState = false): void
    {
        if ($this->working_state === WorkCenterWorkingState::BLOCKED) {
            throw new \Exception(__('Please unblock the work center to start the work order.'));
        }

        if ($this->productivityLogs->filter(fn ($time) => $time->user_id === Auth::id() && ! $time->finished_at)->isNotEmpty()) {
            return;
        }

        if (in_array($this->state, [WorkOrderState::DONE, WorkOrderState::CANCEL])) {
            if ($raiseOnInvalidState) {
                return;
            }

            throw new \Exception(__('You cannot start a work order that is already done or cancelled'));
        }

        if ($this->product_tracking === ProductTracking::SERIAL && $this->qty_producing == 0) {
            $this->manufacturingOrder->update(['qty_producing' => 1]);
        } elseif ($this->qty_producing == 0) {
            $this->manufacturingOrder->update(['qty_producing' => $this->qty_remaining]);
        }

        if ($this->shouldStartTimer()) {
            WorkCenterProductivityLog::create($this->prepareTimelineVals($this->duration, now()));
        }

        if ($this->manufacturingOrder->state !== ManufacturingOrderState::PROGRESS) {
            $this->manufacturingOrder->update(['started_at' => now()]);
        }

        if ($this->state === WorkOrderState::PROGRESS) {
            return;
        }

        $dateStart = now();

        $values = [
            'state'      => WorkOrderState::PROGRESS,
            'started_at' => $dateStart,
        ];

        if (! $this->calendar_leave_id) {
            $leave = CalendarLeave::create([
                'name'          => $this->name,
                'calendar_id'   => $this->workCenter->calendar_id,
                'date_from'     => $dateStart,
                'date_to'       => $dateStart->clone()->addMinutes((float) $this->expected_duration),
                'resource_id'   => $this->workCenter->getKey(),
                'resource_type' => $this->workCenter->getMorphClass(),
                'time_type'     => 'other',
            ]);

            $values['finished_at'] = $leave->date_to;

            $values['calendar_leave_id'] = $leave->id;
        } else {
            if (! $this->started_at || $this->started_at > $dateStart) {
                $values['started_at'] = $dateStart;

                $values['finished_at'] = $this->calculateDateFinished($dateStart);
            }

            if ($this->finished_at && $this->finished_at < $dateStart) {
                $values['finished_at'] = $dateStart;
            }
        }

        $this->update($values);
    }

    public function pending(): void
    {
        static::endPrevious(collect([$this]));
    }

    public function finish(): void
    {
        $dateFinished = now();

        if (in_array($this->state, [WorkOrderState::DONE, WorkOrderState::CANCEL])) {
            return;
        }

        $moves = $this->rawMaterialMoves->merge(
            $this->production->moveByproducts->filter(fn ($move) => $move->mo_operation_id === $this->operation_id)
        );

        foreach ($moves as $move) {
            if (! $move->is_picked) {
                $qtyAvailable = float_is_zero(
                    $this->production->qty_producing,
                    precisionRounding: $this->production->uom->rounding
                )
                    ? $this->production->quantity
                    : $this->production->qty_producing;

                $newQty = float_round(
                    $qtyAvailable * $move->unit_factor,
                    precisionRounding: $move->uom->rounding
                );

                $move->setQuantityDone($newQty);
            }
        }

        $moves->each->update(['is_picked' => true]);

        $this->endAll(collect([$this]));

        $vals = [
            'qty_produced'  => $this->qty_produced ?: ($this->qty_producing ?: $this->qty_production),
            'state'         => WorkOrderState::DONE,
            'date_finished' => $dateFinished,
            'costs_hour'    => $this->workcenter->costs_hour,
        ];

        if (! $this->date_start || $dateFinished < Carbon::parse($this->date_start)) {
            $vals['date_start'] = $dateFinished;
        }

        $this->update($vals);
    }

    public static function endPrevious($workOrders, bool $endAll = false): void
    {
        $query = WorkCenterProductivityLog::whereIn('work_order_id', $workOrders->pluck('id'))
            ->whereNull('finished_at');

        if (! $endAll) {
            $query->where('assigned_user_id', Auth::id())->limit(1);
        }

        $query->get()->each(fn ($log) => $log->closeTimer());
    }

    public function prepareTimelineVals(float $duration, Carbon $dateStart, ?Carbon $dateEnd = null): array
    {
        if (! $this->expected_duration || $duration <= $this->expected_duration) {
            $lossId = WorkCenterProductivityLoss::where('loss_type', 'productive')->first();

            if (! $lossId) {
                throw new \Exception(__("You need to define at least one productivity loss in the category 'Productivity'. Create Configuration settings."));
            }
        } else {
            $lossId = WorkCenterProductivityLoss::where('loss_type', 'performance')->first();

            if (! $lossId) {
                throw new \Exception(__("You need to define at least one productivity loss in the category 'Performance'. Create Configuration settings."));
            }
        }

        return [
            'work_order_id'  => $this->id,
            'work_center_id' => $this->work_center_id,
            'loss_id'        => $lossId->id,
            'started_at'     => $dateStart->startOfSecond(),
            'finished_at'    => $dateEnd?->startOfSecond(),
        ];
    }
}
