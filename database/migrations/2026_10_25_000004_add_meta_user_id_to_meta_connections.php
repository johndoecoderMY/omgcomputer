<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_ads_connections', function (Blueprint $table): void {
            $table->string('meta_user_id', 40)->nullable()->index();
        });

        Schema::table('meta_page_connections', function (Blueprint $table): void {
            $table->string('meta_user_id', 40)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('meta_ads_connections', function (Blueprint $table): void {
            $table->dropIndex(['meta_user_id']);
            $table->dropColumn('meta_user_id');
        });

        Schema::table('meta_page_connections', function (Blueprint $table): void {
            $table->dropIndex(['meta_user_id']);
            $table->dropColumn('meta_user_id');
        });
    }
};
