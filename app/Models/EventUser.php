<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventUser extends Model
{
    public $timestamps = false;
    protected $fillable = ['event_id', 'user_id', 'created_at'];

    public function events()
    {
        return $this->belongsToMany(\App\Models\Event::class, 'event_user')->withTimestamps();
    }
}
