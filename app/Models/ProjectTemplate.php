<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTemplate extends Model
{
    protected $fillable = [
        'created_by_user_id', 'name', 'category', 'description', 'objectives',
        'scope_in', 'scope_out', 'modules', 'milestones', 'checklist_items', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'milestones' => 'array',
            'checklist_items' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
