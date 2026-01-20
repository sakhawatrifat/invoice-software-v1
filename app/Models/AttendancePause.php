<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendancePause extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attendance_id',
        'pause_start',
        'pause_end',
        'pause_duration_minutes',
    ];

    protected $casts = [
        'pause_start' => 'datetime',
        'pause_end' => 'datetime',
        'pause_duration_minutes' => 'decimal:2',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id', 'id');
    }
}
