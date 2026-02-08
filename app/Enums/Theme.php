<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class Theme extends Enum
{
    const int SINGING = 1;
    const int SPORT = 2;
    const int EATING = 2;
    const int DATING = 4;
    const int OTHER = 6;

    public static function getDescription($value): string
    {
        return match ($value) {
            self::SINGING => '唱歌',
            self::SPORT => '運動',
            self::EATING => '吃飯',
            self::DATING => '約會',
            self::OTHER => '其他',
            default => throw new \InvalidArgumentException("Invalid value: $value"),
        };
    }
}
