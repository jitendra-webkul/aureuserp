<?php

namespace Webkul\Inventory;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Webkul\Inventory\Enums\MoveType;
use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\OperationState;
use Webkul\Inventory\Enums\ProcureMethod;
use Webkul\Inventory\Enums\RuleAction;
use Webkul\Inventory\Enums\RuleAuto;
use Webkul\Inventory\Enums\GroupPropagation;
use Webkul\Inventory\Filament\Clusters\Operations\Resources\OperationResource;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Move;
use Webkul\Inventory\Models\Operation;
use Webkul\Inventory\Models\OperationType;
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Rule;
use Webkul\PluginManager\Package;
use Webkul\Purchase\Models\PurchaseOrder;
use Webkul\Purchase\Models\OrderLine as PurchaseOrderLine;
use Webkul\Purchase\Facades\PurchaseOrder as PurchaseOrderFacade;
use Webkul\Account\Facades\Tax as TaxFacade;
use Webkul\Purchase\Enums as PurchaseOrderEnums;
use Webkul\Sale\Facades\SaleOrder as SaleFacade;

class InventoryManager
{
    public function checkTransferAvailability(Operation $record): Operation
    {
        if ($record->state !== OperationState::DRAFT) {
            return $record;
        }

        $record->moves->each(fn (Move $move) => $move->computeLines());

        $record->computeState();

        $record->save();

        return $record;
    }

    public function confirmTransfer(Operation $record): Operation
    {
        if ($record->state !== OperationState::DRAFT) {
            return $record;
        }

        $this->confirmMoves($record->moves);

        $record->computeState();

        $record->save();

        return $record;
    }

    public function confirmMoves($moves)
    {
        $moves->each(fn (Move $move) => $move->update(['state' => MoveState::CONFIRMED]));

        return;

        $movesToCreateProcurement = collect();

        $movesToConfirm = collect();

        $movesWaiting = collect();

        $movesToAssign = [];

        foreach ($moves as $move) {
            if ($move->moveOrigins->isNotEmpty()) {
                $movesWaiting->push($move);
            } elseif ($move->procure_method === ProcureMethod::MAKE_TO_ORDER) {
                $movesWaiting->push($move);

                $movesToCreateProcurement->push($move);
            } elseif ($move->rule?->procure_method === ProcureMethod::MTS_ELSE_MTO) {
                $movesToCreateProcurement->push($move);

                $movesToConfirm->push($move);
            } else {
                $movesToConfirm[] = $move;
            }

            if (! $move->operation_id and $move->operation_type_id) {
                $key = implode('_', $this->keyAssignPicking($move));

                $movesToAssign[$key] = $move;
            }
        }
    }

    public function validateTransfer(Operation $record): Operation
    {
        $record->moves->each(fn (Move $move) => $move->computeLines());

        foreach ($record->moves as $move) {
            $move->update([
                'state'     => MoveState::DONE,
                'is_picked' => true,
            ]);

            foreach ($move->lines as $moveLine) {
                $moveLine->update(['state' => MoveState::DONE]);

                $moveLine->transferInventories();
            }
        }

        $record->refresh()->computeState();

        $record->save();

        if (Package::isPluginInstalled('purchases')) {
            foreach ($record->purchaseOrders as $purchaseOrder) {
                PurchaseOrderFacade::computePurchaseOrder($purchaseOrder);
            }
        }

        if (Package::isPluginInstalled('sales')) {
            if ($record->saleOrder) {
                SaleFacade::computeSaleOrder($record->saleOrder);
            }
        }

        $this->applyPushRules($record->moves);

        return $record;
    }

    public function cancelTransfer(Operation $record): Operation
    {
        foreach ($record->moves as $move) {
            $move->update([
                'state'    => MoveState::CANCELED,
                'quantity' => 0,
            ]);

            $move->lines()->delete();
        }

        $record->computeState();

        $record->save();

        return $record;
    }

    public function returnTransfer(Operation $record): Operation
    {
        $newOperation = $record->replicate()->fill([
            'state'                   => OperationState::DRAFT,
            'origin'                  => 'Return of '.$record->name,
            'operation_type_id'       => $record->operationType->returnOperationType?->id ?? $record->operation_type_id,
            'source_location_id'      => $record->destination_location_id,
            'destination_location_id' => $record->source_location_id,
            'return_id'               => $record->id,
            'user_id'                 => Auth::id(),
            'creator_id'              => Auth::id(),
        ]);

        $newOperation->save();

        foreach ($record->moves as $move) {
            $newMove = $move->replicate()->fill([
                'operation_id'            => $newOperation->id,
                'reference'               => $newOperation->name,
                'state'                   => MoveState::DRAFT,
                'is_refund'               => true,
                'product_qty'             => $move->product_qty,
                'product_uom_qty'         => $move->product_uom_qty,
                'source_location_id'      => $move->destination_location_id,
                'destination_location_id' => $move->source_location_id,
                'origin_returned_move_id' => $move->id,
                'operation_type_id'       => $newOperation->operation_type_id,
            ]);

            $newMove->save();
        }

        $newOperation->refresh();

        $newOperation = $this->confirmTransfer($newOperation);

        if (Package::isPluginInstalled('purchases')) {
            $newOperation->purchaseOrders()->attach($record->purchaseOrders->pluck('id'));
        }

        $url = OperationResource::getUrl('view', ['record' => $record]);

        $newOperation->addMessage([
            'body' => "This transfer has been created from <a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 dark:text-primary-400\">{$record->name}</a>.",
            'type' => 'comment',
        ]);

        $url = OperationResource::getUrl('view', ['record' => $newOperation]);

        $record->addMessage([
            'body' => "The return <a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 dark:text-primary-400\">{$newOperation->name}</a> has been created.",
            'type' => 'comment',
        ]);

        return $newOperation;
    }

