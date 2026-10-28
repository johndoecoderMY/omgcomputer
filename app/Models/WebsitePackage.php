<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class WebsitePackage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'package_notes',
        'pages',
    ];

    protected function casts(): array
    {
        return [
            'pages' => 'array',
        ];
    }

    public static function current(): self
    {
        if (! Schema::hasTable('website_packages')) {
            return new self();
        }

        return self::query()->find('primary') ?? new self(['id' => 'primary']);
    }
}
