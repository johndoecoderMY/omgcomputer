<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->string('guest_payment_token_hash', 64)->nullable();
            $table->timestamp('guest_payment_link_expires_at')->nullable();
            $table->unique('guest_payment_token_hash', 'billing_docs_guest_token_unique');
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->dropUnique('billing_docs_guest_token_unique');
            $table->dropColumn(['guest_payment_token_hash', 'guest_payment_link_expires_at']);
        });
    }
};
