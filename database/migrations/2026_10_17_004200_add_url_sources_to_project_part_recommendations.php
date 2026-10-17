<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_inventory_part', function (Blueprint $table): void {
            $table->json('url_sources')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('project_inventory_part', function (Blueprint $table): void {
            $table->dropColumn('url_sources');
        });
    }
};
