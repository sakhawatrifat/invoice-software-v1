<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StickyNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'note_title',
        'note_description',
        'deadline',
        'reminder_datetime',
        'reminder_mail_sent_at',
        'status',
        'read_status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'reminder_datetime' => 'datetime',
            'reminder_mail_sent_at' => 'datetime',
            'read_status' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'sticky_note_user', 'sticky_note_id', 'user_id')
            ->withTimestamps();
    }

    public function activities()
    {
        return $this->hasMany(StickyNoteActivity::class, 'sticky_note_id', 'id')->latest();
    }

    /**
     * Scope: notes visible to user (admin = all; else owner business, creator, or assigned).
     */
    public function scopeVisibleToUser($query, $user)
    {
        if ($user->user_type === 'admin' && $user->is_staff != 1) {
            return $query;
        }
        $bid = $user->business_id;
        $uid = $user->id;
        return $query->where(function ($q) use ($bid, $uid) {
            $q->where('user_id', $bid)
                ->orWhere('created_by', $uid)
                ->orWhereHas('assignedUsers', function ($aq) use ($uid) {
                    $aq->where('user_id', $uid);
                });
        });
    }
}
