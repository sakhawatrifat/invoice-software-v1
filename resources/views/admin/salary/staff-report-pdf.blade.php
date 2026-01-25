<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['salary_report'] ?? 'Salary Report' }}</title>
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
            <h1>{{ $getCurrentTranslation['salary_report'] ?? 'Salary Report' }}</h1>
            <p><strong>{{ $getCurrentTranslation['employee'] ?? 'Employee' }}:</strong> {{ $employee->name ?? 'N/A' }}</p>
            <p><strong>{{ $getCurrentTranslation['designation'] ?? 'Designation' }}:</strong> {{ $employee->designation->name ?? 'N/A' }}</p>
            <p><strong>{{ $getCurrentTranslation['email'] ?? 'Email' }}:</strong> {{ $employee->email ?? 'N/A' }}</p>
            <p><strong>{{ $getCurrentTranslation['year'] ?? 'Year' }}:</strong> {{ $yearStr }}</p>
            <p><strong>{{ $getCurrentTranslation['month'] ?? 'Month' }}:</strong> {{ $monthStr }}</p>
            <p><strong>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-section">
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_base_salary'] ?? 'Total Base Salary' }}</div>
                    <div class="summary-card-value">{{ number_format($totalBaseSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_deductions'] ?? 'Total Deductions' }}</div>
                    <div class="summary-card-value" style="color: #dc3545;">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_bonus'] ?? 'Total Bonus' }}</div>
                    <div class="summary-card-value" style="color: #28a745;">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_net_salary'] ?? 'Total Net Salary' }}</div>
                    <div class="summary-card-value">{{ number_format($totalNetSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}</div>
                    <div class="summary-card-value" style="color: #28a745;">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid' }}</div>
                    <div class="summary-card-value" style="color: #dc3545;">{{ number_format($totalUnpaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Salary Table --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['salary_details'] ?? 'Salary Details' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $getCurrentTranslation['month'] ?? 'Month' }}</th>
                    <th>{{ $getCurrentTranslation['year'] ?? 'Year' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</th>
                    <th>{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
                    <th>{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $index => $salary)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $monthNames[$salary->month] ?? $salary->month }}</td>
                    <td class="text-center">{{ $salary->year }}</td>
                    <td class="text-right">{{ number_format($salary->base_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="color: #dc3545;">{{ number_format($salary->deductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="color: #28a745;">{{ number_format($salary->bonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right">{{ number_format($salary->net_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-center">
                        <span class="badge 
                            @if($salary->payment_status == 'Paid') badge-success
                            @elseif($salary->payment_status == 'Partial') badge-warning
                            @else badge-danger
                            @endif">
                            {{ $salary->payment_status }}
                        </span>
                    </td>
                    <td class="text-center">
                        @if($salary->payment_date)
                            {{ \Carbon\Carbon::parse($salary->payment_date)->format('Y-m-d') }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}</td>
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
