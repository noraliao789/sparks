<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class PayBill extends Enum
{
    const int AA = 1;
    const int I_PAY = 2;
    const int YOU_PAY = 3;

    public static function getDescription($value): string
    {
        return match ($value) {
            self::AA => 'AA',
            self::I_PAY => '我付',
            self::YOU_PAY => '他付',
            default => parent::getDescription($value),
        };
    }
}
