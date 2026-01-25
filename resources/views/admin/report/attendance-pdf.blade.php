<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['attendance_report'] ?? 'Attendance Report' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header-logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .header-content {
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
        }
        .summary-section {
            margin-bottom: 20px;
        }
        .summary-cards {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .summary-card {
            width: 16.66%;
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: top;
        }
        .summary-card-header {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 5px;
            margin-bottom: 5px;
            font-size: 10px;
        }
        .summary-card-value {
            font-size: 14px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            display: inline-block;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            @php
                $globalData = Auth::user();
            @endphp
            @if(env('UNDER_DEVELOPMENT') == true)
                <strong style="font-size: 16px;">{{ $globalData->company_data->company_name ?? 'N/A' }}</strong>
            @else
                @if(!empty($globalData->company_data->dark_logo_url))
                    <img alt="{{ $globalData->company_data->company_name ?? 'N/A' }}" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" style="height: 30px; max-width: 200px;" />
                @endif
            @endif
        </div>
        <div class="header-content">
            <h1>{{ $getCurrentTranslation['attendance_report'] ?? 'Attendance Report' }}</h1>
            <p><strong>{{ $getCurrentTranslation['date_range'] ?? 'Date Range' }}:</strong> {{ $dateRangeStr }}</p>
            <p><strong>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-section">
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_found_records'] ?? 'Total Records' }}</div>
                    <div class="summary-card-value">{{ $totalRecords }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_hours'] ?? 'Total Hours' }}</div>
                    <div class="summary-card-value">{{ formatHoursMinutes($totalHours) }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_overtime_hours'] ?? 'Total Overtime' }}</div>
                    <div class="summary-card-value">{{ formatHoursMinutes($totalOvertimeHours) }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['running_clock_ins'] ?? 'Running Clock-Ins' }}</div>
                    <div class="summary-card-value">
                        @php
                            $runningCount = $attendances->filter(function($a) {
                                return empty($a->check_out) && !empty($a->check_in);
                            })->count();
                        @endphp
                        {{ $runningCount }}
                    </div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_running_hours'] ?? 'Running Hours' }}</div>
                    <div class="summary-card-value">
                        @php
                            $totalRunningHours = $attendances->filter(function($a) {
                                return empty($a->check_out) && !empty($a->check_in);
                            })->sum('running_total_hour');
                        @endphp
                        {{ formatHoursMinutes($totalRunningHours) }}
                    </div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['average_hours_per_day'] ?? 'Avg Hours/Day' }}</div>
                    <div class="summary-card-value">
                        {{ $totalRecords > 0 ? formatHoursMinutes($totalHours / $totalRecords) : '0h 0m' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Status Summary --}}
    @if($statusCounts && $statusCounts->count() > 0)
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['summary_by_status'] ?? 'Summary by Status' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ $getCurrentTranslation['status'] ?? 'Status' }}</th>
                    <th>{{ $getCurrentTranslation['total_count'] ?? 'Total Count' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statusCounts as $status => $count)
                <tr>
                    <td>{{ $status }}</td>
                    <td class="text-center">{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Employee Summary --}}
    @if(isset($userSummary) && count($userSummary) > 0)
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['summary_by_employee'] ?? 'Summary by Employee' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ $getCurrentTranslation['employee'] ?? 'Employee' }}</th>
                    <th>{{ $getCurrentTranslation['designation'] ?? 'Designation' }}</th>
                    <th class="text-center">{{ $getCurrentTranslation['total_present_days'] ?? 'Present Days' }}</th>
                    <th class="text-center">{{ $getCurrentTranslation['total_absent_days'] ?? 'Absent Days' }}</th>
                    <th class="text-center">{{ $getCurrentTranslation['total_work_hours'] ?? 'Work Hours' }}</th>
                    <th class="text-center">{{ $getCurrentTranslation['total_overtime_hours'] ?? 'Overtime' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($userSummary as $summary)
                <tr>
                    <td>{{ $summary['name'] }}</td>
                    <td>{{ $summary['designation'] }}</td>
                    <td class="text-center">{{ $summary['total_present_days'] }}</td>
                    <td class="text-center">{{ $summary['total_absent_days'] }}</td>
                    <td class="text-center">{{ formatHoursMinutes($summary['total_work_hours']) }}</td>
                    <td class="text-center">{{ formatHoursMinutes($summary['total_overtime_hours']) }}</td>
                </tr>
                @endforeach
            </tbody>
            @if(isset($userSummaryTotals))
            <tfoot>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="2" class="text-right">{{ $getCurrentTranslation['total'] ?? 'Total' }}:</td>
                    <td class="text-center">{{ $userSummaryTotals['total_present_days'] }}</td>
                    <td class="text-center">{{ $userSummaryTotals['total_absent_days'] }}</td>
                    <td class="text-center">{{ formatHoursMinutes($userSummaryTotals['total_work_hours']) }}</td>
                    <td class="text-center">{{ formatHoursMinutes($userSummaryTotals['total_overtime_hours']) }}</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    @endif

    {{-- Detailed Attendance Table --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['attendance_details'] ?? 'Attendance Details' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $getCurrentTranslation['employee'] ?? 'Employee' }}</th>
                    <th>{{ $getCurrentTranslation['date'] ?? 'Date' }}</th>
                    <th>{{ $getCurrentTranslation['check_in'] ?? 'Check In' }}</th>
                    <th>{{ $getCurrentTranslation['check_out'] ?? 'Check Out' }}</th>
                    <th>{{ $getCurrentTranslation['total_hours'] ?? 'Total Hours' }}</th>
                    <th>{{ $getCurrentTranslation['status'] ?? 'Status' }}</th>
                    <th>{{ $getCurrentTranslation['overtime'] ?? 'Overtime' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $index => $attendance)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $attendance->employee->name ?? 'N/A' }}</td>
                    <td>{{ $attendance->date ? $attendance->date->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $attendance->check_in ? $attendance->check_in->format('H:i:s') : '-' }}</td>
                    <td>{{ $attendance->check_out ? $attendance->check_out->format('H:i:s') : '-' }}</td>
                    <td class="text-center">
                        @if($attendance->check_out)
                            {{ formatHoursMinutes($attendance->total_hours ?? 0) }}
                        @elseif(empty($attendance->check_out) && !empty($attendance->check_in))
                            {{ formatHoursMinutes($attendance->running_total_hour ?? 0) }} ({{ $getCurrentTranslation['running'] ?? 'Running' }})
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge 
                            @if($attendance->status == 'Present') badge-success
                            @elseif($attendance->status == 'Late') badge-warning
                            @elseif($attendance->status == 'Absent') badge-danger
                            @else badge-info
                            @endif">
                            {{ $attendance->status }}
                        </span>
                    </td>
                    <td class="text-center">
                        @php
                            $isOvertime = ($attendance->total_hours ?? 0) > $dailyWorkTime;
                            $overtimeHours = $isOvertime ? ($attendance->total_hours ?? 0) - $dailyWorkTime : 0;
                        @endphp
                        @if($isOvertime)
                            <span class="badge badge-warning">{{ formatHoursMinutes($overtimeHours) }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center">{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>{{ $getCurrentTranslation['report_generated_by'] ?? 'Report Generated By' }}: {{ Auth::user()->name ?? 'System' }}</p>
        <p>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
