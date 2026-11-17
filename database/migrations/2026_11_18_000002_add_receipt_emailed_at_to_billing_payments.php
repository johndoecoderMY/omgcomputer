<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_payments', function (Blueprint $table): void {
            $table->timestamp('receipt_emailed_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_payments', function (Blueprint $table): void {
            $table->dropColumn('receipt_emailed_at');
        });
    }
};
