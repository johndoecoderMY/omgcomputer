<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

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
        if (! static::liveModeEnabled()) {
            return true;
        }

        return static::query()->find('primary')?->test_mode
            ?? false;
    }

    public static function liveModeEnabled(): bool
    {
        return (bool) config('services.chip.live_enabled', false)
            && ! (bool) config('services.chip.test_mode', true);
    }

    public static function setTestMode(bool $enabled): void
    {
        if (! $enabled && ! static::liveModeEnabled()) {
            throw new LogicException('CHIP live mode is disabled.');
        }

        static::query()->updateOrCreate(
            ['id' => 'primary'],
            ['test_mode' => $enabled],
        );
    }
}
