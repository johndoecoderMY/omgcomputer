<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('billing_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider_purchase_id')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->text('checkout_url')->nullable();
            $table->string('status', 24)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['billing_document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payment_attempts');
    }
};
