<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_page_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('page_id', 40)->unique();
            $table->string('page_name');
            $table->string('category')->nullable();
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_page_connections');
    }
};
