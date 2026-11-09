<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_previews', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('category', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('preview_url', 2048)->nullable();
            $table->string('image_path');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_previews');
    }
};
