<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->text('shopee_shop_url')->nullable();
            $table->char('shopee_shop_url_hash', 64)->nullable()->unique();
        });

        Schema::create('inventory_part_supplier', function (Blueprint $table): void {
            $table->foreignId('inventory_part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->text('shopee_product_url')->nullable();
            $table->timestamps();
            $table->primary(['inventory_part_id', 'supplier_id']);
        });

        DB::table('inventory_part_supplier')->insertUsing(
            ['inventory_part_id', 'supplier_id', 'created_at', 'updated_at'],
            DB::table('inventory_parts')
                ->whereNotNull('supplier_id')
                ->selectRaw('id, supplier_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP'),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_part_supplier');

        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropUnique(['shopee_shop_url_hash']);
            $table->dropColumn(['shopee_shop_url', 'shopee_shop_url_hash']);
        });
    }
};
