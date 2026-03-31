<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Manufacturing\Database\Factories\OperationFactory;
use Webkul\Manufacturing\Enums\OperationTimeMode;
use Webkul\Manufacturing\Enums\OperationWorksheetType;
use Webkul\Product\Models\ProductAttributeValue;
use Webkul\Security\Models\User;

class Operation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_operations';

    protected $fillable = [
        'sort',
        'time_mode_batch',
        'name',
        'worksheet_type',
        'worksheet',
        'worksheet_google_slide_url',
        'time_mode',
        'note',
        'manual_cycle_time',
        'work_center_id',
        'bill_of_material_id',
        'creator_id',
        'deleted_at',
    ];

    protected $casts = [
        'worksheet_type'    => OperationWorksheetType::class,
        'time_mode'         => OperationTimeMode::class,
        'manual_cycle_time' => 'decimal:4',
    ];

    public function getModelTitle(): string
    {
        return __('manufacturing::models/operation.title');
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class, 'work_center_id')->withTrashed();
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class, 'operation_id');
    }

    public function byproducts(): HasMany
    {
        return $this->hasMany(BillOfMaterialByproduct::class, 'operation_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'operation_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'manufacturing_operation_attribute_values', 'operation_id', 'product_attribute_value_id');
    }

    public function blockedByOperations(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_operation_dependencies', 'operation_id', 'depends_on_operation_id');
    }

    public function dependentOperations(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'manufacturing_operation_dependencies', 'depends_on_operation_id', 'operation_id');
    }

    protected static function newFactory(): OperationFactory
    {
        return OperationFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $operation): void {
            $operation->creator_id ??= Auth::id();
            $operation->worksheet_type ??= OperationWorksheetType::TEXT;
            $operation->time_mode ??= OperationTimeMode::MANUAL;
        });
    }
}
