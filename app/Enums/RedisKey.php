<?php

declare(strict_types=1);

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RedisKey extends Enum
{
    const string AUTH_USER_TOKEN = 'auth:user:token:'; // + {sha256(token)}
    const string AUTH_USER_ID = 'auth:user:id:';    // + {userId}:{clientType}
    const string LOCK_TOKEN_CHECK = 'lock:token:check:';
    const string USER_INFO = 'user:info:';
    const string EVENT_PARTICIPANTS = 'event:participants:';
    const string EVENT_BASIC = 'event:basic:';
}
