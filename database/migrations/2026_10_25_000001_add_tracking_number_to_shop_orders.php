<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->string('tracking_number', 100)->nullable()->after('checkout_url');
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            $table->dropColumn('tracking_number');
        });
    }
};
