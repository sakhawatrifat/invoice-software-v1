<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageHidden extends Model
{
    protected $table = 'chat_message_hidden';

    protected $fillable = ['user_id', 'message_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id', 'id');
    }
}
