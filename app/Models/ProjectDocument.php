<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id', 'uploaded_by', 'title', 'category', 'path', 'original_name',
        'mime_type', 'size', 'visible_to_customer', 'document_date', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'visible_to_customer' => 'boolean',
            'document_date' => 'date',
            'expires_at' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
