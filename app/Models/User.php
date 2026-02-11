<?php

namespace App\Models;

use App\Models\Traits\UnSerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UnSerializeDate;

    public $timestamps = false;
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'email_verified_at',
        'line_verified_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'line_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function eventSignups()
    {
        return $this->hasMany(\App\Models\EventSignup::class);
    }
}