    /**
     * Process back order for the operation.
     */
    public function createBackOrder(Operation $record): void
    {
        $newOperation = $record->replicate()->fill([
            'state'         => OperationState::DRAFT,
            'origin'        => $record->origin ?? $record->name,
            'back_order_id' => $record->id,
            'user_id'       => Auth::id(),
            'creator_id'    => Auth::id(),
        ]);

        $newOperation->save();

        foreach ($record->moves as $move) {
            if ($move->product_uom_qty <= $move->quantity) {
                continue;
            }

            $remainingQty = round($move->product_uom_qty - $move->quantity, 4);

            $newMove = $move->replicate()->fill([
                'operation_id'    => $newOperation->id,
                'reference'       => $newOperation->name,
                'state'           => MoveState::DRAFT,
                'product_qty'     => $move->uom->computeQuantity($remainingQty, $move->product->uom, true, 'HALF-UP'),
                'product_uom_qty' => $remainingQty,
                'quantity'        => $remainingQty,
            ]);

            $newMove->save();
        }

        $newOperation->refresh();

        $newOperation = $this->confirmTransfer($newOperation);

        if (Package::isPluginInstalled('purchases')) {
            $newOperation->purchaseOrders()->attach($record->purchaseOrders->pluck('id'));

            foreach ($record->purchaseOrders as $purchaseOrder) {
                PurchaseOrderFacade::computePurchaseOrder($purchaseOrder);
            }
        }

        $url = OperationResource::getUrl('view', ['record' => $record]);

        $newOperation->addMessage([
            'body' => "This transfer has been created from <a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 dark:text-primary-400\">{$record->name}</a>.",
            'type' => 'comment',
        ]);

        $url = OperationResource::getUrl('view', ['record' => $newOperation]);

        $record->addMessage([
            'body' => "The backorder <a href=\"{$url}\" target=\"_blank\" class=\"text-primary-600 dark:text-primary-400\">{$newOperation->name}</a> has been created.",
            'type' => 'comment',
        ]);
    }

    /**
     * Apply push rules for the operation.
     */
    public function applyPushRules($moves): void
    {
        $rules = [];

        foreach ($moves as $move) {
            if ($move->origin_returned_move_id) {
                continue;
            }

            $rule = $this->getPushRule($move->product, $move->destinationLocation, [
                'packaging' => $move->productPackaging,
                'warehouse' => $move->warehouse,
            ]);

            if (! $rule) {
                continue;
            }

            $pushedMove = $this->runPushRule($rule, $move);

            if (! isset($rules[$rule->id])) {
                $rules[$rule->id] = [
                    'rule'      => $rule,
                    'operation' => $pushedMove->operation,
                    'moves'     => [$pushedMove],
                ];
            } else {
                $rules[$rule->id]['moves'][] = $pushedMove;
            }
        }

        foreach ($rules as $ruleData) {
            $operation = Operation::create([
                'state'                   => OperationState::DRAFT,
                'origin'                  => $ruleData['operation']->name,
                'operation_type_id'       => $ruleData['rule']->operation_type_id,
                'source_location_id'      => $ruleData['rule']->source_location_id,
                'destination_location_id' => $ruleData['rule']->destination_location_id,
                'scheduled_at'            => now()->addDays($ruleData['rule']->delay),
                'company_id'              => $ruleData['rule']->company_id,
            ]);

            foreach ($ruleData['moves'] as $move) {
                $move->update([
                    'operation_id' => $operation->id,
                    'reference'    => $operation->name,
                ]);
            }

            $this->confirmTransfer($operation->fresh());
        }
    }

    /**
     * Run a push rule on a move.
     */
    public function runPushRule(Rule $rule, Move $move)
    {
        $newScheduledAt = $move->scheduled_at->addDays($rule->delay);

        if ($rule->auto == RuleAuto::TRANSPARENT) {
            $move->update([
                'scheduled_at' => $newScheduledAt,
                'destination_location_id' => $rule->destination_location_id,
            ]);

            if ($move->move_line_ids && $move->move_line_ids->isNotEmpty()) {
                $putAwayLocation = $move->destinationLocation->getPutAwayStrategy($move->product);

                foreach ($move->lines as $moveLine) {
                    $moveLine->update([
                        'destination_location_id' => $putAwayLocation?->id ?? $move->destination_location_id,
                    ]);
                }
            }

            //TODO: Check this
            if ($rule->destination_location_id !== $move->destination_location_id) {
                return $this->applyPushRules($move);
            }
        } else {
            $newMoveValues = $this->preparePushMoveCopyValues($rule, $move, $newScheduledAt);

            $newMove = $move->replicate()->fill($newMoveValues);

            $newMove->save();

            if ($newMove->shouldBypassReservation()) {
                $newMove->update([
                    'procure_method' => ProcureMethod::MAKE_TO_STOCK,
                ]);
            }

            if (! $newMove->sourceLocation->shouldBypassReservation()) {
                $move->moveDestinations()->attach($newMove->id);
            }
        }

        return $newMove;
    }

    public function preparePushMoveCopyValues(Rule $rule, Move $moveToCopy, $newScheduledAt)
    {
        $companyId = $rule->company_id;

        $copiedQuantity = $moveToCopy->quantity;

        if (float_compare($moveToCopy->product_uom_qty, 0, precisionRounding: $moveToCopy->uom->rounding) < 0) {
            $copiedQuantity = $moveToCopy->product_uom_qty;
        }

        if (! $companyId) {
            $companyId = $rule->warehouse?->company_id
                ?? $rule->operationType?->warehouse?->company_id;
        }

        return [
            'state'                   => MoveState::DRAFT,
            'reference'               => null,
            'product_uom_qty'         => $copiedQuantity,
            'product_qty'             => $moveToCopy->uom->computeQuantity($copiedQuantity, $moveToCopy->product->uom, true, 'HALF-UP'),
            'origin'                  => $moveToCopy->origin ?? $moveToCopy->operation->name ?? '/',
            'operation_id'            => null,
            'source_location_id'      => $moveToCopy->destination_location_id,
            'destination_location_id' => $rule->destination_location_id,
            'final_location_id'       => $moveToCopy->final_location_id,
            'rule_id'                 => $rule->id,
            'scheduled_at'            => $newScheduledAt,
            'company_id'              => $companyId,
            'operation_type_id'       => $rule->operation_type_id,
            'propagate_cancel'        => $rule->propagate_cancel,
            'warehouse_id'            => $rule->warehouse_id,
            'procure_method'          => ProcureMethod::MAKE_TO_ORDER,
        ];
    }

