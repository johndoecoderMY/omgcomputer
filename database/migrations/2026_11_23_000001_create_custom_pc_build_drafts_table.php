<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('custom_pc_build_drafts')) {
            return;
        }

        Schema::create('custom_pc_build_drafts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('build_name')->nullable();
            $table->string('intended_use', 1000)->nullable();
            $table->string('customer_name')->nullable();
            $table->json('form_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_pc_build_drafts');
    }
};
