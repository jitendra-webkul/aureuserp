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
        Schema::table('pos_bills', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();

            $table->boolean('is_for_all_configs')->default(1)->change();
        });

        Schema::table('pos_notes', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_bills', function (Blueprint $table) {
            $table->boolean('is_for_all_configs')->default(0)->change();
        });
    }
};
