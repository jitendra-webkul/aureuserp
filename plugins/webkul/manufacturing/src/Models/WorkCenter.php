<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Webkul\Employee\Models\Calendar;
use Webkul\Manufacturing\Database\Factories\WorkCenterFactory;
use Webkul\Manufacturing\Enums\WorkCenterWorkingState;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;

class WorkCenter extends Model implements Sortable
{
    use HasFactory, SoftDeletes, SortableTrait;

    protected $table = 'manufacturing_work_centers';

    protected $fillable = [
        'sort',
        'color',
        'name',
        'code',
        'working_state',
        'note',
        'time_efficiency',
        'default_capacity',
        'costs_per_hour',
        'setup_time',
        'cleanup_time',
        'oee_target',
        'company_id',
        'calendar_id',
        'creator_id',
        'deleted_at',
    ];

    protected $casts = [
        'working_state'    => WorkCenterWorkingState::class,
        'time_efficiency'  => 'decimal:2',
        'costs_per_hour'   => 'decimal:4',
        'setup_time'       => 'decimal:4',
        'cleanup_time'     => 'decimal:4',
        'oee_target'       => 'decimal:2',
    ];

    public $sortable = [
        'order_column_name'  => 'sort',
        'sort_when_creating' => true,
    ];

    public function getModelTitle(): string
    {
        return __('manufacturing::models/work-center.title');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class, 'calendar_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class, 'work_center_id');
    }

    public function capacities(): HasMany
    {
        return $this->hasMany(WorkCenterCapacity::class, 'work_center_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'work_center_id');
    }

    public function productivityLogs(): HasMany
    {
        return $this->hasMany(WorkCenterProductivityLog::class, 'work_center_id');
    }

    public function alternativeWorkCenters(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_work_center_alternatives', 'work_center_id', 'alternative_work_center_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WorkCenterTag::class, 'manufacturing_work_center_tag', 'work_center_id', 'tag_id');
    }

    public function getCapacity(?Product $product = null): float
    {
        if (! $product) {
            return max((float) ($this->default_capacity ?? 1), 0.0001);
        }

        $capacity = $this->getMatchingCapacities($product)->first();

        return max((float) ($capacity?->capacity ?? $this->default_capacity ?? 1), 0.0001);
    }

    public function getExpectedDuration(?Product $product = null): float
    {
        if (! $product) {
            return (float) ($this->setup_time ?? 0) + (float) ($this->cleanup_time ?? 0);
        }

        $capacity = $this->getMatchingCapacities($product)->first();

        return (float) ($capacity?->time_start ?? $this->setup_time ?? 0)
            + (float) ($capacity?->time_stop ?? $this->cleanup_time ?? 0);
    }

    protected function getMatchingCapacities(Product $product): Collection
    {
        return $this->capacities
            ->filter(fn (WorkCenterCapacity $capacity): bool => (int) $capacity->product_id === (int) $product->getKey())
            ->values();
    }

    protected static function newFactory(): WorkCenterFactory
    {
        return WorkCenterFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $workCenter): void {
            $authUser = Auth::user();

            $workCenter->creator_id ??= $authUser?->id;
            $workCenter->company_id ??= $authUser?->default_company_id;
            $workCenter->working_state ??= WorkCenterWorkingState::NORMAL;
            $workCenter->time_efficiency ??= 100;
            $workCenter->default_capacity ??= 1;
            $workCenter->costs_per_hour ??= 0;
            $workCenter->setup_time ??= 0;
            $workCenter->cleanup_time ??= 0;
            $workCenter->oee_target ??= 90;
        });
    }

    public function computeWorkingState(): void
    {
        $productivityLog = $this->productivityLogs()
            ->whereNull('finished_at')
            ->first();

        if (! $productivityLog) {
            $this->working_state = WorkCenterWorkingState::NORMAL;
        } elseif (in_array($productivityLog->loss_type, ['productive', 'performance'])) {
            $this->working_state = WorkCenterWorkingState::DONE;
        } else {
            $this->working_state = WorkCenterWorkingState::BLOCKED;
        }
    }

    public function unblock()
    {
        if ($this->working_state !== WorkCenterWorkingState::BLOCKED) {
            throw new \Exception('It has already been unblocked.');
        }

        $this->productivityLogs()->whereNull('finished_at')->update(['finished_at' => now()]);
    }
}