    /**
     * Traverse up the location tree to find a matching push rule.
     */
    public function getPushRule(Product $product, Location $destinationLocation, array $values = [])
    {
        $foundRule = null;

        $location = $destinationLocation;

        $filters['action'] = [RuleAction::PUSH, RuleAction::PULL_PUSH];

        while (! $foundRule && $location) {
            $filters['source_location_id'] = $location->id;

            $foundRule = $this->searchRule(
                $values['packaging'] ?? null,
                $product,
                $values['warehouse'] ?? null,
                $filters
            );

            $location = $location->parent;
        }

        return $foundRule;
    }

    /**
     * Traverse up the location tree to find a matching pull rule.
     */
    public function getRule(Product $product, Location $location, array $values = [])
    {
        $foundRule = null;

        $filters['action'] = ['!=', RuleAction::PUSH];

        while (! $foundRule && $location) {
            $filters['destination_location_id'] = $location->id;

            $foundRule = $this->searchRule(
                $values['packaging'] ?? null,
                $product,
                $values['warehouse'] ?? null,
                $filters
            );

            $location = $location->parent;
        }

        return $foundRule;
    }

    /**
     * Search for a push rule based on the provided filters.
     */
    public function searchRule($productPackaging, $product, $warehouse, array $filters)
    {
        if ($warehouse) {
            $filters['warehouse_id'] = $warehouse->id;
        }

        $routeSources = [
            [$productPackaging, 'routes'],
            [$product, 'routes'],
            [$product?->category, 'routes'],
            [$warehouse, 'routes'],
        ];

        foreach ($routeSources as [$source, $relationName]) {
            if (! $source || ! $source->{$relationName}) {
                continue;
            }

            $routeIds = $source->{$relationName}->pluck('id');

            if ($routeIds->isEmpty()) {
                continue;
            }

            $foundRule = Rule::whereIn('route_id', $routeIds)
                ->where(function ($query) use ($filters) {
                    foreach ($filters as $column => $value) {
                        if (is_array($value)) {
                            if (count($value) === 2 && $value[0] === '!=') {
                                [, $val] = $value;

                                $query->where($column, '!=', $val);
                            } else {
                                $query->whereIn($column, $value);
                            }
                        } else {
                            $query->where($column, $value);
                        }
                    }
                })
                ->orderBy('route_sort', 'asc')
                ->orderBy('sort', 'asc')
                ->first();

            if ($foundRule) {
                return $foundRule;
            }
        }

        return null;
    }

    public function runProcurements($procurements)
    {
        $actionsToRun = [];

        $procurementErrors = [];
        
        foreach ($procurements as $procurement) {
            $procurement['values']['company'] = $procurement['values']['company'] ?? $procurement['location']->company;
            $procurement['values']['priority'] = $procurement['values']['priority'] ?? '0';
            $procurement['values']['planned'] = $procurement['values']['planned'] ?? now();

            $rule = $this->getRule($procurement['product'], $procurement['location'], $procurement['values']);

            if (! $rule) {
                $error = __('No rule has been found to replenish ":product" in ":location".\nVerify the routes configuration on the product.', [
                    'product' => $procurement['product']->name,
                    'location' => $procurement['location']->full_name,
                ]);

                $procurementErrors[] = ['procurement' => $procurement, 'error' => $error];
            } else {
                $action = $rule->action === RuleAction::PULL_PUSH ? RuleAction::PULL : $rule->action;

                if (! isset($actionsToRun[$action->value])) {
                    $actionsToRun[$action->value] = [];
                }

                $actionsToRun[$action->value][] = [$procurement, $rule];
            }
        }

        foreach ($actionsToRun as $action => $procurements) {
            $method = 'run'.ucfirst($action).'Rule';

            try {
                $this->$method($procurements);
            } catch (\Exception $e) {
                $procurementErrors[] = $e->getMessage();
            }
        }

        dd($actionsToRun, $procurementErrors);
    }

    /**
     * Run a pull rule on a line.
     */
    public function runPullRule($procurements)
    {
        foreach ($procurements as [$procurement, $rule]) {
            if (! $rule->source_location_id) {
                throw new \Exception(__('No source location defined on stock rule: :name!', [
                    'name' => $rule->name
                ]));
            }
        }

        usort($procurements, function($procurement) {
            return float_compare($procurement[0]['product_qty'], 0.0, precisionRounding: $procurement[0]['product_uom']->rounding) > 0 ? 1 : 0;
        });

        $movesValuesByCompany = [];

        foreach ($procurements as [$procurement, $rule]) {
            $procureMethod = $rule->procure_method;

            if ($rule->procure_method === ProcureMethod::MTS_ELSE_MTO) {
                $procureMethod = ProcureMethod::MAKE_TO_STOCK;
            }

            $moveValues = $this->prepareMoveValues($rule, $procurement);

            $moveValues['procure_method'] = $procureMethod;

            $movesValuesByCompany[$procurement['company']->id][] = $moveValues;
        }

        foreach ($movesValuesByCompany as $companyId => $moveValues) {
            $moves = collect();

            foreach ($moveValues as $moveValue) {
                $move = Move::create($moveValue);

                $move->update(['state' => MoveState::CONFIRMED]);

                $moves->push($move);
            }

            $this->assignOperation($moves);

            $this->mergeMoves($moves);
        }
    }

