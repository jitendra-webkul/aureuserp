<?php

namespace Webkul\Inventory\Models;

use Illuminate\Support\Collection;
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
use Webkul\Inventory\Enums\GroupPropagation;
use Webkul\Inventory\Facades\Inventory as InventoryFacade;
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
        'quantity'         => 'float',
        'product_qty'      => 'float',
        'product_uom_qty'  => 'float',
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

    protected array $context = [];

    public function setContext(array $context)
    {
        $this->context = array_merge($this->context, $context);

        return $this;
    }

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

    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(Route::class, 'inventories_route_moves', 'move_id', 'route_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldBypassReservation($forceLocation = null): bool
    {
        $location = $forceLocation ?? $this->sourceLocation;

        return $location->shouldBypassReservation() || ! $this->product->is_storable;
    }

    public function skipPush()
    {
        return $this->is_inventory
            || (
                $this->moveDestinations->isNotEmpty()
                && $this->moveDestinations->some(fn ($move) => $move->sourceLocation->isChildOf($this->destinationLocation))
            )
            || (
                $this->location_final_id
                && $this->finalLocation->isChildOf($this->destinationLocation)
            );
    }

    public function procurementGroup(): BelongsTo
    {
        return $this->belongsTo(ProcurementGroup::class, 'procurement_group_id');
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function purchaseOrderLines(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseOrderLine::class, 'purchases_order_line_moves', 'inventory_move_id', 'purchase_order_line_id');
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

            $move->state ??= MoveState::DRAFT;
        });

        static::saving(function ($move) {
            $move->computeWarehouseId();

            $move->computeName();

            $move->computeReference();

            $move->computeUOMId();

            $move->computeProductQty();

            $move->computeProductUOMQty();

            $move->computeProcureMethod();

            $move->computePartnerId();

            $move->computeOperationTypeId();

            $move->computeSourceLocationId();

            $move->computeDestinationLocationId();
            
            $move->computeScheduledAt();
        });

        static::updated(function ($move) {
            if ($move->wasChanged('quantity')) {
                $move->setQuantity();
            }

            $receiptMovesToReassign = collect();

            $moveToRecomputeState = collect();

            if ($move->wasChanged('product_uom_qty')) {
                if (! ($move->context['do_not_unreserve'] ?? false)) {
                    $shouldUnreserve = ! in_array($move->state, [MoveState::DRAFT, MoveState::DONE, MoveState::CANCELED])
                        && float_compare($move->quantity, $move->product_uom_qty ?? null, precisionRounding: $move->uom->rounding) === 1;

                    if ($shouldUnreserve) {
                        InventoryFacade::doUnreserve(collect([$move]));

                        if ($move->sourceLocation->type === LocationType::SUPPLIER) {
                            $receiptMovesToReassign->push($move->refresh());
                        }
                    } else {
                        if ($move->state === MoveState::ASSIGNED) {
                            $move->update(['state' => MoveState::PARTIALLY_ASSIGNED]);
                        }

                        if (
                            $move->sourceLocation->type === LocationType::SUPPLIER
                            && in_array($move->state, [MoveState::PARTIALLY_ASSIGNED, MoveState::ASSIGNED])
                        ) {
                            $receiptMovesToReassign->push($move);
                        } else {
                            $moveToRecomputeState->push($move);
                        }
                    }
                }
            }

            if ($move->wasChanged('state')) {
                $move->lines->each(fn ($moveLine) => $moveLine->update(['state' => $moveLine->move->refresh()->state]));

                if ($operation = $move->operation) {
                    $operation->refresh();

                    $operation->computeState();

                    $operation->save();
                }
            }

            if ($move->wasChanged('is_picked')) {
                $move->lines->each(fn($moveLine) => $moveLine->update(['is_picked' => $move->is_picked]));
            }

            if ($receiptMovesToReassign->isNotEmpty()) {
                InventoryFacade::assignMoves($receiptMovesToReassign);
            }
        });

        static::deleting(function ($move) {
            $move->lines->each->delete();
        });
    }

    public function computeWarehouseId()
    {
        $this->warehouse_id ??= $this->operation?->destinationLocation->warehouse_id;
    }

    public function computeName()
    {
        $this->name ??= $this->product->name;
    }

    public function computeReference()
    {
        $this->reference ??= $this->operation?->name;
    }

    public function computeProductQty()
    {
        $this->product_qty ??= $this->uom?->computeQuantity($this->product_uom_qty, $this->product->uom, roundingMethod: 'HALF-UP');
    }

    public function computeProductUOMQty()
    {
        $this->product_uom_qty ??= $this->product->uom?->computeQuantity($this->product_qty, $this->uom, roundingMethod: 'HALF-UP');
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

    public function prepareProcurementOrigin()
    {
        return $this->procurementGroup?->name ?? ($this->origin ?: $this->operation?->name ?: '/');
    }

    public function setQuantity()
    {
        $processDecrease = function (Move $move, float $quantity) {
            $toDelete = collect();

            foreach ($move->lines->sortBy('id')->reverse() as $ml) {
                if (float_is_zero($quantity, precisionRounding: $move->uom->rounding)) {
                    break;
                }

                $qtyMlDec = min($ml->qty, $ml->uom->computeQuantity($quantity, $ml->uom, round: false));

                if (float_is_zero($qtyMlDec, precisionRounding: $ml->uom->rounding)) {
                    continue;
                }

                if (
                    float_compare($ml->qty, $qtyMlDec, precisionRounding: $ml->uom->rounding) === 0 &&
                    ! in_array($ml->state, [MoveState::DONE, MoveState::CANCELED])
                ) {
                    $toDelete->push($ml->id);
                } else {
                    $ml->decrement('qty', $qtyMlDec);
                }

                $quantity -= $move->uom->computeQuantity($qtyMlDec, $move->uom, round: false);
            }

            MoveLine::whereIn('id', $toDelete)->get()->each->delete();
        };

        $processIncrease = function (Move $move, float $quantity) {
            $move->setQuantityDone($move->quantity);
        };

        $errors = [];

        $uomQty = float_round($this->quantity, precisionRounding: $this->uom->rounding, roundingMethod: 'HALF-UP');

        $qty = float_round($this->quantity, precisionDigits: 2, roundingMethod: 'HALF-UP');

        if (float_compare($uomQty, $qty, precisionDigits: 2) !== 0) {
            $errors[] = __('The quantity done for the product :product doesn\'t respect the rounding precision defined on the unit of measure :unit. Please change the quantity done or the rounding precision of your unit of measure.', [
                'product' => $this->product->name,
                'unit'    => $this->uom->name,
            ]);
        } else {
            $deltaQty = $this->quantity - $this->getQuantitySml();

            if (float_compare($deltaQty, 0, precisionRounding: $this->uom->rounding) > 0) {
                $processIncrease($this, $deltaQty);
            } elseif (float_compare($deltaQty, 0, precisionRounding: $this->uom->rounding) < 0) {
                $processDecrease($this, abs($deltaQty));
            }
        }

        if (! empty($errors)) {
            throw new \Exception(implode("\n", $errors));
        }
    }

    public function setQuantityDone(float $quantity)
    {
        $this->setQuantityDonePrepareVals($quantity);
    }

    public function setQuantityDonePrepareVals(float $qty): void
    {
        $toDelete = collect();

        $toUpdate = [];

        $toCreate = [];

        foreach ($this->lines as $ml) {
            $mlQty = $ml->qty;

            if (float_is_zero($qty, precisionRounding: $this->uom->rounding)) {
                $toDelete->push($ml->id);

                continue;
            }

            if (float_compare($mlQty, 0, precisionRounding: $ml->uom->rounding) <= 0) {
                continue;
            }

            if ($ml->uom->id !== $this->uom->id) {
                $mlQty = $ml->uom->computeQuantity($mlQty, $this->uom, round: false);
            }

            $takenQty = min($qty, $mlQty);

            if ($ml->uom->id !== $this->uom->id) {
                $takenQty = $this->uom->computeQuantity($takenQty, $ml->uom, round: false);
            }

            $takenQty = float_round($takenQty, precisionRounding: $ml->uom->rounding);

            $toUpdate[] = ['id' => $ml->id, 'qty' => $takenQty];

            if ($ml->uom->id !== $this->uom->id) {
                $takenQty = $ml->uom->computeQuantity($takenQty, $this->uom, round: false);
            }

            $qty -= $takenQty;
        }

        if (float_compare($qty, 0.0, precisionRounding: $this->uom->rounding) > 0) {
            if ($this->product->tracking !== ProductTracking::SERIAL) {
                $vals = $this->prepareLineValues(quantity: 0);

                $vals['qty'] = $qty;

                $toCreate[] = $vals;
            } else {
                $uomQty = $this->uom->computeQuantity($qty, $this->product->uom);

                for ($i = 0; $i < (int) $uomQty; $i++) {
                    $vals = $this->prepareLineValues(quantity: 0);

                    $vals['qty'] = 1;

                    $vals['product_uom_id'] = $this->product->uom->id;

                    $toCreate[] = $vals;
                }
            }
        }

        MoveLine::whereIn('id', $toDelete)->get()->each->delete();

        foreach ($toUpdate as $update) {
            $moveLine = MoveLine::find($update['id']);

            $moveLine->update(['qty' => $update['qty']]);
        }

        foreach ($toCreate as $vals) {
            $this->lines()->create($vals);
        }
    }

    public function computeQuantity()
    {
        $moveLineIds = $this->lines->pluck('id')->all();

        $data = MoveLine::whereIn('id', $moveLineIds)
            ->groupBy('move_id', 'uom_id')
            ->selectRaw('move_id, uom_id, SUM(qty) as qty_sum')
            ->get();

        $sumQty = [];

        foreach ($data as $row) {
            $uom = $this->uom;
            
            $sumQty[$row->move_id] = ($sumQty[$row->move_id] ?? 0.0) + $row->uom->computeQuantity($row->qty_sum, $uom, round: false);
        }

        $this->quantity = $sumQty[$this->id] ?? 0.0;
    }

    public function getQuantitySml()
    {
        $quantity = 0;

        $this->lines()->get()->each(function ($moveLine) use (&$quantity) {
            $quantity += $moveLine->uom->computeQuantity($moveLine->qty, $this->product->uom, round: false);
        });

        return $quantity;
    }

    public function split(float $qty, ?int $restrictPartnerId = null): array
    {
        if (in_array($this->state, [MoveState::DONE, MoveState::CANCELED])) {
            throw new \Exception(__('You cannot split a stock move that has been set to \'Done\' or \'Cancel\'.'));
        }

        if ($this->state === MoveState::DRAFT) {
            throw new \Exception(__('You cannot split a draft move. It needs to be confirmed first.'));
        }

        if (float_is_zero($qty, precisionRounding: $this->product->uom->rounding)) {
            return [];
        }

        $uomQty = $this->product->uom->computeQuantity($qty, $this->uom, roundingMethod: 'HALF-UP');

        if (
            float_compare(
                $qty,
                $this->uom->computeQuantity($uomQty, $this->product->uom, roundingMethod: 'HALF-UP'),
                precisionDigits: 2
            ) === 0
        ) {
            $defaults = $this->prepareMoveSplitVals($uomQty);
        } else {
            $defaults = $this->prepareMoveSplitVals($qty, forceUomId: $this->product->uom_id);
        }

        if ($restrictPartnerId) {
            $defaults['restrict_partner_id'] = $restrictPartnerId;
        }

        if ($this->context['source_location_id'] ?? false) {
            $defaults['destination_location_id'] = $this->context['source_location_id'];
        }

        $newMoveVals = array_merge(
            $this->toArray(),
            $defaults,
            ['id' => null]
        );

        $newProductQty = $this->product->uom->computeQuantity(
            max(0, $this->product_qty - $qty),
            $this->uom,
            round: false
        );

        $newProductQty = float_round($newProductQty, precisionDigits: 2);

        $this->setContext(['do_not_unreserve' => true]);

        $this->update(['product_uom_qty' => $newProductQty]);

        return $newMoveVals;
    }

    public function prepareMoveSplitVals(float $qty, ?int $forceUomId = null): array
    {
        $values = [
            'state'                   => MoveState::DRAFT,
            'product_uom_qty'         => $qty,
            'product_qty'             => $this->uom->computeQuantity($qty, $this->product->uom, true, 'HALF-UP'),
            'quantity'                => 0,
            'is_picked'               => false,
            'procure_method'          => $this->procure_method,
            'price_unit'              => $this->price_unit,
            'deadline'                => $this->deadline,
            'rule_id'                 => null,
            'origin_returned_move_id' => $this->origin_returned_move_id,
            'move_origin_ids'         => $this->moveOrigins->pluck('id')->all(),
            'move_destination_ids'    => $this->moveDestinations
                ->filter(fn($x) => ! in_array($x->state, [MoveState::DONE, MoveState::CANCELED]))
                ->pluck('id')
                ->all(),
        ];

        if ($forceUomId) {
            $values['uom_id'] = $forceUomId;
        }

        return $values;
    }

    public function computeState()
    {
        $rounding = $this->uom->rounding;

        if (
            in_array($this->state, [MoveState::CANCELED, MoveState::DONE])
            || ($this->state === MoveState::DRAFT && ! $this->quantity)
        ) {
            return;
        } elseif (float_compare($this->quantity, $this->product_uom_qty, precisionRounding: $rounding) >= 0) {
            $this->state = MoveState::ASSIGNED;
        } elseif ($this->quantity && float_compare($this->quantity, $this->product_uom_qty, precisionRounding: $rounding) <= 0) {
            $this->state = MoveState::PARTIALLY_ASSIGNED;
        } elseif (
            ($this->procure_method === ProcureMethod::MAKE_TO_ORDER && $this->moveOrigins->isEmpty())
            || (
                $this->moveOrigins->isNotEmpty()
                && $this->moveOrigins->some(
                    fn($orig) => float_compare($orig->product_uom_qty, 0, precisionRounding: $orig->uom->rounding) > 0
                    && ! in_array($orig->state, [MoveState::DONE, MoveState::CANCELED])
                )
            )
        ) {
            $this->state = MoveState::WAITING;
        } else {
            $this->state = MoveState::CONFIRMED;
        }
    }

    public function prepareProcurementValues(): array
    {
        $procurementGroup = $this->procurementGroup ?: false;

        if ($this->rule) {
            if (
                $this->rule->group_propagation_option === GroupPropagation::FIXED
                && $this->rule->procurement_group_id
            ) {
                $procurementGroup = $this->rule->procurementGroup;
            } elseif ($this->rule->group_propagation_option === GroupPropagation::NONE) {
                $procurementGroup = false;
            }
        }

        $datesInfo = [
            'planned' => $this->scheduled_at,
        ];

        if (
            $this->location?->warehouse?->lotStock?->parent_path
            && str_contains(
                $this->location->parent_path ?? '',
                $this->location->warehouse->lotStock->parent_path
            )
        ) {
            $datesInfo = $this->product->getDatesInfo(
                $this->date,
                $this->location,
                $this->routes
            );
        }

        $warehouse = $this->warehouse ?: $this->operationType?->warehouse;

        if (! $this->location?->warehouse) {
            $warehouse = $this->rule?->propagateWarehouse;
        }

        $moveDestinations = collect();

        if ($this->procure_method === ProcureMethod::MAKE_TO_ORDER) {
            $moveDestinations = collect([$this]);
        }

        return [
            'planned'           => $datesInfo['planned'] ?? null,
            'ordered_at'        => $datesInfo['ordered_at'] ?? null,
            'deadline'          => $this->deadline,
            'move_destinations' => $moveDestinations,
            'procurement_group' => $procurementGroup,
            'routes'            => $this->routes,
            'warehouse'         => $warehouse,
            'order_point'       => $this->orderPoint,
            'product_packaging' => $this->productPackaging,
        ];
    }

    public function prepareLineValues($quantity = null, $reservedQuantity = null): array
    {
        $values = [
            'move_id'                 => $this->id,
            'product_id'              => $this->product_id,
            'uom_id'                  => $this->uom_id,
            'source_location_id'      => $this->source_location_id,
            'destination_location_id' => $this->destination_location_id,
            'operation_id'            => $this->operation_id,
            'company_id'              => $this->company_id,
        ];

        if ($quantity) {
            $uomQuantity = $this->product->uom->computeQuantity(
                $quantity,
                $this->uom,
                roundingMethod: 'HALF-UP'
            );

            $uomQuantity = float_round($uomQuantity, precisionDigits: 2);

            $uomQuantityBackToProductUom = $this->uom->computeQuantity(
                $uomQuantity,
                $this->product->uom,
                roundingMethod: 'HALF-UP'
            );

            if (float_compare($quantity, $uomQuantityBackToProductUom, precisionDigits: 2) === 0) {
                $values = array_merge($values, [
                    'qty' => $uomQuantity,
                ]);
            } else {
                $values = array_merge($values, [
                    'qty'    => $quantity,
                    'uom_id' => $this->product->uom->id,
                ]);
            }
        }

        $package = null;

        if ($reservedQuantity) {
            $package = $reservedQuantity->package;

            $values = array_merge($values, [
                'source_location_id' => $reservedQuantity->source_location_id,
                'lot_id'             => $reservedQuantity->lot_id,
                'package_id'         => $package?->id,
            ]);
        }

        return $values;
    }

    public function getAvailableMoveLines(Collection $assignedMovesIds, Collection $partiallyAssignedMovesIds): array
    {
        $groupedMoveLinesIn = $this->getAvailableMoveLinesIn();

        $groupedMoveLinesOut = $this->getAvailableMoveLinesOut($assignedMovesIds, $partiallyAssignedMovesIds);

        $rounding = $this->product->uom->rounding;

        $availableMoveLines = [];

        foreach ($groupedMoveLinesIn as $key => $quantity) {
            $net = $quantity - ($groupedMoveLinesOut[$key] ?? 0);

            if (float_compare($net, 0, precisionRounding: $rounding) > 0) {
                $availableMoveLines[$key] = $net;
            }
        }

        return $availableMoveLines;
    }

    public function getAvailableMoveLinesIn(): array
    {
        $groupedMoveLinesIn = [];

        $moveLines = $this->moveOrigins
            ->flatMap->moveDestinations
            ->flatMap->moveOrigins
            ->filter(fn (Move $m) => $m->state === MoveState::DONE)
            ->flatMap->lines;

        $grouped = $moveLines->groupBy(fn($ml) => implode('_', [
            $ml->destination_location_id,
            $ml->lot_id,
            $ml->result_package_id,
        ]));

        foreach ($grouped as $key => $lines) {
            $quantity = 0.0;

            foreach ($lines as $ml) {
                $quantity += $ml->uom->computeQuantity($ml->qty, $ml->product->uom);
            }

            $groupedMoveLinesIn[$key] = $quantity;
        }

        return $groupedMoveLinesIn;
    }

    public function getAvailableMoveLinesOut(Collection $assignedMovesIds, Collection $partiallyAssignedMovesIds): array
    {
        $groupedMoveLinesOut = [];

        $siblingsOutMoves = $this->moveOrigins
            ->flatMap->moveDestinations
            ->filter(fn (Move $m) => $m->id !== $this->id);

        $keyFn = fn ($ml) => implode('_', [$ml->source_location_id, $ml->lot_id, $ml->package_id]);

        $doneMoveLines = $siblingsOutMoves
            ->filter(fn (Move $m) => $m->state === MoveState::DONE)
            ->flatMap->lines;

        foreach ($doneMoveLines->groupBy($keyFn) as $key => $lines) {
            $quantity = 0.0;

            foreach ($lines as $ml) {
                $quantity += $ml->uom->computeQuantity($ml->qty, $ml->product->uom);
            }

            $groupedMoveLinesOut[$key] = ($groupedMoveLinesOut[$key] ?? 0.0) + $quantity;
        }

        $reservedMoveLines = $siblingsOutMoves
            ->filter(fn (Move $m) => in_array($m->state, [MoveState::ASSIGNED, MoveState::PARTIALLY_ASSIGNED])
                || $assignedMovesIds->contains($m->id)
                || $partiallyAssignedMovesIds->contains($m->id)
            )
            ->flatMap->lines;

        foreach ($reservedMoveLines->groupBy($keyFn) as $key => $lines) {
            $groupedMoveLinesOut[$key] = ($groupedMoveLinesOut[$key] ?? 0.0) + $lines->sum('uom_qty');
        }

        return $groupedMoveLinesOut;
    }

    public function updateReservedQuantity(
        float $need,
        Location $location,
        ?Lot $lot = null,
        ?Package $package = null,
        ?Partner $partner = null,
        bool $strict = true
    ): float
    {
        $rounding = $this->product->uom->rounding;

        $quants = ProductQuantity::getReserveQuantity(
            $this->product,
            $location,
            $need,
            productPackaging: $this->productPackaging,
            uom: $this->uom,
            lot: $lot,
            package: $package,
            partner: $partner,
            strict: $strict,
        );

        $takenQuantity = 0.0;

        $candidateLines = [];

        foreach ($this->lines as $line) {
            if ($line->result_package_id || $this->product->tracking === ProductTracking::SERIAL) {
                continue;
            }

            $candidateKey = implode('_', [
                $line->source_location_id,
                $line->lot_id,
                $line->package_id,
                $line->partner_id,
            ]);

            $candidateLines[$candidateKey] = $line;
        }

        $groupedQuants = [];

        foreach ($quants as [$quant, $quantity]) {
            $groupKey = implode('_', [
                $quant->location_id,
                $quant->lot_id,
                $quant->package_id,
                $quant->partner_id,
            ]);

            if (! isset($groupedQuants[$groupKey])) {
                $groupedQuants[$groupKey] = [$quant, $quantity];
            } else {
                $groupedQuants[$groupKey][1] += $quantity;
            }
        }

        $moveLineVals = [];

        foreach ($groupedQuants as [$reservedQuant, $quantity]) {
            $takenQuantity += $quantity;

            $candidateKey = implode('_', [
                $reservedQuant->location_id,
                $reservedQuant->lot_id,
                $reservedQuant->package_id,
                $reservedQuant->partner_id,
            ]);

            $toUpdate = $candidateLines[$candidateKey] ?? null;

            if ($toUpdate) {
                $uomQuantity = $this->product->uom->computeQuantity($quantity, $toUpdate->uom, roundingMethod: 'HALF-UP');

                $uomQuantity = float_round($uomQuantity, precisionRounding: $rounding);

                $uomQuantityBackToProductUom = $toUpdate->uom->computeQuantity($uomQuantity, $this->product->uom, roundingMethod: 'HALF-UP');
            }

            if ($toUpdate && float_compare($quantity, $uomQuantityBackToProductUom, precisionRounding: $rounding) === 0) {
                $toUpdate->update(['uom_qty' => $toUpdate->uom_qty + $uomQuantity]);
            } else {
                if (
                    $this->product->tracking === ProductTracking::SERIAL
                    && ($this->operationType?->use_create_lots || $this->operationType?->use_existing_lots)
                ) {
                    array_push($moveLineVals, ...$this->addSerialMoveLineToValsList($reservedQuant, $quantity));
                } else {
                    $moveLineVals[] = $this->prepareLineValues(quantity: $quantity, reservedQuantity: $reservedQuant);
                }
            }
        }

        if (! empty($moveLineVals)) {
            foreach ($moveLineVals as $vals) {
                $this->lines()->create($vals);
            }
        }

        return $takenQuantity;
    }

    public function addSerialMoveLineToValsList(ProductQuantity $reservedQuant, float $quantity): array
    {
        return array_map(
            fn () => $this->prepareLineValues(quantity: 1, reservedQuantity: $reservedQuant),
            range(0, (int) $quantity - 1)
        );
    }
}
