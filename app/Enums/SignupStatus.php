<?php
declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static OptionOne()
 * @method static static OptionTwo()
 * @method static static OptionThree()
 */
final class SignupStatus extends Enum
{
    const int PENDING = 0;
    const int APPROVED = 1;
    const int REJECTED = 2;
    const int CANCELED = 3;
}
