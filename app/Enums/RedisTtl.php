<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class RedisTtl extends Enum
{
    const int AUTH_TOKEN = 60 * 60 * 24 * 7;

    const int AUTH_USER = 60 * 60 * 24 * 7;

    const int TOKEN_LOCK = 86400;

    const int USER_INFO = 60 * 60 * 24;
}
