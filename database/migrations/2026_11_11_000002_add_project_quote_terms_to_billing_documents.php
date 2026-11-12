<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->text('project_scope')->nullable();
            $table->unsignedTinyInteger('included_revision_rounds')->default(3);
            $table->date('project_due_date')->nullable();
            $table->text('payment_terms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table): void {
            $table->dropColumn([
                'project_scope',
                'included_revision_rounds',
                'project_due_date',
                'payment_terms',
            ]);
        });
    }
};
