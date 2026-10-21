<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailServerSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'from_address',
        'from_name',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_username',
        'imap_password',
        'imap_folder',
        'imap_archive_folder',
    ];

    protected function casts(): array
    {
        return [
            'smtp_password' => 'encrypted',
            'imap_password' => 'encrypted',
        ];
    }
}
