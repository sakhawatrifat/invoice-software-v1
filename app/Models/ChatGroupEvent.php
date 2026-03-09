<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupEvent extends Model
{
    protected $table = 'chat_group_events';

    protected $fillable = ['group_id', 'user_id', 'action', 'target_user_id', 'extra'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id', 'id');
    }
}
