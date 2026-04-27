<?php

namespace Webkul\Manufacturing;

use Webkul\Inventory\Enums\MoveState;
use Webkul\Inventory\Enums\ProductTracking;
use Webkul\Inventory\Facades\Inventory as InventoryFacade;
use Webkul\Manufacturing\Enums\ManufacturingOrderState;
use Webkul\Manufacturing\Models\BillOfMaterial;
use Webkul\Manufacturing\Models\Move;
use Webkul\Product\Enums\ProductType;

class ManufacturingManager
{
    public function confirmManufacturingOrder($order)
    {
        $orderVals = [];

        if ($order->bom_id) {
            $orderVals['consumption'] = $order->bom->consumption;
        }

        if (
            $order->product_tracking === ProductTracking::SERIAL
            && $order->product_uom_id !== $order->product->uom_id
        ) {
            $orderVals['quantity'] = $order->uom->computeQuantity($order->quantity, $order->product->uom);

            $orderVals['uom_id'] = $order->product->uom_id;

            $order->finishedMoves
                ->filter(fn($move) => $move->product_id === $order->product_id)
                ->each(function ($moveFinish) {
                    $moveFinish->update([
                        'product_uom_qty' => $moveFinish->uom->computeQuantity($moveFinish->product_uom_qty, $moveFinish->product->uom),
                        'uom_id' => $moveFinish->product->uom_id,
                    ]);
                });
        }

        if (! empty($orderVals)) {
            $order->update($orderVals);
        }

        $order->rawMaterialMoves->sortBy('id')->each->adjustProcureMethod();

        $order->rawMaterialMoves->sortBy('id')->each(function ($move) {
            $move->adjustProcureMethod();

            $move->save();
        });

        $movesToConfirm = $order->rawMaterialMoves->merge($order->finishedMoves)->sortBy('id')->unique('id');

        $this->confirmMoves($movesToConfirm, merge: false);
        
        $this->confirmWorkOrders($order, $order->workOrders->sortBy('id'));

        $operationsToConfirm = $order->inventory_operations
            ->filter(fn($operation) => ! in_array($operation->state, [MoveState::CANCELED, MoveState::DONE]))
            ->each(fn($operation) => InventoryFacade::confirmTransfer($operation, merge: false));

        foreach ($operationsToConfirm as $operation) {
            InventoryFacade::confirmTransfer($operation, merge: false);
        }

        if ($order->state === ManufacturingOrderState::DRAFT) {
            $order->update(['state' => ManufacturingOrderState::CONFIRMED]);
        }

        return true;
    }

    public function confirmWorkOrders($order, $workOrders)
    {
        $order->linkWorkOrdersAndMoves($workOrders);
    }

    public function confirmMoves($moves, $merge = false, $mergeInto = false)
    {
        $moves = $this->explodeMoves($moves);

        $mergeInto = $mergeInto ? $this->explodeMoves($mergeInto) : false;

        InventoryFacade::confirmMoves($moves, merge: $merge, mergeInto: $mergeInto);
    }

    public function explodeMoves($moves)
    {
        $movesToReturn = collect();

        $movesToUnlink = collect();

        $phantomMovesValsList = [];

        foreach ($moves as $move) {
            if (
                ! $move->operation_type_id
                || (
                    $move->order_id
                    && $move->order->product_id === $move->product_id
                )
            ) {
                $movesToReturn->push($move);
                
                continue;
            }

            $bom = BillOfMaterial::bomFind(collect([$move->product]), companyId: $move->company_id, bomType: 'phantom')[$move->product_id] ?? null;

            if (! $bom) {
                $movesToReturn->push($move);

                continue;
            }

            if (float_is_zero($move->product_uom_qty, precisionRounding: $move->uom->rounding)) {
                $factor = $move->uom->computeQuantity($move->quantity, $bom->uom) / $bom->quantity;
            } else {
                $factor = $move->uom->computeQuantity($move->product_uom_qty, $bom->uom) / $bom->quantity;
            }

            [, $lines] = $bom->explode(
                $move->product,
                $factor,
                operationType: $bom->operationType
            );

            foreach ($lines as [$bomLine, $lineData]) {
                if (float_is_zero($move->product_uom_qty, precisionRounding: $move->uom->rounding)) {
                    $phantomMovesValsList = array_merge($phantomMovesValsList, $this->generatePhantomMove($move, $bomLine, 0, $lineData['qty']));
                } else {
                    $phantomMovesValsList = array_merge($phantomMovesValsList, $this->generatePhantomMove($move, $bomLine, $lineData['qty'], 0));
                }
            }

            $movesToUnlink->push($move);
        }

        if (! empty($phantomMovesValsList)) {
            $phantomMoves = collect(array_map(fn($vals) => Move::create($vals), $phantomMovesValsList));

            $phantomMoves->each->adjustProcureMethod();

            $movesToReturn = $movesToReturn->merge($this->explodeMoves($phantomMoves));
        }

        $movesToUnlink->each(function ($move) {
            $move->update(['quantity' => 0]);

            InventoryFacade::cancelMoves(collect([$move]));

            $move->delete();
        });

        return $movesToReturn;
    }

    public function preparePhantomMoveValues($move, $bomLine, $productQty, $quantityDone): array
    {
        return [
            'operation_id'    => $move->operation?->id ?? null,
            'product_id'      => $bomLine->product->id,
            'product_uom'     => $bomLine->uom->id,
            'product_uom_qty' => $productQty,
            'quantity'        => $quantityDone,
            'name'            => $move->name,
            'is_picked'       => $move->is_picked,
            'bom_line_id'     => $bomLine->id,
        ];
    }

    public function generatePhantomMove($move, $bomLine, $productQty, $quantityDone): array
    {
        $values = [];

        if ($bomLine->product->type === ProductType::GOODS) {
            $values = [$move->replicate()->fill(
                $this->preparePhantomMoveValues($move, $bomLine, $productQty, $quantityDone)
            )->toArray()];

            if ($move->state === MoveState::ASSIGNED) {
                foreach ($values as &$value) {
                    $value['state'] = MoveState::ASSIGNED;
                }
            }
        }

        return $values;
    }

    public function cancelManufacturingOrder($record): void {}
}
