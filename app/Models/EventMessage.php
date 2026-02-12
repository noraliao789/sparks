<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventMessage extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'event_id',
        'user_id',
        'text',
        'created_at',
    ];
    protected $casts = [
        'created_at' => 'integer',
        'text' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
