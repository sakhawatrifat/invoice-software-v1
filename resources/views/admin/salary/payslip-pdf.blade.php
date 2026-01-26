<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['salary_payslip'] ?? 'Salary Payslip' }}</title>
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
            margin-bottom: 3px;
        }
        .header-logo strong,
        .header-logo img {
            margin-bottom: 0px;
        }
        .header-logo > div {
            margin-top: 0px;
        }
        .header-logo > div > div {
            margin-top: 0px;
            margin-bottom: 0px;
        }
        .header-logo > div > div:first-child {
            margin-bottom: 0px;
        }
        .header-logo > div > div:last-child {
            margin-top: 0px;
        }
        .header-logo p,
        .header-logo div p,
        .header-logo * {
            margin-top: 0px;
            margin-bottom: 0px;
        }
        .header-content {
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 16px;
            font-weight: normal;
            color: #666;
        }
        .payslip-container {
            margin-top: 10px;
        }
        .payslip-section {
            margin-bottom: 10px;
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
        .salary-breakdown {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .salary-breakdown th,
        .salary-breakdown td {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 11px;
        }
        .salary-breakdown th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .salary-breakdown .label {
            font-weight: bold;
            width: 60%;
        }
        .salary-breakdown .amount {
            text-align: right;
            width: 40%;
            font-weight: bold;
        }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            display: inline-block;
            font-weight: bold;
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
            <div style="margin-top: 5px; font-size: 10px; text-align: center;">
                @if($address)
                <div style="font-family: {{ language_font(strip_tags($address)) }}; margin-bottom: 0px;">
                    {!! $address !!}
                </div>
                @endif
                @if($email || $phone)
                <div style="margin-top: 5px;">
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
            <h1 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['salary_payslip'] ?? 'Salary Payslip')) }};">{{ $getCurrentTranslation['salary_payslip'] ?? 'Salary Payslip' }}</h1>
            <h2 style="font-family: {{ language_font(strip_tags(($monthNames[$salary->month] ?? $salary->month) . ' ' . $salary->year)) }};">{{ $monthNames[$salary->month] ?? $salary->month }} {{ $salary->year }}</h2>
        </div>
    </div>

    <div class="payslip-container">
        {{-- Employee Information --}}
        <div class="payslip-section">
            <div class="payslip-section-title" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee_information'] ?? 'Employee Information')) }};">{{ $getCurrentTranslation['employee_information'] ?? 'Employee Information' }}</div>
            <table class="info-table">
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee_name'] ?? 'Employee Name')) }};">{{ $getCurrentTranslation['employee_name'] ?? 'Employee Name' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($salary->employee->name ?? 'N/A')) }};">{{ $salary->employee->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['designation'] ?? 'Designation')) }};">{{ $getCurrentTranslation['designation'] ?? 'Designation' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($salary->employee->designation->name ?? 'N/A')) }};">{{ $salary->employee->designation->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email'] ?? 'Email')) }};">{{ $getCurrentTranslation['email'] ?? 'Email' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($salary->employee->email ?? 'N/A')) }};">{{ $salary->employee->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone'] ?? 'Phone')) }};">{{ $getCurrentTranslation['phone'] ?? 'Phone' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($salary->employee->phone ?? 'N/A')) }};">{{ $salary->employee->phone ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        {{-- Salary Breakdown --}}
        <div class="payslip-section">
            <div class="payslip-section-title" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['salary_breakdown'] ?? 'Salary Breakdown')) }};">{{ $getCurrentTranslation['salary_breakdown'] ?? 'Salary Breakdown' }}</div>
            <table class="salary-breakdown">
                <thead>
                    <tr>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['description'] ?? 'Description')) }};">{{ $getCurrentTranslation['description'] ?? 'Description' }}</th>
                        <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['amount'] ?? 'Amount')) }};">{{ $getCurrentTranslation['amount'] ?? 'Amount' }} <span style="font-family: arial;">({{Auth::user()->company_data->currency->short_name ?? ''}})</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="label" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['base_salary'] ?? 'Base Salary')) }};">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }}</td>
                        <td class="amount text-right" style="font-family: arial;">{{ number_format($salary->base_salary, 2) }}</td>
                    </tr>
                    @if($salary->bonus > 0)
                    <tr>
                        <td class="label" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['bonus'] ?? 'Bonus')) }};">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }}</td>
                        <td class="amount text-right" style="color: #28a745; font-family: arial;">+ {{ number_format($salary->bonus, 2) }}</td>
                    </tr>
                    @endif
                    @if($salary->deductions > 0)
                    <tr>
                        <td class="label" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['deductions'] ?? 'Deductions')) }};">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</td>
                        <td class="amount text-right" style="color: #dc3545; font-family: arial;">- {{ number_format($salary->deductions, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['net_salary'] ?? 'Net Salary')) }};">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</td>
                        <td class="amount text-right" style="font-family: arial;">{{ number_format($salary->net_salary, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Payment Information --}}
        <div class="payslip-section">
            <div class="payslip-section-title" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_information'] ?? 'Payment Information')) }};">{{ $getCurrentTranslation['payment_information'] ?? 'Payment Information' }}</div>
            <table class="info-table">
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_status'] ?? 'Payment Status')) }};">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</td>
                    <td>
                        <span class="badge 
                            @if($salary->payment_status == 'Paid') badge-success
                            @elseif($salary->payment_status == 'Partial') badge-warning
                            @else badge-danger
                            @endif" style="font-family: {{ language_font(strip_tags($salary->payment_status)) }};">
                            {{ $salary->payment_status }}
                        </span>
                    </td>
                </tr>
                @if($salary->payment_date)
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_date'] ?? 'Payment Date')) }};">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}</td>
                    <td style="font-family: arial;">{{ \Carbon\Carbon::parse($salary->payment_date)->format('Y-m-d') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['month'] ?? 'Month')) }};">{{ $getCurrentTranslation['month'] ?? 'Month' }}</td>
                    <td style="font-family: {{ language_font(strip_tags(($monthNames[$salary->month] ?? $salary->month) . ' ' . $salary->year)) }};">{{ $monthNames[$salary->month] ?? $salary->month }} {{ $salary->year }}</td>
                </tr>
            </table>
        </div>

        @if($salary->deduction_note || $salary->bonus_note)
        {{-- Notes --}}
        <div class="payslip-section">
            <div class="payslip-section-title" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['notes'] ?? 'Notes')) }};">{{ $getCurrentTranslation['notes'] ?? 'Notes' }}</div>
            <table class="info-table">
                @if($salary->deduction_note)
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['deduction_note'] ?? 'Deduction Note')) }};">{{ $getCurrentTranslation['deduction_note'] ?? 'Deduction Note' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($salary->deduction_note)) }};">{{ $salary->deduction_note }}</td>
                </tr>
                @endif
                @if($salary->bonus_note)
                <tr>
                    <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['bonus_note'] ?? 'Bonus Note')) }};">{{ $getCurrentTranslation['bonus_note'] ?? 'Bonus Note' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($salary->bonus_note)) }};">{{ $salary->bonus_note }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
    </div>

    <div class="footer">
        <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
        <p style="font-family: {{ language_font(strip_tags($getCurrentTranslation['this_is_a_system_generated_document'] ?? 'This is a system generated document and does not require a signature.')) }};">{{ $getCurrentTranslation['this_is_a_system_generated_document'] ?? 'This is a system generated document and does not require a signature.' }}</p>
    </div>
</body>
</html>
