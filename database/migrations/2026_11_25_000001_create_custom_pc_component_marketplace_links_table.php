<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = 'custom_pc_component_marketplace_links';

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('custom_pc_component_id');
                $table->string('marketplace', 30);
                $table->text('url');
                $table->string('category', 40)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex($tableName, ['custom_pc_component_id', 'marketplace'])) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->index(['custom_pc_component_id', 'marketplace'], 'cpcl_component_marketplace_idx');
            });
        }

        $hasComponentForeignKey = collect(Schema::getForeignKeys($tableName))
            ->contains(fn (array $foreignKey): bool => in_array('custom_pc_component_id', $foreignKey['columns'], true)
                && $foreignKey['foreign_table'] === 'custom_pc_components');

        if (! $hasComponentForeignKey) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreign('custom_pc_component_id', 'cpcl_component_fk')
                    ->references('id')
                    ->on('custom_pc_components')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_pc_component_marketplace_links');
    }
};
