<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_parts', function (Blueprint $table): void {
            $table->decimal('catalog_price', 12, 2)->nullable();
            $table->string('catalog_currency', 3)->nullable();
            $table->index(['catalog_currency', 'catalog_price']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_parts', function (Blueprint $table): void {
            $table->dropIndex(['catalog_currency', 'catalog_price']);
            $table->dropColumn(['catalog_price', 'catalog_currency']);
        });
    }
};
