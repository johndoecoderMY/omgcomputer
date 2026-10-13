<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlanningProject extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'visibility',
        'images',
    ];

    protected function casts(): array
    {
        return ['images' => 'array'];
    }

    public static function omgComputer(User $owner): self
    {
        return static::query()->firstOrCreate(
            ['slug' => 'omg-computer'],
            [
                'user_id' => $owner->id,
                'name' => 'OMG Computer',
                'description' => 'The OMG Computer website, repair shop, and day-to-day operations.',
                'visibility' => 'team',
            ],
        );
    }

    protected static function booted(): void
    {
        static::creating(function (self $project): void {
            if (blank($project->slug)) {
                $project->slug = Str::slug($project->name).'-'.Str::lower(Str::random(6));
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(PlanningTask::class, 'project_id');
    }
}
