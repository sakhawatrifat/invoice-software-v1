<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatGroupMember extends Model
{
    protected $table = 'chat_group_members';

    protected $fillable = ['group_id', 'user_id', 'role', 'nickname'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ChatGroup::class, 'group_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
