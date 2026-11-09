<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_services')->update(['is_active' => false]);

        DB::table('product_services')->where('id', 'basic-troubleshooting')->update([
            'name' => 'Computer/PC Troubleshooting',
            'is_active' => true,
            'deleted_at' => null,
        ]);
        DB::table('product_services')->where('id', 'fyp-consultation')->update([
            'name' => 'FYP Consultation',
            'is_active' => true,
            'deleted_at' => null,
        ]);
    }

    public function down(): void
    {
        DB::table('product_services')->update(['is_active' => true]);
        DB::table('product_services')->where('id', 'basic-troubleshooting')->update([
            'name' => 'Basic troubleshooting',
        ]);
        DB::table('product_services')->where('id', 'fyp-consultation')->update([
            'name' => 'FYP consultation',
        ]);
    }
};
