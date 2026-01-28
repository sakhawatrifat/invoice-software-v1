<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendancePause;
use App\Models\User;

class AttendanceController extends Controller
{
    /**
     * Get current attendance status
     */
    public function getCurrentStatus()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // First try to find attendance for today
        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->with('pauses')
            ->first();

        // If not found for today, check if there's an open attendance from a previous day
        if (!$attendance || (!empty($attendance->check_out))) {
            $previousOpenAttendance = Attendance::where('employee_id', $user->id)
                ->where('date', '<', $today)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->with('pauses')
                ->orderBy('check_in', 'desc')
                ->first();
            
            if ($previousOpenAttendance) {
                $attendance = $previousOpenAttendance;
            }
        }

        $isCheckedIn = false;
        $isPaused = false;
        $currentPauseId = null;
        $checkInTime = null;
        $currentSessionCheckInTime = null; // Current session's check-in time from timeline
        $totalWorkMinutes = 0;
        $totalPauseMinutes = 0;
        $currentPauseStart = null;

        if ($attendance) {
            $isCheckedIn = !empty($attendance->check_in) && empty($attendance->check_out);
            
            if ($isCheckedIn) {
                $checkInTime = $attendance->check_in; // Keep for reference (first check-in of day)
                $now = Carbon::now();

                // Get the current session's check-in time from attendance_timeline
                // Find the last open session (clock_out is null)
                $timeline = $attendance->attendance_timeline ?? [];
                if (!empty($timeline)) {
                    // Find the last entry with clock_out = null
                    for ($i = count($timeline) - 1; $i >= 0; $i--) {
                        $entry = $timeline[$i];
                        if (is_array($entry) && isset($entry['clock_in']) && empty($entry['clock_out'])) {
                            $currentSessionCheckInTime = Carbon::parse($entry['clock_in']);
                            break;
                        }
                    }
                }
                
                // If no open session found in timeline, use the main check_in (first session)
                if (!$currentSessionCheckInTime) {
                    $currentSessionCheckInTime = $checkInTime;
                }

                // Check for active pause
                $activePause = AttendancePause::where('attendance_id', $attendance->id)
                    ->whereNull('pause_end')
                    ->first();

                if ($activePause) {
                    $isPaused = true;
                    $currentPauseId = $activePause->id;
                    $currentPauseStart = $activePause->pause_start;
                    // When paused, work time stops at pause start
                    $totalWorkMinutes = $currentSessionCheckInTime->diffInMinutes($currentPauseStart);
                    $pauseMinutes = $currentPauseStart->diffInMinutes($now);
                    $totalPauseMinutes += $pauseMinutes;
                } else {
                    // Not paused, calculate current session work time
                    $totalWorkMinutes = $currentSessionCheckInTime->diffInMinutes($now);
                }

                // Calculate total pause time from completed pauses in current session only
                // Only count pauses that started after the current session's check-in time
                $completedPauses = AttendancePause::where('attendance_id', $attendance->id)
                    ->where('pause_start', '>=', $currentSessionCheckInTime)
                    ->whereNotNull('pause_end')
                    ->get();

                foreach ($completedPauses as $pause) {
                    $totalPauseMinutes += $pause->pause_duration_minutes ?? 0;
                }
            }
        }

        // Get previous total hours from DB (this is the actual accumulated time from all completed sessions)
        $previousTotalHours = 0;
        if ($attendance && $attendance->total_hours) {
            $previousTotalHours = $attendance->total_hours;
        }

