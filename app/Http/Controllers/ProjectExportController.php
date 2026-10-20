<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\FlowChartPngRenderer;
use App\Support\PdfAttachmentImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mpdf\Mpdf;

class ProjectExportController extends Controller
{
    private const SECTIONS = [
        'overview',
        'requirements',
        'customers',
        'technology_stack',
        'estimates',
        'schedule',
        'gantt',
        'flowcharts',
        'photos',
    ];

    public function export(
        Request $request,
        Project $project,
        FlowChartPngRenderer $flowChartRenderer,
        PdfAttachmentImage $attachmentImage,
    ) {
        $data = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['required', 'string', Rule::in(self::SECTIONS)],
            'flow_chart_ids' => ['nullable', 'array'],
            'flow_chart_ids.*' => ['required', 'integer', 'distinct'],
            'flowchart_mode' => ['required', Rule::in(['all', 'selected'])],
        ]);
        $sections = array_values(array_unique($data['sections']));
        $flowChartIds = $data['flow_chart_ids'] ?? [];
        $flowCharts = collect();

        if (in_array('flowcharts', $sections, true)) {
            if ($data['flowchart_mode'] === 'all') {
                $flowCharts = $project->flowCharts()->orderBy('title')->get();
            } else {
                if ($flowChartIds === []) {
                    throw ValidationException::withMessages([
                        'flow_chart_ids' => 'Select at least one linked flowchart or choose to export all of them.',
                    ]);
                }

                $flowCharts = $project->flowCharts()
                    ->whereIn('flow_charts.id', $flowChartIds)
                    ->orderBy('title')
                    ->get();

                if ($flowCharts->count() !== count($flowChartIds)) {
                    throw ValidationException::withMessages([
                        'flow_chart_ids' => 'Choose only flowcharts linked to this project.',
                    ]);
                }
            }
        }

        $project->load([
            ...(in_array('requirements', $sections, true) ? ['requirements'] : []),
            ...(in_array('customers', $sections, true) ? ['customers'] : []),
            ...(in_array('technology_stack', $sections, true) ? ['technologyStack' => fn ($query) => $query->orderBy('name')] : []),
            ...(in_array('estimates', $sections, true) ? ['estimateItems' => fn ($query) => $query->orderBy('category')->orderBy('description')] : []),
            ...(in_array('schedule', $sections, true) ? ['events' => fn ($query) => $query->orderBy('starts_at')] : []),
            ...(in_array('gantt', $sections, true) ? [
                'tasks' => fn ($query) => $query->whereNull('parent_task_id')->with('subtasks'),
            ] : []),
            ...(in_array('photos', $sections, true) ? ['attachments'] : []),
        ]);
        foreach ($flowCharts as $flowChart) {
            $flowChart->makeVisible('steps');
        }
        $flowChartImages = $flowCharts->mapWithKeys(fn ($flowChart): array => [
            $flowChart->id => $flowChartRenderer->render($flowChart),
        ]);
        $projectPhotos = in_array('photos', $sections, true)
            ? $project->attachments->map(fn ($attachment): array => $attachmentImage->make($attachment))
            : collect();

        $tempDirectory = storage_path('app/mpdf-tmp');
        File::ensureDirectoryExists($tempDirectory);
        $mpdf = new Mpdf(['tempDir' => $tempDirectory]);
        $mpdf->AliasNbPages();
        $mpdf->SetFooter('|Page {PAGENO} of {nb}|');
        $mpdf->WriteHTML(view('projects.pdf', compact('project', 'sections', 'flowCharts', 'flowChartImages', 'projectPhotos'))->render());
        $filename = Str::slug($project->name).'-project-details.pdf';

        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
    }
}
