<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('nickname')->nullable()->after('name');
        });
        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->string('face_to_face_nickname')->nullable()->after('face_to_face_name');
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->dropColumn('face_to_face_nickname');
        });
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('nickname');
        });
    }
};
