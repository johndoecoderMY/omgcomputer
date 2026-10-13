<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'email', 'phone'];

    public function tickets(): HasMany
    {
        return $this->hasMany(RepairTicket::class)->latest();
    }
}
