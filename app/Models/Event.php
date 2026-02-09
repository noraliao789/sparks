<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'theme_id',
        'pay_id',
        'title',
        'description',
        'num',
        'start_time',
        'end_time',
        'creator_by',
    ];

    public function participants()
    {
        return $this->belongsToMany(\App\Models\User::class, 'event_user')->withTimestamps();
    }
}
