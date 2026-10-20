<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->after('repair_ticket_id')->constrained()->nullOnDelete();
            $table->index(['project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
