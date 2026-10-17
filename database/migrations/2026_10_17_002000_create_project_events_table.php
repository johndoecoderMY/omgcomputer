<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('event_type', 32);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('location', 180)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'project_id']);
            $table->index(['project_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_events');
    }
};
