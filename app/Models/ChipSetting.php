<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChipSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'test_mode',
    ];

    protected function casts(): array
    {
        return [
            'test_mode' => 'boolean',
        ];
    }

    public static function testMode(): bool
    {
        return static::query()->find('primary')?->test_mode
            ?? (bool) config('services.chip.test_mode', false);
    }

    public static function setTestMode(bool $enabled): void
    {
        static::query()->updateOrCreate(
            ['id' => 'primary'],
            ['test_mode' => $enabled],
        );
    }
}