    public function prepareMoveValues($rule, $procurement)
    {
        $procurementGroupId = null;

        if ($rule->group_propagation_option === GroupPropagation::PROPAGATE) {
            $procurementGroupId = $procurement['values']['procurement_group']?->id;
        } elseif ($rule->group_propagation_option === GroupPropagation::FIXED) {
            $procurementGroupId = $rule->procurement_group_id;
        }

        $dateScheduled = $procurement['values']['planned']->copy()->subDays($rule->delay ?? 0);

        $dateDeadline = isset($procurement['values']['deadline'])
            ? $procurement['values']['deadline']->copy()->subDays($rule->delay ?? 0)
            : null;

        $partner = $rule->partnerAddress ?? $procurement['values']['procurement_group']?->partner;

        $pickingDescription = $procurement['product']->getDescription($rule->operationType);

        $qtyLeft = $procurement['product_qty'];

        if (float_compare($procurement['product_qty'], 0.0, precisionRounding: $procurement['product_uom']->rounding) < 0) {
            $isRefund = true;
        }

        $moveValues = [
            'name' => substr($procurement['name'], 0, 2000),
            'company_id' => $rule->company_id ?? $procurement['location']->company_id ?? $rule->destinationLocation?->company_id ?? $procurement['company']?->id,
            'product_id' => $procurement['product']->id,
            'product_uom' => $procurement['product']->uom_id,
            'product_uom_qty' => $qtyLeft,
            'product_qty' => $qtyLeft,
            'partner_id' => $partner?->id,
            'source_location_id' => $rule->source_location_id,
            'final_location_id' => $rule->destination_location_id,
            'rule_id' => $rule->id,
            'procure_method' => $rule->procure_method,
            'origin' => $procurement['origin'] ?? null,
            'operation_type_id' => $rule->operation_type_id,
            'procurement_group_id' => $procurementGroupId,
            'route_ids' => $procurement['values']['route_ids'] ?? [],
            'warehouse_id' => $rule->warehouse_id,
            'scheduled_at' => $dateScheduled,
            'date_deadline' => $rule->group_propagation_option === GroupPropagation::FIXED ? null : $dateDeadline,
            'propagate_cancel' => $rule->propagate_cancel,
            'description_picking' => $pickingDescription,
            'priority' => $procurement['values']['priority'] ?? '0',
            'order_point_id' => $procurement['values']['order_point']?->is ?? null,
            'product_packaging_id' => $procurement['values']['product_packaging']?->id ?? null,
        ];

        if (isset( $procurement['values']['sale_order_line_id'])) {
            $moveValues['sale_order_line_id'] = $procurement['values']['sale_order_line_id'];
        }

        if (isset( $procurement['values']['purchase_order_line_id'])) {
            $moveValues['purchase_order_line_id'] = $procurement['values']['purchase_order_line_id'];
        }

        if (isset( $procurement['values']['work_order_id'])) {
            $moveValues['work_order_id'] = $procurement['values']['work_order_id'];
        }

        if ($rule->location_dest_from_rule) {
            $moveValues['destination_location_id'] = $rule->destination_location_id;
        }

        return $moveValues;
    }

    public function assignOperation($moves, $mergeInto = null)
    {
        $groupedMoves = $moves->groupBy(fn($move) => implode('_', $this->keyAssignPicking($move)));

        foreach ($groupedMoves as $moves) {
            $operation = $this->searchOperationForAssignation($moves[0]);

            if ($operation) {
                $vals = [];

                if ($moves->some(fn($move) => $operation->partner_id !== $move->partner_id)) {
                    $vals['partner_id'] = null;
                }

                if ($moves->some(fn($move) => $operation->origin !== $move->origin)) {
                    $vals['origin'] = null;
                }

                if (! empty($vals)) {
                    $operation->update($vals);
                }
            } else {
                $moves->each(fn($move) => $move->load('uom'));

                $moves = $moves->filter(fn($move) => float_compare($move->product_uom_qty, 0.0, precisionRounding: $move->uom->rounding) >= 0);

                if ($moves->isEmpty()) {
                    continue;
                }
                
                $operation = Operation::create($this->getNewOperationValues($moves));
            }

            foreach ($moves as $move) {
                $move->update([
                    'operation_id' => $operation->id,
                ]);
            }

            $operation->refresh();

            $operation->computeState();

            $operation->save();
        }
    }

    public function getNewOperationValues($moves): array
    {
        $origins = $moves->filter(fn($move) => $move->origin)
            ->pluck('origin')
            ->unique()
            ->values();

        if ($origins->isEmpty()) {
            $origin = null;
        } else {
            $origin = $origins->take(5)->implode(',');

            if ($origins->count() > 5) {
                $origin .= '...';
            }
        }

        $partners = $moves->pluck('partner_id')->unique();

        $partner = $partners->count() === 1 ? $partners->first() : null;

        $vals = [
            'origin'               => $origin,
            'company_id'           => $moves->pluck('company_id')->first(),
            'user_id'              => null,
            'procurement_group_id' => $moves->pluck('procurement_group_id')->first(),
            'partner_id'           => $partner,
            'operation_type_id'    => $moves->pluck('operation_type_id')->first(),
            'source_location_id'   => $moves->pluck('source_location_id')->first(),
        ];

        $destinationLocationIds = $moves->pluck('destination_location_id')->filter()->unique();

        if ($destinationLocationIds->isNotEmpty()) {
            $vals['destination_location_id'] = $destinationLocationIds->first();
        }

        return $vals;
    }

    public function keyAssignPicking(Move $move): array
    {
        $keys = [
            $move->procurement_group_id,
            $move->source_location_id,
            $move->destination_location_id,
            $move->operation_type_id,
        ];

        if ($move->partner_id && ! $move->procurement_group_id) {
            $keys[] = $move->partner_id;
        }

        return $keys;
    }

