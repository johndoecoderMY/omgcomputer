<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document_number', 40)->unique();
            $table->string('type', 16);
            $table->string('status', 16)->default('draft');
            $table->string('customer_name', 180);
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->date('issued_at');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('stock_committed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status', 'issued_at']);
        });

        Schema::create('billing_document_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_part_id')->nullable()->constrained('inventory_parts')->nullOnDelete();
            $table->string('description', 255);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });

        Schema::create('billing_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 32);
            $table->string('reference', 120)->nullable();
            $table->date('received_at');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['received_at', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_payments');
        Schema::dropIfExists('billing_document_items');
        Schema::dropIfExists('billing_documents');
    }
};
