<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'email',
        'name',
        'nickname',
        'avatar',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
    ];
}
