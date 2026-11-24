<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->string('face_to_face_name')->nullable()->after('customer_id');
            $table->string('face_to_face_email', 255)->nullable()->after('face_to_face_name');
            $table->string('face_to_face_phone', 64)->nullable()->after('face_to_face_email');
        });
    }

    public function down(): void
    {
        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'face_to_face_name',
                'face_to_face_email',
                'face_to_face_phone',
            ]);
        });
    }
};
