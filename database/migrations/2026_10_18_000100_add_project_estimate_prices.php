<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_inventory_part', function (Blueprint $table): void {
            $table->decimal('unit_price', 12, 2)->nullable()->after('quantity');
            $table->string('price_source', 20)->nullable()->after('unit_price');
        });

        Schema::create('project_estimate_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category', 24)->default('other');
            $table->string('description', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
            $table->index(['project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_estimate_items');

        Schema::table('project_inventory_part', function (Blueprint $table): void {
            $table->dropColumn(['unit_price', 'price_source']);
        });
    }
};
