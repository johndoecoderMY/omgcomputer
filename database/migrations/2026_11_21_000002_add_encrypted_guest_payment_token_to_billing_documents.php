<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->text('guest_payment_token_encrypted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->dropColumn('guest_payment_token_encrypted');
        });
    }
};
