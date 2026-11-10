<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_payment_attempts', function (Blueprint $table): void {
            $table->boolean('chip_test_mode')->nullable()->after('provider_purchase_id');
        });

        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->boolean('chip_test_mode')->nullable()->after('provider_purchase_id');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->dropColumn('chip_test_mode');
        });

        Schema::table('billing_payment_attempts', function (Blueprint $table): void {
            $table->dropColumn('chip_test_mode');
        });
    }
};
