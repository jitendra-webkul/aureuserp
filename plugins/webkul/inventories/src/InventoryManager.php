<?php

namespace Webkul\Inventory;

use Illuminate\Support\Facades\Auth;
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
use Webkul\Inventory\Models\Product;
use Webkul\Inventory\Models\Rule;
use Webkul\PluginManager\Package;
use Webkul\Purchase\Facades\PurchaseOrder as PurchaseOrderFacade;
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

    public function todoTransfer(Operation $record): Operation
    {
        if ($record->state !== OperationState::DRAFT) {
            return $record;
        }

        $record->moves->each(fn (Move $move) => $move->update(['state' => MoveState::CONFIRMED]));

        $record->computeState();

        $record->save();

        return $record;
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

        $newOperation = $this->todoTransfer($newOperation);

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

        $newOperation = $this->todoTransfer($newOperation);

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

            $this->todoTransfer($operation->fresh());
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

        if (float_compare($moveToCopy->product_uom_qty, 0, precisionRounding: $moveToCopy->product_uom->rounding) < 0) {
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
            return float_compare($procurement[0]->product_qty, 0.0, precisionRounding: $procurement[0]->uom->rounding) > 0 ? 1 : 0;
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

        foreach ($movesValuesByCompany as $companyId => $movesValues) {
            $move = Move::create($moveValues);

            $move->update(['state' => MoveState::CONFIRMED]);
        }

        dd($movesValuesByCompany);
    }

    /**
     * Run a buy rule on a line.
     */
    public function runBuyRule($procurements)
    {
    }

    /**
     * Run a manufacture rule on a line.
     */
    public function runManufactureRule($procurements)
    {
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
            'orderpoint_id' => $procurement['values']['orderpoint_id'] ?? null,
            'product_packaging_id' => $procurement['values']['product_packaging_id'] ?? null,
        ];

        if ($rule->location_dest_from_rule) {
            $moveValues['destination_location_id'] = $rule->destination_location_id;
        }

        return $moveValues;
    }
}