    public function searchOperationForAssignation(Move $move)
    {
        $query = Operation::where('procurement_group_id', $move->procurement_group_id)
            ->where('source_location_id', $move->source_location_id)
            ->where('destination_location_id', $move->destination_location_id ?? $move->operationType->destination_location_id)
            ->where('operation_type_id', $move->operation_type_id)
            // ->where('printed', false)
            ->whereIn('state', [OperationState::DRAFT, OperationState::CONFIRMED, OperationState::ASSIGNED]);

        if ($move->partner_id && ! $move->procurement_group_id) {
            $query->where('partner_id', $move->partner_id);
        }

        return $query->first();
    }

    public function mergeMoves($moves, $mergeInto = null)
    {
        $candidateMovesSet = [];

        $moves->each(fn ($move) => $move->load('operation'));

        if (! $mergeInto) {
            $operations = $moves
                ->map(fn($move) => $move->operation)
                ->filter()
                ->unique('id');

            foreach ($operations as $operation) {
                $candidateMovesSet[$operation->id] = $operation->moves;
            }
        } else {
            $candidateMovesSet = array_merge($mergeInto, $moves->toArray());
        }

        $distinctFields = [
            'product_id',
            // 'price_unit',
            'procure_method',
            'source_location_id',
            'destination_location_id',
            'final_location_id',
            'uom_id',
            'restrict_partner_id',
            'origin_returned_move_id',
            'package_level_id',
            'description_picking',
            'product_packaging_id',
        ];

        $movesToDelete = collect();

        $mergedMoves = collect();

        $movesToCancel = collect();

        $movesByNegKey = collect();

        $negQtyMoves = $moves->filter(fn($move) => float_compare($move->product_qty, 0.0, precisionRounding: $move->uom->rounding) < 0)
            ->each(function($move) {
                $move->operation_id = null;
            });

        $negKeyFields = array_values(array_diff($distinctFields, ['description_picking', 'price_unit']));

        $negKey = fn($move) => collect($negKeyFields)
            ->map(fn($field) => $move->$field instanceof \BackedEnum ? $move->$field->value : (string) $move->$field)
            ->implode('_');

        $priceUnitPrecision =  2;

        foreach ($candidateMovesSet as $candidateMoves) {
            $candidateMoves = $candidateMoves->filter(fn ($move) => ! in_array($move->state, [
                    MoveState::DRAFT,
                    MoveState::DONE,
                    MoveState::CANCELED,
                ]))
                ->diff($negQtyMoves);

            $distinctKey = fn($move) => collect($distinctFields)
                ->map(fn($field) => $move->$field instanceof \BackedEnum ? $move->$field->value : (string) $move->$field)
                ->implode('_');

            foreach ($candidateMoves->groupBy($distinctKey) as $group) {
                if ($group->count() > 1) {
                    $group->flatMap->lines->each->update(['move_id' => $group->first()->id]);

                    $mergeExtra = (bool) $mergeInto;

                    ['move_destination_ids' => $destinationIds, 'move_origin_ids' => $originIds] = $fields = $this->mergeMoveValues($group, $mergeExtra);

                    $values = collect($fields)->except(['move_destination_ids', 'move_origin_ids'])->all();

                    $group->first()->update($values);

                    $group->first()->moveDestinations()->sync($destinationIds);

                    $group->first()->moveOrigins()->sync($originIds);

                    $movesToDelete = $movesToDelete->merge($group->skip(1));

                    $mergedMoves = $mergedMoves->merge([$group->first()]);
                }

                $negKeyValue = $negKey($group->first());

                $movesByNegKey->put(
                    $negKeyValue,
                    $movesByNegKey->has($negKeyValue)
                        ? $movesByNegKey->get($negKeyValue)->push($group->first())
                        : collect([$group->first()])
                );
            }
        }

        foreach ($negQtyMoves as $negMove) {
            foreach ($movesByNegKey->get($negKey($negMove), collect()) as $posMove) {
                if (float_compare($posMove->price_unit, $negMove->price_unit, precisionDigits: 2) == 0) {
                    $newTotalValue = $posMove->product_qty * $posMove->price_unit + $negMove->product_qty * $negMove->price_unit;

                    if (float_compare($posMove->product_uom_qty, abs($negMove->product_uom_qty), precisionRounding: $posMove->uom->rounding) >= 0) {
                        $posMove->product_uom_qty += $negMove->product_uom_qty;

                        $moveDestinationIds = $negMove->moveDestinations
                            ->filter(fn($move) => $move->source_location_id === $posMove->destination_location_id)
                            ->pluck('id')
                            ->all();

                        $moveOriginIds = $negMove->moveOrigins
                            ->filter(fn($move) => $move->destination_location_id === $posMove->source_location_id)
                            ->pluck('id')
                            ->all();

                        $posMove->update([
                            'price_unit' => $posMove->product_qty
                                ? round($newTotalValue / $posMove->product_qty, $priceUnitPrecision)
                                : 0,
                        ]);

                        $posMove->moveDestinations()->syncWithoutDetaching($moveDestinationIds);
                        $posMove->moveOrigins()->syncWithoutDetaching($moveOriginIds);

                        $mergedMoves->push($posMove);

                        $movesToDelete->push($negMove);

                        if (float_is_zero($posMove->product_uom_qty, precisionRounding: $posMove->uom->rounding)) {
                            $movesToCancel->push($posMove);
                        }

                        break;
                    }

                    $negMove->product_qty += $posMove->product_qty;

                    $negMove->product_uom_qty += $posMove->product_uom_qty;

                    $negMove->price_unit = round($newTotalValue / $negMove->product_qty, $priceUnitPrecision);

                    $posMove->product_uom_qty = 0;

                    $movesToCancel->push($posMove);
                }
            }
        }

        if ($movesToDelete->isNotEmpty()) {
            $this->cancelMoves($movesToDelete);

            foreach ($movesToDelete as $move) {
                foreach ($move->lines()->get() as $line) {
                    $line->delete();
                }

                $move->delete();
            }

            $movesToDelete->each->delete();
        }

        if ($movesToCancel->isNotEmpty()) {
            $this->cancelMoves($movesToCancel->filter(fn ($move) => ! $move->picked));
        }

        return $moves->merge($mergedMoves)->diff($movesToDelete);
    }

