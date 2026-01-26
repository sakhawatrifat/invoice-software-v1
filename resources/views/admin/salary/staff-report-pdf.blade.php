<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['salary_report'] ?? 'Salary Report' }}</title>
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
        .payslip-section {
            margin-bottom: 15px;
        }
        .payslip-section-title {
            background-color: #f0f0f0;
            padding: 3px;
            font-weight: bold;
            font-size: 13px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 3px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        .info-table td:first-child {
            background-color: #f9f9f9;
            font-weight: bold;
            width: 30%;
        }
        .summary-cards {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .summary-card {
            width: 16.66%;
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
            <h1 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['salary_report'] ?? 'Salary Report')) }};">{{ $getCurrentTranslation['salary_report'] ?? 'Salary Report' }}</h1>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['month'] ?? 'Month')) }};">{{ $getCurrentTranslation['month'] ?? 'Month' }}:</strong> <span style="font-family: {{ language_font(strip_tags($monthStr)) }};">{{ $monthStr }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['year'] ?? 'Year')) }};">{{ $getCurrentTranslation['year'] ?? 'Year' }}:</strong> <span style="font-family: arial;">{{ $yearStr }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
        </div>
    </div>

    {{-- Employee Information --}}
    <div class="summary-section">
        <div class="payslip-section">
            <div class="payslip-section-title" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee_information'] ?? 'Employee Information')) }};">{{ $getCurrentTranslation['employee_information'] ?? 'Employee Information' }}</div>
            <table class="info-table">
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee_name'] ?? 'Employee Name')) }};">{{ $getCurrentTranslation['employee_name'] ?? 'Employee Name' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($employee->name ?? 'N/A')) }};">{{ $employee->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['designation'] ?? 'Designation')) }};">{{ $getCurrentTranslation['designation'] ?? 'Designation' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($employee->designation->name ?? 'N/A')) }};">{{ $employee->designation->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email'] ?? 'Email')) }};">{{ $getCurrentTranslation['email'] ?? 'Email' }}</td>
                    <td style="font-family: arial;">{{ $employee->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone'] ?? 'Phone')) }};">{{ $getCurrentTranslation['phone'] ?? 'Phone' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($employee->phone ?? 'N/A')) }};">{{ $employee->phone ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-section">
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_base_salary'] ?? 'Total Base Salary')) }};">{{ $getCurrentTranslation['total_base_salary'] ?? 'Total Base Salary' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ number_format($totalBaseSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_deductions'] ?? 'Total Deductions')) }};">{{ $getCurrentTranslation['total_deductions'] ?? 'Total Deductions' }}</div>
                    <div class="summary-card-value" style="color: #dc3545; font-family: arial;">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_bonus'] ?? 'Total Bonus')) }};">{{ $getCurrentTranslation['total_bonus'] ?? 'Total Bonus' }}</div>
                    <div class="summary-card-value" style="color: #28a745; font-family: arial;">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_net_salary'] ?? 'Total Net Salary')) }};">{{ $getCurrentTranslation['total_net_salary'] ?? 'Total Net Salary' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ number_format($totalNetSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_paid'] ?? 'Total Paid')) }};">{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}</div>
                    <div class="summary-card-value" style="color: #28a745; font-family: arial;">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid')) }};">{{ $getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid' }}</div>
                    <div class="summary-card-value" style="color: #dc3545; font-family: arial;">{{ number_format($totalUnpaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Salary Table --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; font-family: {{ language_font(strip_tags($getCurrentTranslation['salary_details'] ?? 'Salary Details')) }};">{{ $getCurrentTranslation['salary_details'] ?? 'Salary Details' }}</h3>
        <table>
            <thead>
                <tr>
                    <th style="font-family: arial;">#</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['month'] ?? 'Month')) }};">{{ $getCurrentTranslation['month'] ?? 'Month' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['year'] ?? 'Year')) }};">{{ $getCurrentTranslation['year'] ?? 'Year' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['base_salary'] ?? 'Base Salary')) }};">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['deductions'] ?? 'Deductions')) }};">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['bonus'] ?? 'Bonus')) }};">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['net_salary'] ?? 'Net Salary')) }};">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_status'] ?? 'Payment Status')) }};">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_date'] ?? 'Payment Date')) }};">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $index => $salary)
                <tr>
                    <td class="text-center" style="font-family: arial;">{{ $index + 1 }}</td>
                    <td class="text-center" style="font-family: {{ language_font(strip_tags($monthNames[$salary->month] ?? $salary->month)) }};">{{ $monthNames[$salary->month] ?? $salary->month }}</td>
                    <td class="text-center" style="font-family: arial;">{{ $salary->year }}</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($salary->base_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="color: #dc3545; font-family: arial;">{{ number_format($salary->deductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="color: #28a745; font-family: arial;">{{ number_format($salary->bonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($salary->net_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-center">
                        <span class="badge 
                            @if($salary->payment_status == 'Paid') badge-success
                            @elseif($salary->payment_status == 'Partial') badge-warning
                            @else badge-danger
                            @endif" style="font-family: {{ language_font(strip_tags($salary->payment_status)) }};">
                            {{ $salary->payment_status }}
                        </span>
                    </td>
                    <td class="text-center" style="font-family: arial;">
                        @if($salary->payment_date)
                            {{ \Carbon\Carbon::parse($salary->payment_date)->format('Y-m-d') }}
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
