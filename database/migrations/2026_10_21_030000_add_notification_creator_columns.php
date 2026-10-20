<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('project_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
