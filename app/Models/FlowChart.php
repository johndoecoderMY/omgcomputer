<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowChart extends Model
{
    public const CATEGORIES = [
        'website' => 'Website journey',
        'website_structure' => 'Website structure',
        'sitemap' => 'Sitemap',
        'process' => 'Business process',
        'product' => 'Product SOP',
        'service' => 'Service SOP',
        'education' => 'Student FYP SOP',
    ];

    public const WEBSITE_CATEGORIES = ['website', 'website_structure', 'sitemap'];

    protected $fillable = [
        'title',
        'category',
        'subject_name',
        'is_example',
        'description',
        'steps',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'is_example' => 'boolean',
        ];
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Flow chart';
    }

    public function isWebsite(): bool
    {
        return in_array($this->category, self::WEBSITE_CATEGORIES, true);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }
}
