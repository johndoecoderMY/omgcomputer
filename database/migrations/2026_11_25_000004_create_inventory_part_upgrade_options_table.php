<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_part_upgrade_options', function (Blueprint $table): void {
            $table->foreignId('base_inventory_part_id')->constrained('inventory_parts')->cascadeOnDelete();
            $table->foreignId('upgrade_inventory_part_id')->constrained('inventory_parts')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['base_inventory_part_id', 'upgrade_inventory_part_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_part_upgrade_options');
    }
};
