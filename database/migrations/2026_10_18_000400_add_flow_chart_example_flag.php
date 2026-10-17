<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flow_charts', function (Blueprint $table): void {
            $table->boolean('is_example')->default(false)->after('subject_name');
            $table->index(['category', 'is_example']);
        });

        DB::table('flow_charts')
            ->where('title', 'like', 'Example %')
            ->update([
                'title' => DB::raw('SUBSTR(title, 9)'),
                'is_example' => true,
            ]);

        DB::table('flow_charts')
            ->where('title', 'like', '%(Example)')
            ->update([
                'title' => DB::raw('SUBSTR(title, 1, LENGTH(title) - 10)'),
                'is_example' => true,
            ]);

        DB::table('flow_chart_project')
            ->whereIn('flow_chart_id', DB::table('flow_charts')->select('id')->where('is_example', true))
            ->delete();
    }

    public function down(): void
    {
        Schema::table('flow_charts', function (Blueprint $table): void {
            $table->dropIndex(['category', 'is_example']);
            $table->dropColumn('is_example');
        });
    }
};
