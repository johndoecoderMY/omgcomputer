<?php

namespace App\Observers;

use App\Models\Attachment;
use App\Models\Event;
use App\Models\Project;
use App\Models\ProjectActivity;
use App\Models\ProjectEstimateItem;
use App\Models\ProjectFeature;
use App\Models\ProjectLink;
use App\Models\ProjectMilestone;
use App\Models\ProjectModule;
use App\Models\ProjectQualityItem;
use App\Models\ProjectRequirement;
use App\Models\ProjectTechnology;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProjectActivityObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'Added');
    }

    public function updated(Model $model): void
    {
        $fields = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));
        if ($fields !== []) {
            $this->record($model, 'Updated', array_map(Str::headline(...), $fields));
        }
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'Removed');
    }

    private function record(Model $model, string $action, array $details = []): void
    {
        $projectId = $this->projectId($model);
        $type = $this->activityType($model);
        if ($projectId === null || $type === null || Auth::id() === null) {
            return;
        }

        ProjectActivity::query()->create([
            'project_id' => $projectId,
            'user_id' => Auth::id(),
            'summary' => $action.' '.$type,
            'details' => $details === [] ? null : $details,
        ]);
    }

    private function projectId(Model $model): ?int
    {
        if ($model instanceof ProjectFeature) {
            $projectId = ProjectModule::query()->whereKey($model->project_module_id)->value('project_id');

            return $projectId ? (int) $projectId : null;
        }

        if ($model instanceof Attachment) {
            if ($model->attachable_type === (new Project)->getMorphClass()) {
                return (int) $model->attachable_id;
            }

            if ($model->attachable_type === (new Task)->getMorphClass()) {
                $projectId = Task::query()->whereKey($model->attachable_id)->value('project_id');

                return $projectId ? (int) $projectId : null;
            }

            return null;
        }

        $projectId = $model->getAttribute('project_id');

        return $projectId ? (int) $projectId : null;
    }

    private function activityType(Model $model): ?string
    {
        return match (true) {
            $model instanceof Task => 'task',
            $model instanceof Event => 'calendar event',
            $model instanceof ProjectRequirement => 'requirement',
            $model instanceof ProjectModule => 'module',
            $model instanceof ProjectFeature => 'feature',
            $model instanceof ProjectMilestone => 'milestone',
            $model instanceof ProjectQualityItem => 'quality item',
            $model instanceof ProjectTechnology => 'technology',
            $model instanceof ProjectEstimateItem => 'estimate item',
            $model instanceof ProjectLink => 'project link',
            $model instanceof Attachment => $this->attachmentActivityType($model),
            default => null,
        };
    }

    private function attachmentActivityType(Attachment $attachment): ?string
    {
        if ($attachment->attachable_type === (new Project)->getMorphClass()) {
            return 'project photo';
        }

        return $attachment->attachable_type === (new Task)->getMorphClass()
            ? 'task photo'
            : null;
    }
}
