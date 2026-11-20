<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_message_templates', function (Blueprint $table): void {
            $table->string('language', 10)->default('en')->after('channel');
        });

        DB::table('customer_message_templates')
            ->where('slug', 'like', '%-ms')
            ->update(['language' => 'ms']);
    }

    public function down(): void
    {
        Schema::table('customer_message_templates', function (Blueprint $table): void {
            $table->dropColumn('language');
        });
    }
};
