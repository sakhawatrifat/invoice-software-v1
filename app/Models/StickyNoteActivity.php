<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StickyNoteActivity extends Model
{
    use HasFactory;

    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_STATUS = 'status';
    const ACTION_DELETE = 'delete';

    protected $fillable = [
        'sticky_note_id',
        'user_id',
        'action',
        'changes',
        'ip_address',
        'user_agent',
    ];

    public function stickyNote()
    {
        return $this->belongsTo(StickyNote::class, 'sticky_note_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
