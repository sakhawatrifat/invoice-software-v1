<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'body',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'reply_to_message_id',
        'deleted_for_everyone_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'deleted_for_everyone_at' => 'datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id', 'id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ChatMessageRead::class, 'message_id', 'id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_message_id', 'id');
    }

    public function hiddenBy(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_message_hidden', 'message_id', 'user_id')
            ->withTimestamps();
    }

    public function isHiddenForUser(int $userId): bool
    {
        return $this->hiddenBy()->where('user_id', $userId)->exists();
    }

    public function isReadByUser(int $userId): bool
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }
}
