<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'theme_id',
        'pay_id',
        'title',
        'description',
        'num',
        'start_time',
        'end_time',
        'creator_by',
        'created_at',
        'updated_at',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'event_user')->withTimestamps();
    }

    // 被邀請的使用者
    public function invitedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user', 'event_id', 'user_id');
    }

    public function is_private(): bool
    {
        return $this->invitedUsers()->exists();
    }

    public function applies()
    {
        return $this->hasMany(\App\Models\EventApply::class);
    }
}
