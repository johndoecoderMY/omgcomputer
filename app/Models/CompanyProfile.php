<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CompanyProfile extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'legal_name',
        'ssm_registration_number',
        'tax_identification_number',
        'sst_registration_number',
        'address',
        'phone',
        'email',
        'website_url',
        'bank_name',
        'bank_account_number',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
    ];

    protected function casts(): array
    {
        return [
            'bank_account_number' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        if (! Schema::hasTable('company_profiles')) {
            return new self(['legal_name' => 'OMG Computer']);
        }

        return self::query()->find('primary') ?? new self(['legal_name' => 'OMG Computer']);
    }
}
