<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('flow_chart_id')->nullable()->after('assigned_to')->constrained('flow_charts')->nullOnDelete();
            $table->string('flow_chart_step_id', 64)->nullable()->after('flow_chart_id');
            $table->index(['flow_chart_id', 'flow_chart_step_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('flow_chart_id');
            $table->dropColumn('flow_chart_step_id');
        });
    }
};