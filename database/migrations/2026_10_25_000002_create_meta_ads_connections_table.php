<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meta_ads_connections', function (Blueprint $table): void {
            $table->id();
            $table->string('account_id', 40);
            $table->string('account_name');
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->foreignId('connected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meta_ads_connections');
    }
};
