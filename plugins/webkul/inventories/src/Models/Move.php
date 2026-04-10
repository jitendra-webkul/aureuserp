<?php

namespace Webkul\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Database\Factories\MoveFactory;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\ProcureMethod;
use Webkul\Partner\Models\Partner;
use Webkul\Purchase\Models\OrderLine as PurchaseOrderLine;
use Webkul\Sale\Models\OrderLine as SaleOrderLine;
use Webkul\Security\Models\User;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

class Move extends Model
{
    use HasFactory;

    protected $table = 'inventories_moves';

    protected $fillable = [
        'name',
        'state',
        'origin',
        'procure_method',
        'reference',
        'description_picking',
        'next_serial',
        'next_serial_count',
        'is_favorite',
        'product_qty',
        'product_uom_qty',
        'quantity',
        'is_picked',
        'is_scraped',
        'is_inventory',
        'is_refund',
        'deadline',
        'reservation_date',
        'scheduled_at',
        'product_id',
        'uom_id',
        'source_location_id',
        'destination_location_id',
        'final_location_id',
        'partner_id',
        'operation_id',
        'rule_id',
        'operation_type_id',
        'origin_returned_move_id',
        'restrict_partner_id',
        'warehouse_id',
        'product_packaging_id',
        'scrap_id',
        'price_unit',
        'company_id',
        'creator_id',
        'procurement_group_id',
        'purchase_order_line_id',
        'sale_order_line_id',
    ];

    protected $casts = [
        'state'            => MoveState::class,
        'is_favorite'      => 'boolean',
        'is_picked'        => 'boolean',
        'is_scraped'       => 'boolean',
        'is_inventory'     => 'boolean',
        'is_refund'        => 'boolean',
        'reservation_date' => 'date',
        'scheduled_at'     => 'datetime',
        'deadline'         => 'datetime',
        'alert_Date'       => 'datetime',
    ];

    public function isPurchaseReturn()
    {
        return $this->destinationLocation->type === LocationType::SUPPLIER
            || (
                $this->originReturnedMove
                && $this->destinationLocation->id === $this->destinationLocation->company->inter_company_location_id
            );
    }

    public function isDropshipped()
    {
        return (
            $this->sourceLocation->type === LocationType::SUPPLIER
            || ($this->sourceLocation->type === LocationType::TRANSIT && ! $this->sourceLocation->company_id)
        )
            && (
                $this->destinationLocation->type === LocationType::CUSTOMER
                || ($this->destinationLocation->type === LocationType::TRANSIT && ! $this->destinationLocation->company_id)
            );
    }

    public function isDropshippedReturned()
    {
        return (
            $this->sourceLocation->type === LocationType::CUSTOMER
            || ($this->sourceLocation->type === LocationType::TRANSIT && ! $this->sourceLocation->company_id)
        )
            && (
                $this->destinationLocation->type === LocationType::SUPPLIER
                || ($this->destinationLocation->type === LocationType::TRANSIT && ! $this->destinationLocation->company_id)
            );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function finalLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function scrap(): BelongsTo
    {
        return $this->belongsTo(Scrap::class);
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }

    public function operationType(): BelongsTo
    {
        return $this->belongsTo(OperationType::class);
    }

    public function originReturnedMove(): BelongsTo
    {
        return $this->belongsTo(self::class);
    }

    public function restrictPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function packageLevel(): BelongsTo
    {
        return $this->belongsTo(PackageLevel::class);
    }

    public function productPackaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(MoveLine::class);
    }

