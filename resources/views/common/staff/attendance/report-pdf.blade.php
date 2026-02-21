<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['attendance_report'] ?? 'My Attendance Report' }}</title>
    <style>
        body {
            font-family: arial, sans-serif;
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
            width: 20%;
            padding: 5px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: top;
        }
        .summary-card-header {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 3px;
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
            padding: 5px;
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
                <strong style="font-size: 16px; font-family: {{ language_font(strip_tags($globalData->company_data->company_name ?? 'N/A')) }};">{{ $globalData->company_data->company_name ?? 'N/A' }}</strong>
            @else
                @if(!empty($globalData->company_data->dark_logo_url))
                    <img alt="{{ $globalData->company_data->company_name ?? 'N/A' }}" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" style="height: 30px; max-width: 200px;" />
                @endif
            @endif
            @php
                $email = $globalData->company_data->email_1 ?? '';
                $phone = $globalData->company_data->phone_1 ?? '';
                $address = $globalData->company_data->address ?? '';
            @endphp
            @if($address || $email || $phone)
            <div style="margin-top: 0px; font-size: 10px; text-align: center;">
                @if($address)
                <div style="font-family: {{ language_font(strip_tags($address)) }}; margin-bottom: 0px;">
                    {!! $address !!}
                </div>
                @endif
                @if($email || $phone)
                <div>
                    @if($email)
                    <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email'] ?? 'Email')) }};">{{ $getCurrentTranslation['email'] ?? 'Email' }}:</span> <span style="font-family: arial;">{{ $email }}</span>
                    @endif
                    @if($email && $phone) <span style="margin: 0 5px;">|</span> @endif
                    @if($phone)
                    <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone'] ?? 'Phone')) }};">{{ $getCurrentTranslation['phone'] ?? 'Phone' }}:</span> <span style="font-family: arial;">{{ $phone }}</span>
                    @endif
                </div>
                @endif
            </div>
            @endif
        </div>
        <div class="header-content">
            <h1 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['attendance_report'] ?? 'My Attendance Report')) }};">{{ $getCurrentTranslation['attendance_report'] ?? 'My Attendance Report' }}</h1>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee'] ?? 'Employee')) }};">{{ $getCurrentTranslation['employee'] ?? 'Employee' }}:</strong> <span style="font-family: {{ language_font(strip_tags($employeeSummary['name'] ?? 'N/A')) }};">{{ $employeeSummary['name'] ?? 'N/A' }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['designation'] ?? 'Designation')) }};">{{ $getCurrentTranslation['designation'] ?? 'Designation' }}:</strong> <span style="font-family: {{ language_font(strip_tags($employeeSummary['designation'] ?? 'N/A')) }};">{{ $employeeSummary['designation'] ?? 'N/A' }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['date_range'] ?? 'Date Range')) }};">{{ $getCurrentTranslation['date_range'] ?? 'Date Range' }}:</strong> <span style="font-family: {{ language_font(strip_tags($dateRangeStr)) }};">{{ $dateRangeStr }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-section">
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_present_days'] ?? 'Present Days')) }};">{{ $getCurrentTranslation['total_present_days'] ?? 'Present Days' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ $employeeSummary['total_present_days'] ?? 0 }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_absent_days'] ?? 'Absent Days')) }};">{{ $getCurrentTranslation['total_absent_days'] ?? 'Absent Days' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ $employeeSummary['total_absent_days'] ?? 0 }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_work_hours'] ?? 'Work Hours')) }};">{{ $getCurrentTranslation['total_work_hours'] ?? 'Work Hours' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ formatHoursMinutes($employeeSummary['total_work_hours'] ?? 0) }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_overtime_hours'] ?? 'Overtime')) }};">{{ $getCurrentTranslation['total_overtime_hours'] ?? 'Overtime' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ formatHoursMinutes($employeeSummary['total_overtime_hours'] ?? 0) }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['work_hours_gap_present'] ?? 'Work Hours Gap')) }};">{{ $getCurrentTranslation['work_hours_gap_present'] ?? 'Work Hours Gap' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ formatHoursMinutes($employeeSummary['work_hours_gap_present'] ?? 0) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Detailed Attendance Table --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; font-family: {{ language_font(strip_tags($getCurrentTranslation['attendance_details'] ?? 'Attendance Details')) }};">{{ $getCurrentTranslation['attendance_details'] ?? 'Attendance Details' }}</h3>
        <table>
            <thead>
                <tr>
                    <th style="font-family: arial;">#</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['date'] ?? 'Date')) }};">{{ $getCurrentTranslation['date'] ?? 'Date' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['clock_status'] ?? 'Clock Status')) }};">{{ $getCurrentTranslation['clock_status'] ?? 'Clock Status' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['check_in'] ?? 'Check In')) }};">{{ $getCurrentTranslation['check_in'] ?? 'Check In' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['check_out'] ?? 'Check Out')) }};">{{ $getCurrentTranslation['check_out'] ?? 'Check Out' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['running_total_hours'] ?? 'Running Hours')) }};">{{ $getCurrentTranslation['running_total_hours'] ?? 'Running Hours' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_hours'] ?? 'Total Hours')) }};">{{ $getCurrentTranslation['total_hours'] ?? 'Total Hours' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['status'] ?? 'Status')) }};">{{ $getCurrentTranslation['status'] ?? 'Status' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['overtime'] ?? 'Overtime')) }};">{{ $getCurrentTranslation['overtime'] ?? 'Overtime' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['location'] ?? 'Location')) }};">{{ $getCurrentTranslation['location'] ?? 'Location' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $index => $attendance)
                @php
                    $isRunning = empty($attendance->check_out) && !empty($attendance->check_in);
                    $runningTotalHours = $attendance->running_total_hour ?? 0;
                    $isOvertime = ($attendance->total_hours ?? 0) > $dailyWorkTime;
                    $overtimeHours = $isOvertime ? ($attendance->total_hours ?? 0) - $dailyWorkTime : 0;
                @endphp
                <tr>
                    <td class="text-center" style="font-family: arial;">{{ $index + 1 }}</td>
                    <td style="font-family: arial;">{{ $attendance->date ? $attendance->date->format('Y-m-d') : 'N/A' }}</td>
                    <td class="text-center">
                        @if($isRunning)
                            <span class="badge badge-success" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['running'] ?? 'Running')) }};">{{ $getCurrentTranslation['running'] ?? 'Running' }}</span>
                        @else
                            <span class="badge badge-secondary" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['completed'] ?? 'Completed')) }};">{{ $getCurrentTranslation['completed'] ?? 'Completed' }}</span>
                        @endif
                    </td>
                    <td class="text-center" style="font-family: arial;">
                        @if($attendance->check_in)
                            {{ $attendance->check_in->format('H:i:s') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center" style="font-family: arial;">
                        @if($attendance->check_out)
                            {{ $attendance->check_out->format('H:i:s') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center" style="font-family: arial;">
                        @if($isRunning)
                            {{ formatHoursMinutes($runningTotalHours) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center" style="font-family: arial;">
                        @if($attendance->check_out)
                            {{ formatHoursMinutes($attendance->total_hours ?? 0) }}
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
                            @endif" style="font-family: {{ language_font(strip_tags($attendance->status)) }};">
                            {{ $attendance->status }}
                        </span>
                    </td>
                    <td class="text-center" style="font-family: arial;">
                        @if($isOvertime)
                            <span class="badge badge-warning">{{ formatHoursMinutes($overtimeHours) }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['no_data_found'] ?? 'No data found')) }};">{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p><span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['report_generated_by'] ?? 'Report Generated By')) }};">{{ $getCurrentTranslation['report_generated_by'] ?? 'Report Generated By' }}:</span> <span style="font-family: {{ language_font(strip_tags(Auth::user()->name ?? 'System')) }};">{{ Auth::user()->name ?? 'System' }}</span></p>
        <p><span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</span> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
    </div>
</body>
</html>
