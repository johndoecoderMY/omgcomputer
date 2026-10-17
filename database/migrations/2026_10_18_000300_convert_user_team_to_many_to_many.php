<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_user', function (Blueprint $table): void {
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['team_id', 'user_id']);
            $table->index('user_id');
        });

        DB::table('users')
            ->whereNotNull('team_id')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('team_user')->insert([
                    'team_id' => $user->team_id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('team_user')
            ->select('user_id', DB::raw('MIN(team_id) as team_id'))
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->each(function (object $membership): void {
                DB::table('users')
                    ->where('id', $membership->user_id)
                    ->update(['team_id' => $membership->team_id]);
            });

        Schema::dropIfExists('team_user');
    }
};
