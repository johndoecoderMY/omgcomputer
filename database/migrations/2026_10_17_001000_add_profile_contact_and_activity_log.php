<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 50)->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
        });

        Schema::create('profile_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->index();
            $table->string('event', 50);
            $table->json('changed_fields')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['actor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_activities');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'address']);
        });
    }
};
