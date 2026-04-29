<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventories_moves', function (Blueprint $table) {
            $table->foreignId('created_order_id')
                ->nullable()
                ->constrained('manufacturing_orders')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('order_id')
                ->nullable()
                ->constrained('manufacturing_orders')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('raw_material_order_id')
                ->nullable()
                ->constrained('manufacturing_orders')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('unbuild_order_id')
                ->nullable()
                ->constrained('manufacturing_unbuild_orders')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('consume_unbuild_order_id')
                ->nullable()
                ->constrained('manufacturing_unbuild_orders')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('mo_operation_id')
                ->nullable()
                ->constrained('manufacturing_operations')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('work_order_id')
                ->nullable()
                ->constrained('manufacturing_work_orders')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('bom_line_id')
                ->nullable()
                ->constrained('manufacturing_bill_of_material_lines')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('byproduct_id')
                ->nullable()
                ->constrained('manufacturing_bill_of_material_byproducts')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->foreignId('order_finished_lot_id')
                ->nullable()
                ->constrained('inventories_lots')
                ->nullOnDelete()
                ->noActionOnUpdate();

            $table->decimal('cost_share', 15, 4)
                ->nullable();

            $table->boolean('manual_consumption')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories_moves', function (Blueprint $table) {
            $table->dropForeign(['created_order_id']);
            $table->dropColumn('created_order_id');

            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');

            $table->dropForeign(['raw_material_order_id']);
            $table->dropColumn('raw_material_order_id');

            $table->dropForeign(['unbuild_order_id']);
            $table->dropColumn('unbuild_order_id');

            $table->dropForeign(['consume_unbuild_order_id']);
            $table->dropColumn('consume_unbuild_order_id');

            $table->dropForeign(['work_order_id']);
            $table->dropColumn('work_order_id');

            $table->dropForeign(['bom_line_id']);
            $table->dropColumn('bom_line_id');

            $table->dropForeign(['byproduct_id']);
            $table->dropColumn('byproduct_id');

            $table->dropForeign(['order_finished_lot_id']);
            $table->dropColumn('order_finished_lot_id');

            $table->dropColumn('cost_share');
            $table->dropColumn('manual_consumption');
        });
    }
};
