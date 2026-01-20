<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'total_hours',
        'attendance_timeline',
        'status',
        'ip_address',
        'device_browser',
        'overtime_task_description',
        'forgot_clock_out',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_hours' => 'decimal:2',
        'attendance_timeline' => 'array',
        'forgot_clock_out' => 'boolean',
    ];

    protected $appends = ['running_total_hour'];

    /**
     * Get running total hours (for active clock-ins)
     * Calculates from attendance_timeline including current session minus pause times
     */
    public function getRunningTotalHourAttribute()
    {
        $timeline = $this->attendance_timeline ?? [];
        
        if (empty($timeline)) {
            // If no timeline, fallback to simple calculation
            if (!empty($this->check_in) && empty($this->check_out)) {
                $now = Carbon::now();
                $totalMinutes = $this->check_in->diffInMinutes($now);
                
                // Subtract total pause minutes
                $totalPauseMinutes = $this->pauses()
                    ->where('pause_start', '>=', $this->check_in)
                    ->get()
                    ->sum(function($pause) use ($now) {
                        if ($pause->pause_end) {
                            return $pause->pause_duration_minutes ?? 0;
                        } else {
                            // Active pause
                            return $pause->pause_start->diffInMinutes($now);
                        }
                    });
                
                $netMinutes = max(0, $totalMinutes - $totalPauseMinutes);
                return round($netMinutes / 60, 2);
            }
            return 0;
        }

        $totalHours = 0;
        $now = Carbon::now();

        foreach ($timeline as $session) {
            if (empty($session['clock_in'])) {
                continue;
            }

            $clockIn = Carbon::parse($session['clock_in']);
            
            // If session is completed (has clock_out)
            if (!empty($session['clock_out'])) {
                // Use the stored total_time from timeline (already excludes pauses)
                $totalHours += $session['total_time'] ?? 0;
            } else {
                // Current running session - calculate from clock_in to now
                $sessionMinutes = $clockIn->diffInMinutes($now);
                
                // Subtract pause times for this session (from timeline)
                $sessionPauseMinutes = $session['total_pause_minutes'] ?? 0;
                
                // Check for active pause in current session (not yet recorded in timeline)
                $activePause = $this->pauses()
                    ->whereNull('pause_end')
                    ->where('pause_start', '>=', $clockIn)
                    ->first();
                
                if ($activePause) {
                    // Add current active pause time (from pause_start to now)
                    $activePauseMinutes = $activePause->pause_start->diffInMinutes($now);
                    $sessionPauseMinutes += $activePauseMinutes;
                }
                
                $netSessionMinutes = max(0, $sessionMinutes - $sessionPauseMinutes);
                $totalHours += $netSessionMinutes / 60;
            }
        }

        return round($totalHours, 2);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function pauses()
    {
        return $this->hasMany(AttendancePause::class, 'attendance_id', 'id');
    }
}
