<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_shops', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 24);
            $table->string('external_shop_id', 120);
            $table->string('shop_name')->nullable();
            $table->string('region', 16)->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->timestamp('refresh_token_expires_at')->nullable();
            $table->timestamp('last_order_import_at')->nullable();
            $table->timestamps();
            $table->unique(['platform', 'external_shop_id']);
        });

        Schema::create('marketplace_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_shop_id')->constrained('marketplace_shops')->cascadeOnDelete();
            $table->string('external_order_id', 120);
            $table->string('status', 64)->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->string('currency', 8)->nullable();
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->json('raw_payload');
            $table->timestamps();
            $table->unique(['marketplace_shop_id', 'external_order_id']);
            $table->index(['marketplace_shop_id', 'ordered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('marketplace_shops');
    }
};
