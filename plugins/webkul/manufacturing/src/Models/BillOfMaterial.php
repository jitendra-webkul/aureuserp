<?php

namespace Webkul\Manufacturing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Models\OperationType;
use Webkul\Manufacturing\Database\Factories\BillOfMaterialFactory;
use Webkul\Manufacturing\Enums\BillOfMaterialConsumption;
use Webkul\Manufacturing\Enums\BillOfMaterialReadyToProduce;
use Webkul\Manufacturing\Enums\BillOfMaterialType;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

class BillOfMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_bills_of_materials';

    protected $fillable = [
        'code',
        'type',
        'ready_to_produce',
        'consumption',
        'quantity',
        'allow_operation_dependencies',
        'produce_delay',
        'days_to_prepare_mo',
        'product_id',
        'uom_id',
        'operation_type_id',
        'company_id',
        'creator_id',
        'deleted_at',
    ];

    protected $casts = [
        'type'                         => BillOfMaterialType::class,
        'ready_to_produce'             => BillOfMaterialReadyToProduce::class,
        'consumption'                  => BillOfMaterialConsumption::class,
        'quantity'                     => 'decimal:4',
        'allow_operation_dependencies' => 'boolean',
        'produce_delay'                => 'integer',
        'days_to_prepare_mo'           => 'integer',
    ];

    public function getModelTitle(): string
    {
        return __('manufacturing::models/bill-of-material.title');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class)->withTrashed();
    }

    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class)->withTrashed();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillOfMaterialLine::class, 'bill_of_material_id');
    }

    public function byproducts(): HasMany
    {
        return $this->hasMany(BillOfMaterialByproduct::class, 'bill_of_material_id');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class, 'bill_of_material_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'bill_of_material_id');
    }

    public function unbuildOrders(): HasMany
    {
        return $this->hasMany(UnbuildOrder::class, 'bill_of_material_id');
    }

    protected static function newFactory(): BillOfMaterialFactory
    {
        return BillOfMaterialFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $billOfMaterial): void {
            $authUser = Auth::user();

            $billOfMaterial->creator_id ??= $authUser?->id;
            $billOfMaterial->company_id ??= $authUser?->default_company_id;
            $billOfMaterial->type ??= BillOfMaterialType::NORMAL;
            $billOfMaterial->ready_to_produce ??= BillOfMaterialReadyToProduce::ALL_AVAILABLE;
            $billOfMaterial->consumption ??= BillOfMaterialConsumption::WARNING;
            $billOfMaterial->produce_delay ??= 0;
            $billOfMaterial->days_to_prepare_mo ??= 0;
        });
    }
}
