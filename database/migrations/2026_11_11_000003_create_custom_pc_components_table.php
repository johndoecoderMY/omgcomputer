<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_pc_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_inventory_part_id')->nullable()->unique()->constrained('inventory_parts')->nullOnDelete();
            $table->timestamp('source_inventory_archived_at')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->string('name');
            $table->string('type', 40);
            $table->text('description')->nullable();
            $table->decimal('catalog_price', 12, 2)->nullable();
            $table->string('catalog_currency', 3)->nullable();
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->timestamps();
            $table->index(['type', 'name']);
        });

        Schema::create('custom_pc_component_supplier', function (Blueprint $table): void {
            $table->foreignId('custom_pc_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->text('shopee_product_url')->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->timestamps();
            $table->primary(['custom_pc_component_id', 'supplier_id']);
        });

        $componentTypes = [
            'cpu', 'gpu', 'ram', 'motherboard', 'storage', 'power_supply',
            'case', 'cooling', 'operating_system', 'software', 'peripheral', 'other',
        ];

        DB::table('custom_pc_components')->insertUsing(
            [
                'source_inventory_part_id',
                'sku',
                'name',
                'type',
                'description',
                'catalog_price',
                'catalog_currency',
                'cost_price',
                'selling_price',
                'created_at',
                'updated_at',
            ],
            DB::table('inventory_parts')
                ->whereIn('type', $componentTypes)
                ->whereNull('deleted_at')
                ->select([
                    'id',
                    'sku',
                    'name',
                    'type',
                    'description',
                    'catalog_price',
                    'catalog_currency',
                    'cost_price',
                    'selling_price',
                    'created_at',
                    'updated_at',
                ]),
        );

        DB::table('custom_pc_component_supplier')->insertUsing(
            [
                'custom_pc_component_id',
                'supplier_id',
                'shopee_product_url',
                'cost_price',
                'unit_price',
                'created_at',
                'updated_at',
            ],
            DB::table('inventory_part_supplier')
                ->join('custom_pc_components', 'custom_pc_components.source_inventory_part_id', '=', 'inventory_part_supplier.inventory_part_id')
                ->selectRaw('custom_pc_components.id, inventory_part_supplier.supplier_id, inventory_part_supplier.shopee_product_url, inventory_part_supplier.cost_price, inventory_part_supplier.unit_price, inventory_part_supplier.created_at, inventory_part_supplier.updated_at'),
        );

        DB::table('custom_pc_component_supplier')->insertUsing(
            [
                'custom_pc_component_id',
                'supplier_id',
                'created_at',
                'updated_at',
            ],
            DB::table('custom_pc_components')
                ->join('inventory_parts', 'inventory_parts.id', '=', 'custom_pc_components.source_inventory_part_id')
                ->whereNotNull('inventory_parts.supplier_id')
                ->whereNotExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('custom_pc_component_supplier')
                        ->whereColumn('custom_pc_component_supplier.custom_pc_component_id', 'custom_pc_components.id')
                        ->whereColumn('custom_pc_component_supplier.supplier_id', 'inventory_parts.supplier_id');
                })
                ->selectRaw('custom_pc_components.id, inventory_parts.supplier_id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP'),
        );

        $archiveTimestamp = now()->toDateTimeString();
        DB::table('custom_pc_components')
            ->whereIn('source_inventory_part_id', DB::table('inventory_parts')->select('id')->where('stock_quantity', 0)->whereNull('deleted_at'))
            ->update(['source_inventory_archived_at' => $archiveTimestamp]);
        DB::table('inventory_parts')
            ->whereIn('id', DB::table('custom_pc_components')
                ->select('source_inventory_part_id')
                ->where('source_inventory_archived_at', $archiveTimestamp))
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $archiveTimestamp]);

        Schema::table('billing_document_items', function (Blueprint $table): void {
            $table->foreignId('custom_pc_component_id')->nullable()->after('inventory_part_id')
                ->constrained('custom_pc_components')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('billing_document_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('custom_pc_component_id');
        });

        DB::table('inventory_parts')
            ->join('custom_pc_components', 'custom_pc_components.source_inventory_part_id', '=', 'inventory_parts.id')
            ->whereNotNull('custom_pc_components.source_inventory_archived_at')
            ->whereColumn('inventory_parts.deleted_at', 'custom_pc_components.source_inventory_archived_at')
            ->update(['inventory_parts.deleted_at' => null]);

        Schema::dropIfExists('custom_pc_component_supplier');
        Schema::dropIfExists('custom_pc_components');
    }
};
