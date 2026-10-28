<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_profiles')) {
            Schema::table('company_profiles', function (Blueprint $table): void {
                if (Schema::hasColumn('company_profiles', 'website_pages')) {
                    $table->dropColumn('website_pages');
                }
            });
            Schema::table('company_profiles', function (Blueprint $table): void {
                if (Schema::hasColumn('company_profiles', 'website_package_notes')) {
                    $table->dropColumn('website_package_notes');
                }
            });
        }

        Schema::create('website_packages', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->text('package_notes')->nullable();
            $table->json('pages')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_packages');
    }
};