    public function mergeMoveValues($moves, $mergeExtra = false)
    {
        $state = $this->getRelevantStateAmongMoves($moves);

        $origin = $moves->filter(fn($move) => $move->origin)
            ->pluck('origin')
            ->unique()
            ->implode('/');

        $date = $moves->pluck('operation')->every(fn($operation) => $operation->move_type === MoveType::DIRECT)
            ? $moves->min('date')
            : $moves->max('date');

        return [
            'product_uom_qty'      => ! $mergeExtra
                ? $moves->sum('product_uom_qty')
                : $moves->first()->product_uom_qty,
            'product_qty'          => ! $mergeExtra
                ? $moves->sum('product_qty')
                : $moves->first()->product_qty,
            'date'                 => $date,
            'state'                => $state,
            'origin'               => $origin,
            'move_destination_ids' => $moves->flatMap->moveDestinations->pluck('id')->all(),
            'move_origin_ids'      => $moves->flatMap->moveOrigins->pluck('id')->all(),
        ];
    }

    public function cancelMoves($moves)
    {
        if ($moves->some(fn($move) => $move->state === MoveState::DONE && ! $move->is_scraped)) {
            throw new \Exception(__('You cannot cancel a stock move that has been set to \'Done\'. Create a return in order to reverse the moves which took place.'));
        }

        $movesToCancel = $moves->filter(
            fn($move) => $move->state !== MoveState::CANCELED &&
                ! ($move->state === MoveState::DONE && $move->is_scraped)
        );

        $movesToCancel->each->update(['picked' => false]);

        // $this->doUnreserve($movesToCancel);

        $cancelMovesOrigin = false;

        $movesToCancel->each->update(['state' => MoveState::CANCELED]);

        foreach ($movesToCancel as $move) {
            $siblingsStates = $move->moveDestinations
                ->flatMap->moveOrigins
                ->diff(collect([$move]))
                ->pluck('state');

            if ($move->propagate_cancel) {
                if ($siblingsStates->every(fn($state) => $state === MoveState::CANCELED)) {
                    $this->cancelMoves(
                        $move->moveDestinations->filter(
                            fn($move) => $move->state !== MoveState::DONE &&
                                $move->location_dest_id === $move->moveDestinations->first()?->location_id
                        )
                    );

                    if ($cancelMovesOrigin) {
                        $this->cancelMoves($move->moveOrigins->filter(fn($move) => $move->state !== MoveState::DONE));
                    }
                }
            } else {
                if ($siblingsStates->every(fn ($state) => in_array($state, [MoveState::DONE, MoveState::CANCELED]))) {
                    $move->moveDestinations->each(function ($destMove) use ($move) {
                        $destMove->update(['procure_method' => ProcureMethod::MAKE_TO_STOCK]);
                        
                        $destMove->moveOrigins()->detach($move->id);
                    });
                }
            }
        }

        $movesToCancel->each(function ($move) {
            $move->update(['procure_method' => ProcureMethod::MAKE_TO_STOCK]);

            $move->moveOrigins()->detach();
        });

        return true;
    }

    public function getRelevantStateAmongMoves($moves): \BackedEnum
    {
        $sortMap = [
            MoveState::ASSIGNED->value           => 4,
            MoveState::WAITING->value            => 3,
            MoveState::PARTIALLY_ASSIGNED->value => 2,
            MoveState::CONFIRMED->value          => 1,
        ];

        $movesTodo = $moves->filter(fn($move) => 
                ! in_array($move->state, [MoveState::CANCELED, MoveState::DONE]) &&
                ! ($move->state === MoveState::ASSIGNED && ! $move->product_uom_qty)
            )
            ->sortBy([
                fn($a, $b) => ($sortMap[$a->state->value] ?? 0) <=> ($sortMap[$b->state->value] ?? 0),
                fn($a, $b) => $a->product_uom_qty <=> $b->product_uom_qty,
            ])
            ->values();

        if ($movesTodo->isEmpty()) {
            return MoveState::ASSIGNED;
        }

        $firstMove = $movesTodo->first();

        if ($firstMove->picking && $firstMove->picking->move_type === MoveType::ONE) {
            if ($movesTodo->every(fn($move) => ! $move->product_uom_qty)) {
                return MoveState::ASSIGNED;
            }

            $mostImportantMove = $movesTodo->first();

            if ($mostImportantMove->state === MoveState::CONFIRMED) {
                return MoveState::CONFIRMED;
            } elseif ($mostImportantMove->state === MoveState::PARTIALLY_ASSIGNED) {
                return MoveState::CONFIRMED;
            } else {
                return $mostImportantMove->state ?? MoveState::DRAFT;
            }
        } elseif (
            $firstMove->state !== MoveState::ASSIGNED &&
            $movesTodo->some(fn ($move) => in_array($move->state, [MoveState::ASSIGNED, MoveState::PARTIALLY_ASSIGNED]))
        ) {
            return MoveState::PARTIALLY_ASSIGNED;
        } else {
            $leastImportantMove = $movesTodo->last();

            if ($leastImportantMove->state === MoveState::CONFIRMED && $leastImportantMove->product_uom_qty == 0) {
                return MoveState::ASSIGNED;
            }

            return $leastImportantMove->state ?? MoveState::DRAFT;
        }
    }

