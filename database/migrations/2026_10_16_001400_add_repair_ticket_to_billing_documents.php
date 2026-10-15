<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->foreignId('repair_ticket_id')->nullable()->after('user_id')->constrained('repair_tickets')->nullOnDelete();
            $table->index(['repair_ticket_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('repair_ticket_id');
        });
    }
};