<?php

namespace App\Http\Controllers;
use App\Models\FlowChart;
use App\Models\Task;
use Illuminate\View\View;

class DevelopmentController extends Controller
{
    public function index(): View
    {
        $tasks = Task::query()->get();

        return view('development.overview', [
            'flowCharts' => FlowChart::latest()->get(),
            'taskCounts' => [
                'total' => $tasks->count(),
                'done' => $tasks->where('status', 'done')->count(),
                'inProgress' => $tasks->where('status', 'in_progress')->count(),
                'remaining' => $tasks->where('status', '!=', 'done')->count(),
                'percent' => $tasks->isEmpty()
                    ? 0
                    : (int) round($tasks->where('status', 'done')->count() / $tasks->count() * 100),
            ],
        ]);
    }

    public function plan(): View
    {
        return view('development.plan');
    }
}
