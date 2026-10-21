<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drama_scripts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('genre', 80)->nullable();
            $table->text('synopsis')->nullable();
            $table->string('status', 20)->default('draft');
            $table->json('characters');
            $table->json('scenes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drama_scripts');
    }
};
