<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectInventoryPart extends Pivot
{
    protected function casts(): array
    {
        return [
            'url_sources' => 'array',
        ];
    }
}
