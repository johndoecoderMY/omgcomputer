<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_part_supplier', function (Blueprint $table): void {
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_part_supplier', function (Blueprint $table): void {
            $table->dropColumn(['cost_price', 'unit_price']);
        });
    }
};