        return response()->json([
            'is_checked_in' => $isCheckedIn,
            'is_paused' => $isPaused,
            'current_pause_id' => $currentPauseId,
            'check_in_time' => $currentSessionCheckInTime ? $currentSessionCheckInTime->format('Y-m-d H:i:s') : ($checkInTime ? $checkInTime->format('Y-m-d H:i:s') : null),
            'current_pause_start' => $currentPauseStart ? $currentPauseStart->format('Y-m-d H:i:s') : null,
            'total_work_minutes' => $totalWorkMinutes,
            'total_pause_minutes' => $totalPauseMinutes,
            'net_work_minutes' => $totalWorkMinutes - $totalPauseMinutes,
            'previous_total_hours' => $previousTotalHours, // This is the actual total_hours from DB (all completed sessions)
            'forgot_clock_out' => $attendance && $attendance->forgot_clock_out ? true : false,
        ]);
    }

    /**
     * Check-in
     */
    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // Check if already checked in today (without check-out)
        $existingAttendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today. Please check out first.',
            ], 400);
        }

        // Also check if there's an open attendance from a previous day
        $previousOpenAttendance = Attendance::where('employee_id', $user->id)
            ->where('date', '<', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->orderBy('check_in', 'desc')
            ->first();

        if ($previousOpenAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'You have an open attendance from a previous day. Please check out from that day first.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get or create attendance for today
            $attendance = Attendance::firstOrCreate(
                [
                    'employee_id' => $user->id,
                    'date' => $today,
                ],
                [
                    'check_in' => Carbon::now(),
                    'status' => 'Present',
                    'ip_address' => $request->ip(),
                    'device_browser' => $request->userAgent(),
                ]
            );

            $now = Carbon::now();
            
            // If attendance exists (from previous check-out on the same day), don't update check_in
            // Keep the first check_in time, just reset check_out for new session
            if ($attendance->check_out && $attendance->date->isSameDay($today)) {
                // Continue from previous attendance on the same day
                // Keep first check_in, reset check_out to allow new session
                // Add new session start to timeline: ["clock_in" => "...", "clock_out" => null, "total_time" => null]
                $timeline = $attendance->attendance_timeline ?? [];
                $timeline[] = [
                    'clock_in' => $now->format('Y-m-d H:i:s'),
                    'clock_out' => null,
                    'total_time' => null
                ];
                $attendance->attendance_timeline = $timeline;
                $attendance->check_out = null; // Reset check-out to allow new session
                $attendance->ip_address = $request->ip();
                $attendance->device_browser = $request->userAgent();
                $attendance->status = 'Present';
                $attendance->save();
            } elseif (empty($attendance->check_in)) {
                // First time check-in for today - set check_in (this will be the first and only check_in)
                $attendance->check_in = $now;
                $attendance->ip_address = $request->ip();
                $attendance->device_browser = $request->userAgent();
                $attendance->save();
            }

            DB::commit();

            // Refresh attendance to get updated data
            $attendance->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully.',
                'data' => [
                    'check_in_time' => $attendance->check_in->format('Y-m-d H:i:s'),
                    'date' => $attendance->date->format('Y-m-d'),
                    'previous_total_hours' => $attendance->total_hours ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Check-in error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check in. Please try again.',
            ], 500);
        }
    }

    /**
     * Check-out
     */
    public function checkOut(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // First try to find attendance for today
        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();

        // If not found for today, find the most recent open attendance (handles cross-day checkout)
        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $user->id)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->orderBy('check_in', 'desc')
                ->first();
        }

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'You have not checked in. Please check in first.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Determine the current session's check-in time
            $timeline = $attendance->attendance_timeline ?? [];
            $checkInTime = null;
            
            if (!empty($timeline)) {
                // Get the last entry - if it has clock_in but no clock_out, that's the current session
                $lastEntry = end($timeline);
                if (is_array($lastEntry) && isset($lastEntry['clock_in']) && empty($lastEntry['clock_out'])) {
                    // Current session check-in is in the timeline
                    $checkInTime = Carbon::parse($lastEntry['clock_in']);
                } else {
                    // All entries are complete, use the first check_in for this session
                    // Actually, if all are complete, there shouldn't be an open session
                    // This shouldn't happen, but fallback to first check_in
                    $checkInTime = Carbon::parse($attendance->check_in);
                }
            } else {
                // First session - use the first check_in
                $checkInTime = Carbon::parse($attendance->check_in);
            }
            
            $forgotClockOut = $request->input('forgot_clock_out', false);
            
            // Get daily work time from env (default 8 hours)
            $dailyWorkTimeHours = (float) env('DAILY_WORK_TIME', 8);
            $dailyWorkTimeMinutes = $dailyWorkTimeHours * 60;
            
            if ($forgotClockOut) {
                // When forgot clock out: Calculate check-out time to be exactly 8 hours of work (excluding breaks)
                
                // First, get all completed pauses in this session
                $completedPauses = AttendancePause::where('attendance_id', $attendance->id)
                    ->where('pause_start', '>=', $checkInTime)
                    ->whereNotNull('pause_end')
                    ->get();
                
                $totalPauseMinutes = $completedPauses->sum('pause_duration_minutes') ?? 0;
                
                // Get active pause if exists
                $activePause = AttendancePause::where('attendance_id', $attendance->id)
                    ->where('pause_start', '>=', $checkInTime)
                    ->whereNull('pause_end')
                    ->first();

                // Calculate check-out time: check_in + 8 hours + total_break_time
                // This ensures exactly 8 hours of work time (excluding breaks)
                $calculatedCheckOutTime = $checkInTime->copy()
                    ->addHours($dailyWorkTimeHours)
                    ->addMinutes($totalPauseMinutes);

                // If there's an active pause, end it at the calculated check-out time
                if ($activePause) {
                    $pauseDuration = $activePause->pause_start->diffInMinutes($calculatedCheckOutTime);
                    $activePause->pause_end = $calculatedCheckOutTime;
                    $activePause->pause_duration_minutes = $pauseDuration;
                    $activePause->save();
                    $totalPauseMinutes += $pauseDuration;
                    
                    // Recalculate check-out time with the active pause included
                    $calculatedCheckOutTime = $checkInTime->copy()
                        ->addHours($dailyWorkTimeHours)
                        ->addMinutes($totalPauseMinutes);
                }

                $checkOutTime = $calculatedCheckOutTime;
                $sessionHours = $dailyWorkTimeHours; // Exactly 8 hours (or daily work time)
                
                // Calculate total work minutes and net work minutes for response
                $totalWorkMinutes = $checkInTime->diffInMinutes($checkOutTime);
                $netWorkMinutes = $totalWorkMinutes - $totalPauseMinutes;
            } else {
                // Normal check-out: use current time
                $checkOutTime = Carbon::now();
                
                // End any active pause
                $activePause = AttendancePause::where('attendance_id', $attendance->id)
                    ->whereNull('pause_end')
                    ->first();

                if ($activePause) {
                    $pauseEnd = Carbon::now();
                    $pauseDuration = $activePause->pause_start->diffInMinutes($pauseEnd);
                    $activePause->pause_end = $pauseEnd;
                    $activePause->pause_duration_minutes = $pauseDuration;
                    $activePause->save();
                }

                // Calculate total pause time for this session
                $totalPauseMinutes = AttendancePause::where('attendance_id', $attendance->id)
                    ->where(function($query) use ($checkInTime, $checkOutTime) {
                        $query->where(function($q) use ($checkInTime, $checkOutTime) {
                            // Pauses that started and ended in this session
                            $q->where('pause_start', '>=', $checkInTime)
                              ->whereNotNull('pause_end')
                              ->where('pause_end', '<=', $checkOutTime);
                        })->orWhere(function($q) use ($checkInTime, $checkOutTime) {
                            // Active pause that started in this session
                            $q->where('pause_start', '>=', $checkInTime)
                              ->whereNull('pause_end');
                        });
                    })
                    ->get()
                    ->sum(function($pause) use ($checkOutTime) {
                        if ($pause->pause_end) {
                            return $pause->pause_duration_minutes ?? 0;
                        } else {
                            // Active pause - calculate duration up to check-out
                            return $pause->pause_start->diffInMinutes($checkOutTime);
                        }
                    });

                // Calculate work time for this session (excluding pauses)
                $totalWorkMinutes = $checkInTime->diffInMinutes($checkOutTime);
                $netWorkMinutes = $totalWorkMinutes - $totalPauseMinutes;
                $sessionHours = round($netWorkMinutes / 60, 2);
            }

            // Get previous total hours and add current session
            $previousTotalHours = $attendance->total_hours ?? 0;
            $totalHours = $previousTotalHours + $sessionHours;

            // Check for overtime (based on total accumulated time) - only if not forgot clock out
            if (!$forgotClockOut) {
                $totalNetMinutes = ($previousTotalHours * 60) + ($sessionHours * 60);
                $isOvertime = $totalNetMinutes > $dailyWorkTimeMinutes;
                $overtimeTaskDescription = $request->input('overtime_task_description');

                if ($isOvertime) {
                    if (empty($overtimeTaskDescription)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Please provide a description of the tasks completed during overtime.',
                            'requires_overtime_description' => true,
                        ], 400);
                    }
                    $attendance->overtime_task_description = $overtimeTaskDescription;
                }
            } else {
                // If forgot clock out, clear overtime description
                $attendance->overtime_task_description = null;
            }

            // Always update check_out to the latest check-out time
            $attendance->check_out = $checkOutTime;
            $attendance->total_hours = $totalHours;
            $attendance->forgot_clock_out = $forgotClockOut;
            
            // Calculate pause times for this session (between check_in and check_out)
            // Note: $totalPauseMinutes is already calculated above in both branches
            $sessionPauses = AttendancePause::where('attendance_id', $attendance->id)
                ->where(function($query) use ($checkInTime, $checkOutTime) {
                    $query->where(function($q) use ($checkInTime, $checkOutTime) {
                        // Pauses that started and ended in this session
                        $q->where('pause_start', '>=', $checkInTime)
                          ->whereNotNull('pause_end')
                          ->where('pause_end', '<=', $checkOutTime);
                    })->orWhere(function($q) use ($checkInTime, $checkOutTime) {
                        // Active pause that started in this session (ended at check-out)
                        $q->where('pause_start', '>=', $checkInTime)
                          ->where(function($subQ) use ($checkOutTime) {
                              $subQ->whereNull('pause_end')
                                   ->orWhere('pause_end', '<=', $checkOutTime);
                          });
                    });
                })
                ->get()
                ->map(function($pause) use ($checkOutTime) {
                    $pauseStart = Carbon::parse($pause->pause_start);
                    $pauseEnd = $pause->pause_end ? Carbon::parse($pause->pause_end) : $checkOutTime;
                    $pauseDuration = $pause->pause_duration_minutes ?? $pauseStart->diffInMinutes($pauseEnd);
                    
                    return [
                        'pause_start' => $pause->pause_start->format('Y-m-d H:i:s'),
                        'pause_end' => $pause->pause_end ? $pause->pause_end->format('Y-m-d H:i:s') : $checkOutTime->format('Y-m-d H:i:s'),
                        'pause_duration_minutes' => $pauseDuration,
                        'pause_duration_hours' => round($pauseDuration / 60, 2)
                    ];
                })
                ->toArray();
            
            // Update attendance_timeline
            $timeline = $attendance->attendance_timeline ?? [];
            
            if (!empty($timeline)) {
                // Get the last entry
                $lastIndex = count($timeline) - 1;
                $lastEntry = $timeline[$lastIndex];
                
                // If the last entry is incomplete (has clock_in but no clock_out), complete it
                if (is_array($lastEntry) && isset($lastEntry['clock_in']) && empty($lastEntry['clock_out'])) {
                    $timeline[$lastIndex] = [
                        'clock_in' => $lastEntry['clock_in'],
                        'clock_out' => $checkOutTime->format('Y-m-d H:i:s'),
                        'total_time' => round($sessionHours, 2), // Already excludes pause times
                        'pause_times' => $sessionPauses, // Add pause information for this session
                        'total_pause_minutes' => $totalPauseMinutes,
                        'total_pause_hours' => round($totalPauseMinutes / 60, 2)
                    ];
                } else {
                    // All entries are complete, add new one
                    $timeline[] = [
                        'clock_in' => $checkInTime->format('Y-m-d H:i:s'),
                        'clock_out' => $checkOutTime->format('Y-m-d H:i:s'),
                        'total_time' => round($sessionHours, 2), // Already excludes pause times
                        'pause_times' => $sessionPauses, // Add pause information for this session
                        'total_pause_minutes' => $totalPauseMinutes,
                        'total_pause_hours' => round($totalPauseMinutes / 60, 2)
                    ];
                }
            } else {
                // First session - add to timeline
                $timeline[] = [
                    'clock_in' => $checkInTime->format('Y-m-d H:i:s'),
                    'clock_out' => $checkOutTime->format('Y-m-d H:i:s'),
                    'total_time' => round($sessionHours, 2), // Already excludes pause times
                    'pause_times' => $sessionPauses, // Add pause information for this session
                    'total_pause_minutes' => $totalPauseMinutes,
                    'total_pause_hours' => round($totalPauseMinutes / 60, 2)
                ];
            }
            
            $attendance->attendance_timeline = $timeline;
            $attendance->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully.',
                'data' => [
                    'check_out_time' => $checkOutTime->format('Y-m-d H:i:s'),
                    'session_hours' => $sessionHours,
                    'total_hours' => $totalHours,
                    'previous_total_hours' => $previousTotalHours,
                    'total_work_minutes' => $totalWorkMinutes,
                    'total_pause_minutes' => $totalPauseMinutes,
                    'net_work_minutes' => $netWorkMinutes,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Check-out error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check out. Please try again.',
            ], 500);
        }
    }

    /**
     * Pause timer
     */
    public function pause(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // First try to find attendance for today
        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();

        // If not found for today, find the most recent open attendance (handles cross-day)
        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $user->id)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->orderBy('check_in', 'desc')
                ->first();
        }

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'You must check in first.',
            ], 400);
        }

        // Check if already paused
        $activePause = AttendancePause::where('attendance_id', $attendance->id)
            ->whereNull('pause_end')
            ->first();

        if ($activePause) {
            return response()->json([
                'success' => false,
                'message' => 'Timer is already paused.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $pause = new AttendancePause();
            $pause->attendance_id = $attendance->id;
            $pause->pause_start = Carbon::now();
            $pause->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Timer paused.',
                'data' => [
                    'pause_id' => $pause->id,
                    'pause_start' => $pause->pause_start->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Pause error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to pause timer. Please try again.',
            ], 500);
        }
    }

    /**
     * Resume timer
     */
    public function resume(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // First try to find attendance for today
        $attendance = Attendance::where('employee_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in')
            ->whereNull('check_out')
            ->first();

        // If not found for today, find the most recent open attendance (handles cross-day)
        if (!$attendance) {
            $attendance = Attendance::where('employee_id', $user->id)
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->orderBy('check_in', 'desc')
                ->first();
        }

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'You must check in first.',
            ], 400);
        }

        // Find active pause
        $activePause = AttendancePause::where('attendance_id', $attendance->id)
            ->whereNull('pause_end')
            ->first();

        if (!$activePause) {
            return response()->json([
                'success' => false,
                'message' => 'Timer is not paused.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $pauseEnd = Carbon::now();
            $pauseDuration = $activePause->pause_start->diffInMinutes($pauseEnd);
            
            $activePause->pause_end = $pauseEnd;
            $activePause->pause_duration_minutes = $pauseDuration;
            $activePause->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Timer resumed.',
                'data' => [
                    'pause_duration_minutes' => $pauseDuration,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Resume error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to resume timer. Please try again.',
            ], 500);
        }
    }

    /**
     * Attendance Report
     */
    public function report(Request $request)
    {
        if (!hasPermission('admin.attendance.report')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        // Start with base query - include soft deleted
        $attendanceQuery = Attendance::withTrashed()
            ->with(['employee' => function($query) {
                $query->withTrashed()->with('designation');
            }, 'pauses']);

        // Only get attendances for staff members (is_staff == 1) OR admin users (id == 1)
        $attendanceQuery->whereHas('employee', function ($query) {
            $query->withTrashed()->where(function($q) {
                $q->where('is_staff', 1)
                  ->orWhere('id', 1); // Include admin user
            });
        });

        // Date range filter - default to current date (today) if not provided
        if (!empty($request->date_range) && $request->date_range != 0 && $request->date_range != '0') {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start));
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end));
            
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        } else {
            // Default to current date (today) if no date range is provided
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        }

        // Search filter
        if (!empty($request->search)) {
            $search = $request->search;
            $attendanceQuery->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($query) use ($search) {
                    $query->withTrashed()->where(function($q2) {
                        $q2->where('is_staff', 1)->orWhere('id', 1);
                    })->where(function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->orWhere('ip_address', 'like', '%' . $search . '%')
                ->orWhere('device_browser', 'like', '%' . $search . '%')
                ->orWhere('overtime_task_description', 'like', '%' . $search . '%');
            });
        }

        // Employee filter (only staff members)
        if (!empty($request->employee_id) && $request->employee_id != 0) {
            $attendanceQuery->where('employee_id', $request->employee_id);
        }

        // Status filter
        if (!empty($request->status) && $request->status != 0) {
            $attendanceQuery->where('status', $request->status);
        }

        // Order by date descending
        $attendanceQuery->orderBy('date', 'desc')->orderBy('check_in', 'desc');

        // Debug: Log the query
        // Log::info('Attendance Report Query', [
        //     'sql' => $attendanceQuery->toSql(),
        //     'bindings' => $attendanceQuery->getBindings(),
        //     'date_range' => $request->date_range,
        //     'startDate' => isset($startDate) ? $startDate->format('Y-m-d') : null,
        //     'endDate' => isset($endDate) ? $endDate->format('Y-m-d') : null,
        // ]);

        $attendances = $attendanceQuery->get();
        
        // Debug: Log results
        // Log::info('Attendance Report Results', [
        //     'count' => $attendances->count(),
        //     'employee_ids' => $attendances->pluck('employee_id')->toArray(),
        // ]);
        
        // TEMPORARY DEBUG: Check all attendances without filters
        // $allAttendances = Attendance::withTrashed()->with('employee')->get();
        // Log::info('All Attendances (No Filters)', [
        //     'total_count' => $allAttendances->count(),
        //     'this_month_count' => $allAttendances->filter(function($a) {
        //         $start = Carbon::now()->firstOfMonth();
        //         $end = Carbon::today();
        //         return $a->date >= $start && $a->date <= $end;
        //     })->count(),
        //     'employee_ids' => $allAttendances->pluck('employee_id')->unique()->toArray(),
        //     'employee_is_staff' => $allAttendances->map(function($a) {
        //         return [
        //             'employee_id' => $a->employee_id,
        //             'is_staff' => $a->employee->is_staff ?? 'null',
        //             'name' => $a->employee->name ?? 'null'
        //         ];
        //     })->unique('employee_id')->values()->toArray(),
        // ]);

        // Calculate summary statistics
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        $totalRecords = $attendances->count();
        $totalHours = $attendances->sum('total_hours');
        $totalOvertimeHours = $attendances->filter(function ($attendance) use ($dailyWorkTime) {
            return $attendance->total_hours > $dailyWorkTime;
        })->sum(function ($attendance) use ($dailyWorkTime) {
            return $attendance->total_hours - $dailyWorkTime;
        });
        $statusCounts = $attendances->groupBy('status')->map->count();
        $employeeCounts = $attendances->groupBy('employee_id')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_hours' => $group->sum('total_hours'),
                'employee_name' => optional($group->first()->employee)->name ?? 'Unknown'
            ];
        });

        // Check if date range is a single day (fixed day)
        $isSingleDay = $startDate->format('Y-m-d') === $endDate->format('Y-m-d');
        
        // Find absent employees (active employees with no attendance in date range)
        // Only show absent employees when date range is a single day
        $absentEmployees = collect();
        if ($isSingleDay) {
            $employeeIdsWithAttendance = $attendances->pluck('employee_id')->unique()->toArray();
            
            // Get all active employees (is_staff == 1 or id == 1) with status = 'Active'
            $activeEmployees = User::where(function($q) {
                $q->where('is_staff', 1)->orWhere('id', 1);
            })
            ->where('status', 'Active')
            ->with('designation')
            ->get();
            
            // Filter out employees who have attendance records
            $absentEmployees = $activeEmployees->filter(function($employee) use ($employeeIdsWithAttendance) {
                return !in_array($employee->id, $employeeIdsWithAttendance);
            });
            
            // Apply employee filter if provided
            if (!empty($request->employee_id) && $request->employee_id != 0) {
                $absentEmployees = $absentEmployees->filter(function($employee) use ($request) {
                    return $employee->id == $request->employee_id;
                });
            }
            
            // Apply search filter if provided
            if (!empty($request->search)) {
                $search = $request->search;
                $absentEmployees = $absentEmployees->filter(function($employee) use ($search) {
                    return stripos($employee->name, $search) !== false || 
                           stripos($employee->email, $search) !== false;
                });
            }
        }

        // Calculate user summary (all users with attendance statistics)
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        
        // Get all users (staff and others) for summary
        $allUsers = User::where(function($q) {
            $q->where('is_staff', 1)->orWhere('id', 1);
        })
        ->with('designation')
        ->get();
        
        $userSummary = [];
        
        foreach ($allUsers as $user) {
            // Get all attendances for this user in the date range
            $userAttendances = Attendance::withTrashed()
                ->where('employee_id', $user->id)
                ->whereDate('date', '>=', $startDate->format('Y-m-d'))
                ->whereDate('date', '<=', $endDate->format('Y-m-d'))
                ->get();
            
            $totalPresentDays = $userAttendances->where('status', 'Present')->count();
            $totalAbsentDays = 0; // Will calculate based on working days
            
            // Calculate total completed work hours (only from checked-out attendances)
            $totalWorkHours = $userAttendances->sum('total_hours') ?? 0;
            
            // Calculate total running hours (for active clock-ins that haven't checked out yet)
            $totalRunningHours = $userAttendances->filter(function($att) {
                return empty($att->check_out) && !empty($att->check_in);
            })->sum('running_total_hour') ?? 0;
            
            // Total actual work hours = completed hours + running hours
            $totalWorkHoursWithRunning = $totalWorkHours + $totalRunningHours;
            
            // Calculate overtime hours (only from completed attendances where total_hours > daily work time)
            $totalOvertimeHours = $userAttendances->filter(function($att) use ($dailyWorkTime) {
                return ($att->total_hours ?? 0) > $dailyWorkTime;
            })->sum(function($att) use ($dailyWorkTime) {
                return ($att->total_hours ?? 0) - $dailyWorkTime;
            });
            
            // Calculate absent days (working days in range - present days)
            $workingDays = $startDate->copy();
            $totalWorkingDays = 0;
            while ($workingDays->lte($endDate)) {
                // Count weekdays (Monday to Friday) as working days
                if ($workingDays->dayOfWeek >= 1 && $workingDays->dayOfWeek <= 5) {
                    $totalWorkingDays++;
                }
                $workingDays->addDay();
            }
            $totalAbsentDays = max(0, $totalWorkingDays - $totalPresentDays);
            
            // Work Hours Gap Calculation (Present Days Only):
            // Expected Work Hours = Total Present Days × Daily Work Time (e.g., 25 days × 8 hours = 200 hours)
            // Actual Work Hours = Total Work Hours (completed + running)
            // Work Hours Gap (Present) = Expected - Actual (only positive values, no overtime)
            // - Positive gap = Deficit (worked less than expected, e.g., 200 - 197 = 3 hours deficit)
            // - Negative/Zero gap = 0 (worked more than or equal to expected, no gap)
            $expectedWorkHoursPresent = $totalPresentDays * $dailyWorkTime;
            $workHoursGapPresent = max(0, $expectedWorkHoursPresent - $totalWorkHoursWithRunning);
            
            // Work Hours Gap Calculation (Present + Absent Days):
            // Expected Work Hours = (Total Present Days + Total Absent Days) × Daily Work Time
            // Actual Work Hours = Total Work Hours (completed + running)
            // Work Hours Gap (Present + Absent) = Expected - Actual (only positive values, no overtime)
            // - Positive gap = Deficit (worked less than expected including absent days)
            // - Negative/Zero gap = 0 (worked more than or equal to expected, no gap)
            $expectedWorkHoursTotal = ($totalPresentDays + $totalAbsentDays) * $dailyWorkTime;
            $workHoursGapTotal = max(0, $expectedWorkHoursTotal - $totalWorkHoursWithRunning);
            
            $userSummary[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation?->name ?? 'N/A',
                'total_present_days' => $totalPresentDays,
                'total_absent_days' => $totalAbsentDays,
                'total_work_hours' => $totalWorkHoursWithRunning, // Includes running hours
                'total_overtime_hours' => $totalOvertimeHours,
                'work_hours_gap_present' => $workHoursGapPresent,
                'work_hours_gap_total' => $workHoursGapTotal,
            ];
        }
        
        // Sort by name
        usort($userSummary, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        // Calculate totals for footer
        $userSummaryTotals = [
            'total_present_days' => array_sum(array_column($userSummary, 'total_present_days')),
            'total_absent_days' => array_sum(array_column($userSummary, 'total_absent_days')),
            'total_work_hours' => array_sum(array_column($userSummary, 'total_work_hours')),
            'total_overtime_hours' => array_sum(array_column($userSummary, 'total_overtime_hours')),
            'work_hours_gap_present' => array_sum(array_column($userSummary, 'work_hours_gap_present')),
            'work_hours_gap_total' => array_sum(array_column($userSummary, 'work_hours_gap_total')),
        ];

        return view('admin.report.attendance', get_defined_vars());
    }
    
    /**
     * Show employee attendance details
     */
    public function employeeAttendanceDetails($employeeId, Request $request)
    {
        if (!hasPermission('admin.attendance.report')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        $employee = User::with('designation')->findOrFail($employeeId);
        
        // Date range filter - default to current month if not provided
        if (!empty($request->date_range) && $request->date_range != 0 && $request->date_range != '0') {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
        } else {
            // Default to current month
            $startDate = Carbon::now()->firstOfMonth()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        }
        
        // Get attendances for this employee
        $attendances = Attendance::withTrashed()
            ->where('employee_id', $employeeId)
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->with('pauses')
            ->orderBy('date', 'desc')
            ->orderBy('check_in', 'desc')
            ->get();
        
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        
        // Calculate summary statistics for this employee
        $totalPresentDays = $attendances->where('status', 'Present')->count();
        
        // Calculate total completed work hours (only from checked-out attendances)
        $totalWorkHours = $attendances->sum('total_hours') ?? 0;
        
        // Calculate total running hours (for active clock-ins that haven't checked out yet)
        $totalRunningHours = $attendances->filter(function($att) {
            return empty($att->check_out) && !empty($att->check_in);
        })->sum('running_total_hour') ?? 0;
        
        // Total actual work hours = completed hours + running hours
        $totalWorkHoursWithRunning = $totalWorkHours + $totalRunningHours;
        
        // Calculate total overtime hours (only from completed attendances where total_hours > daily work time)
        $totalOvertimeHours = $attendances->filter(function($att) use ($dailyWorkTime) {
            return ($att->total_hours ?? 0) > $dailyWorkTime;
        })->sum(function($att) use ($dailyWorkTime) {
            return ($att->total_hours ?? 0) - $dailyWorkTime;
        });
        
        // Calculate absent days (working days in range - present days)
        $workingDays = $startDate->copy();
        $totalWorkingDays = 0;
        while ($workingDays->lte($endDate)) {
            // Count weekdays (Monday to Friday) as working days
            if ($workingDays->dayOfWeek >= 1 && $workingDays->dayOfWeek <= 5) {
                $totalWorkingDays++;
            }
            $workingDays->addDay();
        }
        $totalAbsentDays = max(0, $totalWorkingDays - $totalPresentDays);
        
        // Work Hours Gap Calculation (Present Days Only):
        // Expected Work Hours = Total Present Days × Daily Work Time (e.g., 25 days × 8 hours = 200 hours)
        // Actual Work Hours = Total Work Hours (completed + running)
        // Work Hours Gap (Present) = Expected - Actual (only positive values, no overtime)
        // - Positive gap = Deficit (worked less than expected, e.g., 200 - 197 = 3 hours deficit)
        // - Negative/Zero gap = 0 (worked more than or equal to expected, no gap)
        $expectedWorkHoursPresent = $totalPresentDays * $dailyWorkTime;
        $workHoursGapPresent = max(0, $expectedWorkHoursPresent - $totalWorkHoursWithRunning);
        
        // Work Hours Gap Calculation (Present + Absent Days):
        // Expected Work Hours = (Total Present Days + Total Absent Days) × Daily Work Time
        // Actual Work Hours = Total Work Hours (completed + running)
        // Work Hours Gap (Present + Absent) = Expected - Actual (only positive values, no overtime)
        // - Positive gap = Deficit (worked less than expected including absent days)
        // - Negative/Zero gap = 0 (worked more than or equal to expected, no gap)
        $expectedWorkHoursTotal = ($totalPresentDays + $totalAbsentDays) * $dailyWorkTime;
        $workHoursGapTotal = max(0, $expectedWorkHoursTotal - $totalWorkHoursWithRunning);
        
        // Employee summary
        $employeeSummary = [
            'total_present_days' => $totalPresentDays,
            'total_absent_days' => $totalAbsentDays,
            'total_work_hours' => $totalWorkHoursWithRunning,
            'total_overtime_hours' => $totalOvertimeHours,
            'work_hours_gap_present' => $workHoursGapPresent,
            'work_hours_gap_total' => $workHoursGapTotal,
        ];
        
        return view('admin.report.employeeAttendanceDetails', get_defined_vars());
    }
    
    /**
     * Get attendance details for modal (AJAX)
     */
    public function getAttendanceDetails(Request $request)
    {
        $attendanceId = $request->attendance_id;
        $attendance = Attendance::withTrashed()
            ->with(['employee.designation', 'pauses'])
            ->findOrFail($attendanceId);
        
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        $timeline = $attendance->attendance_timeline ?? [];
        $pauses = $attendance->pauses ?? collect();
        
        $html = view('admin.report._partials.attendanceDetailsModal', compact('attendance', 'timeline', 'pauses', 'dailyWorkTime'))->render();
        
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Staff attendance report (for is_staff == 1)
     */
    public function staffAttendanceReport(Request $request)
    {
        $user = Auth::user();
        
        if ($user->is_staff != 1) {
            abort(403, 'Unauthorized action.');
        }
        
        // Start with base query - only current user's attendance
        $attendanceQuery = Attendance::withTrashed()
            ->where('employee_id', $user->id)
            ->with(['employee' => function($query) {
                $query->withTrashed()->with('designation');
            }, 'pauses']);

        // Date range filter - default to current month if not provided
        if (!empty($request->date_range) && $request->date_range != 0 && $request->date_range != '0') {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start));
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end));
            
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        } else {
            // Default to current month if no date range is provided
            $startDate = Carbon::now()->firstOfMonth();
            $endDate = Carbon::today();
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        }

        // Status filter
        if (!empty($request->status) && $request->status != 0) {
            $attendanceQuery->where('status', $request->status);
        }

        // Order by date descending
        $attendanceQuery->orderBy('date', 'desc')->orderBy('check_in', 'desc');

        $attendances = $attendanceQuery->get();
        
        // Calculate summary for current user
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        
        $totalPresentDays = $attendances->where('status', 'Present')->count();
        $totalAbsentDays = $attendances->where('status', 'Absent')->count();
        $totalWorkHours = 0;
        $totalOvertimeHours = 0;
        $totalRunningHours = 0;
        
        foreach ($attendances as $attendance) {
            if ($attendance->status == 'Present') {
                $dutyTime = $attendance->duty_time ?? 0;
                $runningTime = $attendance->running_total_hour ?? 0;
                $overtime = $attendance->overtime_hours ?? 0;
                
                $totalWorkHours += $dutyTime;
                $totalRunningHours += $runningTime;
                $totalOvertimeHours += $overtime;
            }
        }
        
        // Calculate work hours gap
        $expectedWorkHours = $totalPresentDays * $dailyWorkTime;
        $workHoursGapPresent = max(0, $expectedWorkHours - ($totalWorkHours + $totalRunningHours));
        $workHoursGapTotal = max(0, (($totalPresentDays + $totalAbsentDays) * $dailyWorkTime) - ($totalWorkHours + $totalRunningHours));
        
        $employeeSummary = [
            'user_id' => $user->id,
            'name' => $user->name,
            'designation' => $user->designation?->name ?? 'N/A',
            'total_present_days' => $totalPresentDays,
            'total_absent_days' => $totalAbsentDays,
            'total_work_hours' => $totalWorkHours + $totalRunningHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'work_hours_gap_present' => $workHoursGapPresent,
            'work_hours_gap_total' => $workHoursGapTotal,
        ];
        
        // Get default date range for filter
        $defaultDateRange = $request->date_range ?? getDateRange(0, 'Current Month');
        
        // Get active employees for filter (only current user)
        $activeEmployees = collect([$user]);
        
        return view('common.staff.attendance.report', get_defined_vars());
    }

    /**
     * Export attendance report as PDF (Admin)
     */
    public function exportPdf(Request $request, \App\Services\PdfService $pdfService)
    {
        if (!hasPermission('admin.attendance.report')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        
        // Reuse the same logic from report() method
        $attendanceQuery = Attendance::withTrashed()
            ->with(['employee' => function($query) {
                $query->withTrashed()->with('designation');
            }, 'pauses']);

        $attendanceQuery->whereHas('employee', function ($query) {
            $query->withTrashed()->where(function($q) {
                $q->where('is_staff', 1)->orWhere('id', 1);
            });
        });

        if (!empty($request->date_range) && $request->date_range != 0 && $request->date_range != '0') {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start));
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end));
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        } else {
            $startDate = Carbon::today();
            $endDate = Carbon::today();
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        }

        if (!empty($request->search)) {
            $search = $request->search;
            $attendanceQuery->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($query) use ($search) {
                    $query->withTrashed()->where(function($q2) {
                        $q2->where('is_staff', 1)->orWhere('id', 1);
                    })->where(function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->orWhere('ip_address', 'like', '%' . $search . '%')
                ->orWhere('device_browser', 'like', '%' . $search . '%')
                ->orWhere('overtime_task_description', 'like', '%' . $search . '%');
            });
        }

        if (!empty($request->employee_id) && $request->employee_id != 0) {
            $attendanceQuery->where('employee_id', $request->employee_id);
        }

        if (!empty($request->status) && $request->status != 0) {
            $attendanceQuery->where('status', $request->status);
        }

        $attendanceQuery->orderBy('date', 'desc')->orderBy('check_in', 'desc');
        $attendances = $attendanceQuery->get();
        
        // Calculate summary statistics (same as report method)
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        $totalRecords = $attendances->count();
        $totalHours = $attendances->sum('total_hours') ?? 0;
        $totalOvertimeHours = $attendances->filter(function ($attendance) use ($dailyWorkTime) {
            return ($attendance->total_hours ?? 0) > $dailyWorkTime;
        })->sum(function ($attendance) use ($dailyWorkTime) {
            return ($attendance->total_hours ?? 0) - $dailyWorkTime;
        });
        
        $statusCounts = $attendances->groupBy('status')->map->count();
        
        // Get all users for summary
        $allUsers = User::where(function($q) {
            $q->where('is_staff', 1)->orWhere('id', 1);
        })->with('designation')->get();
        
        $userSummary = [];
        foreach ($allUsers as $user) {
            $userAttendances = Attendance::withTrashed()
                ->where('employee_id', $user->id)
                ->whereDate('date', '>=', $startDate->format('Y-m-d'))
                ->whereDate('date', '<=', $endDate->format('Y-m-d'))
                ->get();
            
            $totalPresentDays = $userAttendances->where('status', 'Present')->count();
            $totalWorkHours = $userAttendances->sum('total_hours') ?? 0;
            $totalRunningHours = $userAttendances->filter(function($att) {
                return empty($att->check_out) && !empty($att->check_in);
            })->sum('running_total_hour') ?? 0;
            $totalWorkHoursWithRunning = $totalWorkHours + $totalRunningHours;
            $userOvertimeHours = $userAttendances->filter(function($att) use ($dailyWorkTime) {
                return ($att->total_hours ?? 0) > $dailyWorkTime;
            })->sum(function($att) use ($dailyWorkTime) {
                return ($att->total_hours ?? 0) - $dailyWorkTime;
            });
            
            $workingDays = $startDate->copy();
            $totalWorkingDays = 0;
            while ($workingDays->lte($endDate)) {
                if ($workingDays->dayOfWeek >= 1 && $workingDays->dayOfWeek <= 5) {
                    $totalWorkingDays++;
                }
                $workingDays->addDay();
            }
            $totalAbsentDays = max(0, $totalWorkingDays - $totalPresentDays);
            $expectedWorkHoursPresent = $totalPresentDays * $dailyWorkTime;
            $workHoursGapPresent = max(0, $expectedWorkHoursPresent - $totalWorkHoursWithRunning);
            $expectedWorkHoursTotal = ($totalPresentDays + $totalAbsentDays) * $dailyWorkTime;
            $workHoursGapTotal = max(0, $expectedWorkHoursTotal - $totalWorkHoursWithRunning);
            
            $userSummary[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation?->name ?? 'N/A',
                'total_present_days' => $totalPresentDays,
                'total_absent_days' => $totalAbsentDays,
                'total_work_hours' => $totalWorkHoursWithRunning,
                'total_overtime_hours' => $userOvertimeHours,
                'work_hours_gap_present' => $workHoursGapPresent,
                'work_hours_gap_total' => $workHoursGapTotal,
            ];
        }
        
        usort($userSummary, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        $userSummaryTotals = [
            'total_present_days' => array_sum(array_column($userSummary, 'total_present_days')),
            'total_absent_days' => array_sum(array_column($userSummary, 'total_absent_days')),
            'total_work_hours' => array_sum(array_column($userSummary, 'total_work_hours')),
            'total_overtime_hours' => array_sum(array_column($userSummary, 'total_overtime_hours')),
            'work_hours_gap_present' => array_sum(array_column($userSummary, 'work_hours_gap_present')),
            'work_hours_gap_total' => array_sum(array_column($userSummary, 'work_hours_gap_total')),
        ];
        
        $getCurrentTranslation = getCurrentTranslation();
        $dateRangeStr = $request->date_range ?? ($startDate->format('Y/m/d') . '-' . $endDate->format('Y/m/d'));
        
        $html = view('admin.report.attendance-pdf', compact('attendances', 'totalRecords', 'totalHours', 'totalOvertimeHours', 'statusCounts', 'userSummary', 'userSummaryTotals', 'startDate', 'endDate', 'dateRangeStr', 'getCurrentTranslation', 'dailyWorkTime'))->render();
        
        $filename = 'Attendance_Report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';
        
        return $pdfService->generatePdf(null, $html, $filename, 'I');
    }

    /**
     * Export staff attendance report as PDF
     */
    public function staffExportPdf(Request $request, \App\Services\PdfService $pdfService)
    {
        $user = Auth::user();
        
        if ($user->is_staff != 1) {
            abort(403, 'Unauthorized action.');
        }
        
        $attendanceQuery = Attendance::withTrashed()
            ->where('employee_id', $user->id)
            ->with(['employee' => function($query) {
                $query->withTrashed()->with('designation');
            }, 'pauses']);

        if (!empty($request->date_range) && $request->date_range != 0 && $request->date_range != '0') {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start));
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end));
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        } else {
            $startDate = Carbon::now()->firstOfMonth();
            $endDate = Carbon::today();
            $attendanceQuery->whereDate('date', '>=', $startDate->format('Y-m-d'))
                           ->whereDate('date', '<=', $endDate->format('Y-m-d'));
        }

        if (!empty($request->status) && $request->status != 0) {
            $attendanceQuery->where('status', $request->status);
        }

        $attendanceQuery->orderBy('date', 'desc')->orderBy('check_in', 'desc');
        $attendances = $attendanceQuery->get();
        
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        $totalPresentDays = $attendances->where('status', 'Present')->count();
        $totalAbsentDays = $attendances->where('status', 'Absent')->count();
        $totalWorkHours = 0;
        $totalOvertimeHours = 0;
        $totalRunningHours = 0;
        
        foreach ($attendances as $attendance) {
            if ($attendance->status == 'Present') {
                $dutyTime = $attendance->duty_time ?? 0;
                $runningTime = $attendance->running_total_hour ?? 0;
                $overtime = $attendance->overtime_hours ?? 0;
                
                $totalWorkHours += $dutyTime;
                $totalRunningHours += $runningTime;
                $totalOvertimeHours += $overtime;
            }
        }
        
        $expectedWorkHours = $totalPresentDays * $dailyWorkTime;
        $workHoursGapPresent = max(0, $expectedWorkHours - ($totalWorkHours + $totalRunningHours));
        $workHoursGapTotal = max(0, (($totalPresentDays + $totalAbsentDays) * $dailyWorkTime) - ($totalWorkHours + $totalRunningHours));
        
        $employeeSummary = [
            'user_id' => $user->id,
            'name' => $user->name,
            'designation' => $user->designation?->name ?? 'N/A',
            'total_present_days' => $totalPresentDays,
            'total_absent_days' => $totalAbsentDays,
            'total_work_hours' => $totalWorkHours + $totalRunningHours,
            'total_overtime_hours' => $totalOvertimeHours,
            'work_hours_gap_present' => $workHoursGapPresent,
            'work_hours_gap_total' => $workHoursGapTotal,
        ];
        
        $getCurrentTranslation = getCurrentTranslation();
        $dateRangeStr = $request->date_range ?? ($startDate->format('Y/m/d') . '-' . $endDate->format('Y/m/d'));
        
        $html = view('common.staff.attendance.report-pdf', compact('attendances', 'employeeSummary', 'startDate', 'endDate', 'dateRangeStr', 'getCurrentTranslation', 'dailyWorkTime'))->render();
        
        $filename = 'My_Attendance_Report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';
        
        return $pdfService->generatePdf(null, $html, $filename, 'I');
    }

    /**
     * Export employee attendance details as PDF
     */
    public function employeeAttendanceDetailsExportPdf($employeeId, Request $request, \App\Services\PdfService $pdfService)
    {
        if (!hasPermission('admin.attendance.report')) {
            abort(403, 'Unauthorized action.');
        }
        $user = Auth::user();
        $employee = User::with('designation')->findOrFail($employeeId);
        
        // Date range filter - default to current month if not provided
        if (!empty($request->date_range) && $request->date_range != 0 && $request->date_range != '0') {
            $dateRange = $request->date_range;
            [$start, $end] = explode('-', $dateRange);
            $startDate = Carbon::createFromFormat('Y/m/d', trim($start))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($end))->endOfDay();
        } else {
            // Default to current month
            $startDate = Carbon::now()->firstOfMonth()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        }
        
        // Get attendances for this employee
        $attendances = Attendance::withTrashed()
            ->where('employee_id', $employeeId)
            ->whereDate('date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('date', '<=', $endDate->format('Y-m-d'))
            ->with('pauses')
            ->orderBy('date', 'desc')
            ->orderBy('check_in', 'desc')
            ->get();
        
        $dailyWorkTime = (float) env('DAILY_WORK_TIME', 8);
        
        // Calculate summary statistics for this employee
        $totalPresentDays = $attendances->where('status', 'Present')->count();
        $totalWorkHours = $attendances->sum('total_hours') ?? 0;
        $totalRunningHours = $attendances->filter(function($att) {
            return empty($att->check_out) && !empty($att->check_in);
        })->sum('running_total_hour') ?? 0;
        $totalWorkHoursWithRunning = $totalWorkHours + $totalRunningHours;
        $totalOvertimeHours = $attendances->filter(function($att) use ($dailyWorkTime) {
            return ($att->total_hours ?? 0) > $dailyWorkTime;
        })->sum(function($att) use ($dailyWorkTime) {
            return ($att->total_hours ?? 0) - $dailyWorkTime;
        });
        
        // Calculate absent days
        $workingDays = $startDate->copy();
        $totalWorkingDays = 0;
        while ($workingDays->lte($endDate)) {
            if ($workingDays->dayOfWeek >= 1 && $workingDays->dayOfWeek <= 5) {
                $totalWorkingDays++;
            }
            $workingDays->addDay();
        }
        $totalAbsentDays = max(0, $totalWorkingDays - $totalPresentDays);
        
        $expectedWorkHoursPresent = $totalPresentDays * $dailyWorkTime;
        $workHoursGapPresent = max(0, $expectedWorkHoursPresent - $totalWorkHoursWithRunning);
        $expectedWorkHoursTotal = ($totalPresentDays + $totalAbsentDays) * $dailyWorkTime;
        $workHoursGapTotal = max(0, $expectedWorkHoursTotal - $totalWorkHoursWithRunning);
        
        $employeeSummary = [
            'total_present_days' => $totalPresentDays,
            'total_absent_days' => $totalAbsentDays,
            'total_work_hours' => $totalWorkHoursWithRunning,
            'total_overtime_hours' => $totalOvertimeHours,
            'work_hours_gap_present' => $workHoursGapPresent,
            'work_hours_gap_total' => $workHoursGapTotal,
        ];
        
        $getCurrentTranslation = getCurrentTranslation();
        $dateRangeStr = $request->date_range ?? ($startDate->format('Y/m/d') . '-' . $endDate->format('Y/m/d'));
        
        $html = view('admin.report.employee-attendance-details-pdf', compact('attendances', 'employee', 'employeeSummary', 'startDate', 'endDate', 'dateRangeStr', 'getCurrentTranslation', 'dailyWorkTime'))->render();
        
        $filename = 'Employee_Attendance_' . str_replace(' ', '_', $employee->name) . '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.pdf';
        
        return $pdfService->generatePdf(null, $html, $filename, 'I');
    }
}
