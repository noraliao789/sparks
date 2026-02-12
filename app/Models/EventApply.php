<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventApply extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'user_id',
        'message',
        'reason',
        'unlock_photo',
        'status',
        'created_at',
        'updated_at',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
