<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_document_items', function (Blueprint $table): void {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('billing_document_items', function (Blueprint $table): void {
            $table->dropColumn('discount_amount');
        });
    }
};
