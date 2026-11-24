<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeedback extends Model
{
    protected $table = 'project_feedback';

    protected $fillable = ['project_id', 'customer_user_id', 'rating', 'comment', 'submitted_at'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'submitted_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
