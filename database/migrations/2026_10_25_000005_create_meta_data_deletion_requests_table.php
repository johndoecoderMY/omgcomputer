<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_data_deletion_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('confirmation_code_hash', 64)->unique();
            $table->timestamp('completed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_data_deletion_requests');
    }
};
