<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'profile_photo_path',
        'password',
        'force_password_change',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'force_password_change' => 'boolean',
        ];
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path
            ? Storage::disk('public')->url($this->profile_photo_path)
            : null;
    }

    public function planningTasks(): HasMany
    {
        return $this->hasMany(PlanningTask::class);
    }

    public function planningProjects(): HasMany
    {
        return $this->hasMany(PlanningProject::class);
    }

    public function assignedPlanningTasks(): HasMany
    {
        return $this->hasMany(PlanningTask::class, 'assigned_to');
    }

    public function assignedTasks(): MorphToMany
    {
        return $this->morphedByMany(Task::class, 'assignable', 'user_assignments')->withTimestamps();
    }

    public function assignedRepairTickets(): MorphToMany
    {
        return $this->morphedByMany(RepairTicket::class, 'assignable', 'user_assignments')->withTimestamps();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    public function profileActivities(): HasMany
    {
        return $this->hasMany(ProfileActivity::class, 'actor_id');
    }
}