    /**
     * Run a buy rule on a line.
     */
    public function runBuyRule($procurements)
    {
        if (! Package::isPluginInstalled('purchases')) {
            return;
        }

        $procurementsByPoFilters = [];
        
        $errors = [];

        foreach ($procurements as [$procurement, $rule]) {
            $procurementDatePlanned = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $procurement['values']['planned']
            );

            $supplier = false;

            $company = $rule->company ?: $procurement['company'];

            if (! empty($procurement['values']['supplierinfo'])) {
                $supplier = $procurement['values']['supplierinfo'];
            } elseif (
                ! empty($procurement['values']['order_point']) &&
                $procurement['values']['order_point']->supplier
            ) {
                $supplier = $procurement['values']['order_point']->supplier;
            } else {
                $supplier = $procurement['product']
                    ->getSeller([
                        'partner'    => $procurement['values']['supplier'] ?? null,
                        'quantity'   => $procurement['product_qty'],
                        'date'       => max(
                            $procurementDatePlanned->format('Y-m-d'),
                            now()->format('Y-m-d')
                        ),
                        'uom'        => $procurement['product_uom'],
                        'company'    => $company,
                        'params'     => ['force_uom' => $procurement['values']['force_uom'] ?? null],
                    ]);
            }

            $supplier = $supplier ?: $procurement['product']
                ->prepareSellers(false)
                ->filter(fn($seller) => ! $seller->company_id || $seller->company_id === $company->id)
                ->first();

            if (! $supplier && $procurement['values']['from_order_point'] ?? null) {
                $msg = __(
                    'There is no matching vendor price to generate the purchase order for product %s ' .
                    '(no vendor defined, minimum quantity not reached, dates not valid, ...). ' .
                    'Go on the product form and complete the list of vendors.',
                    $procurement['product']->name
                );

                $errors[] = [$procurement, $msg];
            } elseif (! $supplier) {
                $moves = $procurement['values']['move_destination_ids'] ?? collect();

                foreach ($moves as $move) {
                    if ($move->propagate_cancel) {
                        $this->cancelMoves(collect([$move]));
                    }

                    $move->procure_method = 'make_to_stock';
                }

                continue;
            }

            $partner = $supplier->partner;

            $procurement['values']['supplier'] = $supplier;

            $procurement['values']['propagate_cancel'] = $rule->propagate_cancel;

            $filters = $this->getPurchaseOrderFilters($rule, $company, $procurement['values'], $partner);

            $filtersKey = serialize($filters);

            $procurementsByPoFilters[$filtersKey][] = [$procurement, $rule];
        }

        if (! empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }

        foreach ($procurementsByPoFilters as $filtersKey => $procurementsRules) {
            $procurements = collect($procurementsRules)->pluck(0);

            $rules = collect($procurementsRules)->pluck(1);

            $filters = unserialize($filtersKey);

            $origins = $procurements->pluck('origin')->unique()->filter()->all();

            $purchaseOrder = PurchaseOrder::where($filters)->first();

            $company = $rules->first()->company ?: $procurements->first()['company'];

            if (! $purchaseOrder) {
                $positiveValues = $procurements
                    ->filter(fn($procurement) => bccomp(round($procurement['product_qty'], $procurement['product_uom']->rounding), 0.0) >= 0)
                    ->pluck('values')
                    ->all();

                if (! empty($positiveValues)) {
                    $values = $this->preparePurchaseOrderValues($rules->first(), $company, $origins, $positiveValues);

                    $purchaseOrder = PurchaseOrder::create($values);
                }
            } else {
                if ($purchaseOrder->origin) {
                    $missingOrigins = array_diff($origins, explode(', ', $purchaseOrder->origin));

                    if (! empty($missingOrigins)) {
                        $purchaseOrder->update(['origin' => $purchaseOrder->origin . ', ' . implode(', ', $missingOrigins)]);
                    }
                } else {
                    $purchaseOrder->update(['origin' => implode(', ', $origins)]);
                }
            }

            $procurementsToMerge = $this->getProcurementsToMerge($procurements->all());

            $procurements = $this->mergeProcurements($procurementsToMerge);

            $purchaseOrderLinesByProduct = $purchaseOrder->orderLines
                ->filter(fn($line) => ! $line->display_type && $line->uom_id === $line->product->uom_po_id)
                ->groupBy('product_id');

            $purchaseOrderLineValues = [];

            foreach ($procurements as $procurement) {
                $purchaseOrderLines = $purchaseOrderLinesByProduct->get($procurement['product_id'], collect());

                $purchaseOrderLine  = $purchaseOrderLines->findCandidate($procurement);

                if ($purchaseOrderLine) {
                    $values = $this->updatePurchaseOrderLine(
                        $procurement['product'],
                        $procurement['product_qty'],
                        $procurement['product_uom'],
                        $company,
                        $procurement['values'],
                        $purchaseOrderLine,
                    );

                    $purchaseOrderLine->update($values);
                } else {
                    if (bccomp(round($procurement['product_qty'], $procurement['product_uom']->rounding), 0) <= 0) {
                        continue;
                    }

                    $purchaseOrderLineValues[] = PurchaseOrderLine::preparePurchaseOrderLineFromProcurement($procurement, $purchaseOrder);

                    $orderDatePlanned = Carbon::parse($procurement['values']['planned'])
                        ->subDays($procurement['values']['supplier']?->delay ?? 0);

                    if ($orderDatePlanned->toDateString() < Carbon::parse($purchaseOrder->ordered_at)->toDateString()) {
                        $purchaseOrder->update(['ordered_at' => $orderDatePlanned]);
                    }
                }
            }
        }

