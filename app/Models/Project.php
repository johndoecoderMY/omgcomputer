<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    protected $fillable = [
        'name',
        'created_by_user_id',
        'description',
        'requirements_notes',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $project): void {
            $taskIds = $project->tasks()->pluck('id')->all();
            $subtaskIds = $taskIds === []
                ? []
                : Task::query()->whereIn('parent_task_id', $taskIds)->pluck('id')->all();
            $project->assignees()->detach();

            $assignedTaskIds = array_merge($taskIds, $subtaskIds);
            if ($assignedTaskIds !== []) {
                DB::table('user_assignments')
                    ->where('assignable_type', Task::class)
                    ->whereIn('assignable_id', $assignedTaskIds)
                    ->delete();
            }
        });
    }

    public function scopeVisibleToCustomer(Builder $query, User $user): Builder
    {
        $email = strtolower(trim($user->email));
        if ($email === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $projects) use ($email): void {
            $projects->whereHas('customers', fn (Builder $customers) => $customers
                ->whereRaw('LOWER(email) = ?', [$email]))
                ->orWhereHas('repairTickets.customer', fn (Builder $customers) => $customers
                    ->whereRaw('LOWER(email) = ?', [$email]));
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignees(): MorphToMany
    {
        return $this->morphToMany(User::class, 'assignable', 'user_assignments')->withTimestamps();
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function repairTickets(): HasMany
    {
        return $this->hasMany(RepairTicket::class);
    }

    public function billingDocuments(): HasMany
    {
        return $this->hasMany(BillingDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function estimateItems(): HasMany
    {
        return $this->hasMany(ProjectEstimateItem::class);
    }

    public function technologyStack(): HasMany
    {
        return $this->hasMany(ProjectTechnology::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ProjectRequirement::class)->orderBy('sort_order');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ProjectLink::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function flowCharts(): BelongsToMany
    {
        return $this->belongsToMany(FlowChart::class)->withTimestamps();
    }

    public function inventoryParts(): BelongsToMany
    {
        return $this->belongsToMany(InventoryPart::class, 'project_inventory_part')
            ->using(ProjectInventoryPart::class)
            ->withPivot(['quantity', 'notes', 'url_sources', 'unit_price', 'price_source', 'added_by'])
            ->withTimestamps();
    }

    public function getProgressAttribute(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $completed = $this->completed_tasks_count
            ?? $this->tasks()->where('status', 'done')->count();

        return $total === 0 ? 0 : (int) round($completed / $total * 100);
    }
}