    public function moveOrigins(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'inventories_move_destinations', 'destination_move_id', 'origin_move_id');
    }

    public function moveDestinations(): BelongsToMany
    {
        return $this->belongsToMany(Move::class, 'inventories_move_destinations', 'origin_move_id', 'destination_move_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldBypassReservation(): bool
    {
        return $this->sourceLocation->shouldBypassReservation() || ! $this->product->is_storable;
    }

    public function procurementGroup(): BelongsTo
    {
        return $this->belongsTo(ProcurementGroup::class, 'procurement_group_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function saleOrderLine(): BelongsTo
    {
        return $this->belongsTo(SaleOrderLine::class, 'sale_order_line_id');
    }

    protected static function newFactory(): MoveFactory
    {
        return MoveFactory::new();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($move) {
            $move->creator_id ??= Auth::id();

            $move->company_id ??= $move->operation?->company_id;

            $move->quantity ??= null;

            $move->computeState();
        });

        static::saving(function ($move) {
            $move->quantity ??= null;

            $move->computeWarehouseId();

            $move->computeName();

            $move->computeReference();

            $move->computeProductQty();

            $move->computeProcureMethod();

            $move->computePartnerId();

            $move->computeUOMId();

            $move->computeOperationTypeId();

            $move->computeSourceLocationId();

            $move->computeDestinationLocationId();
            
            $move->computeScheduledAt();

            $move->computeLines();
        });
    }

    public function computeWarehouseId()
    {
        $this->warehouse_id ??= $this->operation?->destinationLocation->warehouse_id;
    }

    public function computeState()
    {
        $this->state ??= $this->operation?->state->value;
    }

    public function computeName()
    {
        $this->name = $this->product->name;
    }

    public function computeReference()
    {
        $this->reference ??= $this->operation?->name;
    }

    public function computeProductQty()
    {
        $this->product_qty ??= $this->uom?->computeQuantity($this->product_uom_qty, $this->product->uom, roundingMethod: 'HALF-UP');
    }

    public function computeProcureMethod()
    {
        $this->procure_method ??= ProcureMethod::MAKE_TO_STOCK;
    }

    public function computeUOMId()
    {
        $this->uom_id ??= $this->product?->uom_id;
    }

    public function computePartnerId()
    {
        $this->partner_id ??= $this->operation?->partner_id;
    }

    public function computeOperationTypeId()
    {
        $this->operation_type_id ??= $this->operation?->operation_type_id;
    }

    public function computeSourceLocationId()
    {
        $this->source_location_id ??= $this->operation?->source_location_id ?? $this->operationType->source_location_id;
    }

    public function computeDestinationLocationId()
    {
        $this->destination_location_id ??= $this->operation?->destination_location_id ?? $this->operationType->destination_location_id;
    }

    public function computeScheduledAt()
    {
        $this->scheduled_at ??= $this->operation?->scheduled_at ?? now();
    }

    public function computeLines()
    {
        if (! $this->state) {
            return;
        }

        if (in_array($this->state, [MoveState::DRAFT, MoveState::DONE, MoveState::CANCELED])) {
            return;
        }

        $lines = $this->lines()->orderBy('created_at')->get();

        $remainingQty = ! is_null($this->quantity)
            ? $this->uom->computeQuantity($this->quantity, $this->product->uom, true, 'HALF-UP')
            : $this->product_qty;

        $isSupplierSource = $this->sourceLocation->type === LocationType::SUPPLIER;

        $processedKeys = collect();

        $availableQty = 0;

        $productQuantities = collect();

        if (! $isSupplierSource) {
            $parentPath = $this->sourceLocation->parent_path;
            $sourceLocationIds = ($parentPath && trim($parentPath, '/') !== '')
                ? Location::where('parent_path', 'LIKE', $parentPath . '%')->pluck('id')
                : collect([$this->source_location_id]);

            $productQuantities = ProductQuantity::query()
                ->with(['location', 'lot', 'package'])
                ->where('product_id', $this->product_id)
                ->whereIn('location_id', $sourceLocationIds)
                ->when(
                    $this->product->tracking === ProductTracking::LOT,
                    fn($query) => $query->whereNotNull('lot_id')
                )
                ->get();
        }

        foreach ($lines as $line) {
            if (! $isSupplierSource) {
                $locationQty = $productQuantities
                    ->where('location_id', $line->source_location_id)
                    ->where('lot_id', $line->lot_id)
                    ->where('package_id', $line->package_id)
                    ->first()?->quantity ?? 0;

                if ($locationQty <= 0) {
                    $line->delete();

                    continue;
                }
            }

            if ($remainingQty <= 0) {
                $line->delete();

                continue;
            }

            $newQty = $isSupplierSource
                ? min($line->uom_qty, $remainingQty)
                : min($line->uom_qty, $locationQty, $remainingQty);

            if ($newQty != $line->uom_qty) {
                $line->update([
                    'qty'     => $this->product->uom->computeQuantity($newQty, $this->uom, true, 'HALF-UP'),
                    'uom_qty' => $newQty,
                    'state'   => MoveState::ASSIGNED,
                ]);
            }

            $processedKeys->push("{$line->source_location_id}-{$line->lot_id}-{$line->package_id}");
            $availableQty += $newQty;
            $remainingQty = round($remainingQty - $newQty, 4);
        }

        if ($remainingQty > 0 && $isSupplierSource) {
            while ($remainingQty > 0) {
                $newQty = $this->product->tracking === ProductTracking::SERIAL ? 1 : $remainingQty;

                $this->lines()->create([
                    'qty'     => $this->product->uom->computeQuantity($newQty, $this->uom, true, 'HALF-UP'),
                    'uom_qty' => $newQty,
                    'state'   => MoveState::ASSIGNED,
                ]);

                $availableQty += $newQty;
                $remainingQty = round($remainingQty - $newQty, 4);
            }
        } elseif ($remainingQty > 0) {
            foreach ($productQuantities as $productQuantity) {
                if ($remainingQty <= 0) break;

                $key = "{$productQuantity->location_id}-{$productQuantity->lot_id}-{$productQuantity->package_id}";

                if ($processedKeys->contains($key) || $productQuantity->quantity <= 0) {
                    continue;
                }

                $newQty = min($productQuantity->quantity, $remainingQty);

                $this->lines()->create([
                    'qty'                => $this->product->uom->computeQuantity($newQty, $this->uom, true, 'HALF-UP'),
                    'uom_qty'            => $newQty,
                    'lot_name'           => $productQuantity->lot?->name,
                    'lot_id'             => $productQuantity->lot_id,
                    'package_id'         => $productQuantity->package_id,
                    'result_package_id'  => $newQty == $productQuantity->quantity ? $productQuantity->package_id : null,
                    'source_location_id' => $productQuantity->location_id,
                    'state'              => MoveState::ASSIGNED,
                ]);

                $availableQty += $newQty;
                $remainingQty  = round($remainingQty - $newQty, 4);
            }
        }

        [$state, $quantity] = match(true) {
            $availableQty <= 0                 => [MoveState::CONFIRMED, null],
            $availableQty < $this->product_qty => [
                MoveState::PARTIALLY_ASSIGNED,
                $this->product->uom->computeQuantity($availableQty, $this->uom, true, 'HALF-UP')
            ],
            default                            => [
                MoveState::ASSIGNED,
                $this->product->uom->computeQuantity($availableQty, $this->uom, true, 'HALF-UP')
            ],
        };

        $this->updateQuietly(['state' => $state, 'quantity' => $quantity]);

        if ($state !== MoveState::ASSIGNED) {
            $this->lines()->update(['state' => $state]);
        }
    }
}