        if (! empty($purchaseOrderLineValues)) {
            PurchaseOrderLine::insert($purchaseOrderLineValues);
        }
    }

    public function preparePurchaseOrderValues($rule, $company, $origins, $values)
    {
        $purchaseDate = collect($values)
            ->map(fn($value) => ! empty($value['scheduled_at'])
                ? Carbon::parse($value['scheduled_at'])
                : Carbon::parse($value['planned'])->subDays((int) $value['supplier']?->delay ?? 0)
            )
            ->min();

        $value = $values[0];

        $partner = $value['supplier']->partner;

        // $fiscalPosition = FiscalPosition::getFiscalPosition($partner);

        $gpo = $rule->group_propagation_option;

        $procurementGroupId = match(true) {
            $gpo === GroupPropagation::FIXED     => $rule->procurement_group_id,
            $gpo === GroupPropagation::PROPAGATE => $values['procurement_group']?->id ?? false,
            default                              => false,
        };

        return [
            'partner_id'             => $partner->id,
            'user_id'                => $partner->user_id,
            'operation_type_id'      => $rule->operation_type_id,
            'company_id'             => $company->id,
            'currency_id'            => $partner->purchase_currency_id ?? $company->currency_id,
            'destination_address_id' => $value['partner_id'] ?? null,
            'origin'                 => implode(', ', $origins),
            'payment_term_id'        => $partner->property_supplier_payment_term_id,
            'ordered_at'             => $purchaseDate,
            // 'fiscal_position_id'     => $fiscalPosition?->id,
            'procurement_group_id'   => $procurementGroupId,
        ];
    }

    public function getPurchaseOrderFilters($rule, $company, $values, $partner)
    {
        $gpo = $rule->group_propagation_option;

        $procurementGroupId = match(true) {
            $gpo === GroupPropagation::FIXED     => $rule->procurement_group_id,
            $gpo === GroupPropagation::PROPAGATE => $values['procurement_group']?->id ?? false,
            default                              => false,
        };

        $filters = [
            ['partner_id', '=', $partner->id],
            ['state', '=', PurchaseOrderEnums\OrderState::DRAFT],
            ['operation_type_id', '=', $rule->operation_type_id],
            ['company_id', '=', $company->id],
            ['user_id', '=', $partner->user_id],
        ];

        if (! empty($values['order_point'])) {
            $procurementDate = Carbon::parse($values['planned'])
                ->subDays($values['supplier']->delay ?? 0)
                ->toDateString();

            $filters[] = ['ordered_at', '<=', Carbon::parse($procurementDate)->endOfDay()];
            $filters[] = ['ordered_at', '>=', Carbon::parse($procurementDate)->startOfDay()];
        }

        if ($procurementGroupId) {
            $filters[] = ['procurement_group_id', '=', $procurementGroupId];
        }

        return $filters;
    }

    public function getProcurementsToMerge($procurements)
    {
        return collect($procurements)
            ->groupBy(function ($procurement) {
                $orderPointKey = (! empty($procurement['values']['order_point']) && empty($procurement['values']['move_destination_ids']))
                    ? $procurement['values']['order_point']->id
                    : null;

                return implode('_', [
                    $procurement['product']->id,
                    $procurement['product_uom']->id,
                    (int) $procurement['values']['propagate_cancel'],
                    $orderPointKey ?? '',
                ]);
            })
            ->values()
            ->all();
    }

    public function mergeProcurements($procurements)
    {
        $mergedProcurements = [];

        foreach ($procurements as $procurements) {
            $quantity = 0;

            $moveDestinationIds = collect();

            $orderPoint = null;

            foreach ($procurements as $procurement) {
                if (! empty($procurement['values']['move_destination_ids'])) {
                    $moveDestinationIds = $moveDestinationIds->merge($procurement['values']['move_destination_ids']);
                }

                if (! $orderPoint && ! empty($procurement['values']['order_point'])) {
                    $orderPoint = $procurement['values']['order_point'];
                }

                $quantity += $procurement['product_qty'];
            }

            $values = array_merge($procurement['values'], [
                'move_destination_ids' => $moveDestinationIds,
                'order_point' => $orderPoint,
            ]);

            $mergedProcurements[] = [
                'product'     => $procurement['product'],
                'product_qty' => $quantity,
                'product_uom' => $procurement['product_uom'],
                'location'    => $procurement['location'],
                'name'        => $procurement['name'],
                'origin'      => $procurement['origin'],
                'company'     => $procurement['company'],
                'values'      => $values,
            ];
        }

        return $mergedProcurements;
    }

    public function updatePurchaseOrderLine($product, $quantity, $uom, $company, $values, $line)
    {
        $partner = $values['supplier']->partner;

        $procurementUOMPoQty = $uom->computeQuantity($quantity, $product->uomPO, roundingMethod: 'HALF-UP');

        $seller = $product
            ->getSeller([
                'partner'  => $partner,
                'quantity' => $line->product_qty + $procurementUOMPoQty,
                'date'     => $line->order->ordered_at?->toDateString(),
                'uom'      => $product->uomPO,
                'company'  => $company,
            ]);

        $priceUnit = $seller
            ? TaxFacade::fixTaxIncludedPriceCompany($seller->price, $line->product->supplierTaxes, $line->taxes, $company)
            : 0.0;

        if ($priceUnit && $seller && $line->order->currency && $seller->currency_id !== $line->order->currency_id) {
            $priceUnit = $seller->currency->convert(
                $priceUnit,
                $line->order->currency,
                $line->order->company,
                now()->toDateString(),
            );
        }

        $result = [
            'product_qty'          => $line->product_qty + $procurementUOMPoQty,
            'price_unit'           => $priceUnit,
            'move_destination_ids' => collect($values['move_destination_ids'] ?? [])->pluck('id')->all(),
        ];

        if (! empty($values['order_point'])) {
            $result['order_point_id'] = $values['order_point']->id;
        }

        return $result;
    }

    /**
     * Run a manufacture rule on a line.
     */
    public function runManufactureRule($procurements)
    {
    }
}
