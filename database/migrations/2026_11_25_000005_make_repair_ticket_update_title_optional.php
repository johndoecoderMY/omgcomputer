<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_ticket_updates', function (Blueprint $table): void {
            $table->string('title', 180)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('repair_ticket_updates', function (Blueprint $table): void {
            $table->string('title', 180)->nullable(false)->change();
        });
    }
};
