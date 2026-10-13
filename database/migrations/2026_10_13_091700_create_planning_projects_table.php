<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('visibility', 24)->default('personal');
            $table->timestamps();

            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_projects');
    }
};
