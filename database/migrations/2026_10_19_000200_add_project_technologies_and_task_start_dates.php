<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('priority');
        });

        Schema::create('project_technologies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('version', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_technologies');

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('start_date');
        });
    }
};
