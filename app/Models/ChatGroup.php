<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatGroup extends Model
{
    protected $fillable = ['name', 'created_by_user_id', 'image'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_group_members', 'group_id', 'user_id')
            ->withPivot('role', 'nickname')
            ->withTimestamps();
    }

    public function memberPivots(): HasMany
    {
        return $this->hasMany(ChatGroupMember::class, 'group_id', 'id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'group_id', 'id');
    }

    public function isMember(int $userId): bool
    {
        return $this->memberPivots()->where('user_id', $userId)->exists();
    }
}
