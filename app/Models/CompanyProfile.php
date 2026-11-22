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

    public function formattedPhone(): ?string
    {
        $phone = trim((string) $this->phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $phone = '+'.$digits;
        }

        if (str_starts_with($digits, '60')) {
            $nationalNumber = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $nationalNumber = substr($digits, 1);
        } else {
            return $phone;
        }

        if ($nationalNumber === '') {
            return $phone;
        }

        $prefixLength = str_starts_with($nationalNumber, '1') ? 2 : 1;
        $prefix = substr($nationalNumber, 0, $prefixLength);
        $subscriber = substr($nationalNumber, $prefixLength);

        if (strlen($subscriber) === 7) {
            $subscriber = substr($subscriber, 0, 3).' '.substr($subscriber, 3);
        } elseif (strlen($subscriber) === 8) {
            $subscriber = substr($subscriber, 0, 4).' '.substr($subscriber, 4);
        }

        return '+60 '.$prefix.($subscriber !== '' ? '-'.$subscriber : '');
    }
}
