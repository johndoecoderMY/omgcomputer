<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('flow_charts')->select(['id', 'steps'])->orderBy('id')->each(function ($chart): void {
            $steps = array_values(json_decode($chart->steps, true, flags: JSON_THROW_ON_ERROR));
            $nodeIds = array_map(fn (int $index): string => 'node_' . $index, array_keys($steps));

            foreach ($steps as $index => &$step) {
                $step['id'] = $nodeIds[$index];
                $step['connections'] = isset($nodeIds[$index + 1]) ? [$nodeIds[$index + 1]] : [];
            }
            unset($step);

            DB::table('flow_charts')
                ->where('id', $chart->id)
                ->update(['steps' => json_encode($steps, JSON_THROW_ON_ERROR)]);
        });
    }

    public function down(): void
    {
        DB::table('flow_charts')->select(['id', 'steps'])->orderBy('id')->each(function ($chart): void {
            $steps = json_decode($chart->steps, true, flags: JSON_THROW_ON_ERROR);

            foreach ($steps as &$step) {
                unset($step['id'], $step['connections']);
            }
            unset($step);

            DB::table('flow_charts')
                ->where('id', $chart->id)
                ->update(['steps' => json_encode($steps, JSON_THROW_ON_ERROR)]);
        });
    }
};
