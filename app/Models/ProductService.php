<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductService extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'category',
        'name',
        'scope',
        'audience',
        'price',
        'billing_price',
    ];

    protected function casts(): array
    {
        return [
            'billing_price' => 'decimal:2',
        ];
    }
}
