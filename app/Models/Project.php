<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

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

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function repairTickets(): HasMany
    {
        return $this->hasMany(RepairTicket::class);
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
