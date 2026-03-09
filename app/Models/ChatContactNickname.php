<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatContactNickname extends Model
{
    protected $table = 'chat_contact_nicknames';

    protected $fillable = ['user_id', 'contact_user_id', 'nickname'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contact_user_id', 'id');
    }
}
