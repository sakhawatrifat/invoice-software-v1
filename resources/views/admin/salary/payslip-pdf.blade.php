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
            padding: 0;
        }
        .payslip-container {
            width: 100%;
            padding: 20px;
        }
        .header {
            margin-bottom: 20px;
            text-align: center;
            padding: 0;
            margin-top: -20px;
            margin-left: -20px;
            margin-right: -20px;
            padding: 20px;
        }
        .header-logo {
            margin: 0;
            padding: 0;
        }
        .header-logo > strong,
        .header-logo > img {
            margin: 0;
            padding: 0;
            display: block;
        }
        .header-logo > div {
            margin: 0;
            padding: 0;
            line-height: 1;
        }
        .header-logo img {
            height: 30px;
            max-width: 200px;
            margin: 0;
            padding: 0;
        }
        .header-logo > div > div {
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        .header-title {
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
        }
        .section-title {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
            color: #ffffff;
            margin-bottom: 15px;
            border-radius: 6px;
            width: 100%;
            display: block;
        }
        .section-titles-container {
            margin-bottom: 20px;
        }
        .info-tables-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }
        .info-tables-container td {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
            overflow: hidden;
        }
        .info-tables-container td:first-child {
            padding-left: 0;
        }
        .info-tables-container td:last-child {
            padding-right: 0;
        }
        .info-tables-container td > table {
            min-height: 250px;
            height: 250px;
        }
        .company-info-table,
        .employee-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            display: table;
            table-layout: fixed;
            height: 100%;
        }
        .company-info-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
            color: #ffffff;
            text-align: left;
        }
        .employee-info-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            padding: 10px 15px;
            font-weight: bold;
            font-size: 14px;
            color: #ffffff;
            text-align: left;
        }
        .company-info-table td,
        .employee-info-table td {
            padding: 8px 15px;
            border: none;
            font-size: 11px;
            background-color: transparent;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            color: #333;
        }
        .info-line {
            padding-top: 8px;
            padding-bottom: 8px;
            font-size: 11px;
            display: block;
        }
        .info-line:first-child {
            padding-top: 0;
        }
        .info-line:last-child {
            padding-bottom: 0;
        }
        .info-line strong {
            font-weight: bold;
            color: #185786;
            display: inline-block;
            min-width: 120px;
        }
        .tables-section-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .tables-section-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
        }
        .tables-section-table td:first-child {
            padding-left: 0;
        }
        .tables-section-table td:last-child {
            padding-right: 0;
        }
        .tables-section-table td > table {
            min-height: 200px;
        }
        .earnings-table,
        .deductions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            border-radius: 8px;
            overflow: hidden;
            display: table;
        }
        .earnings-table th,
        .deductions-table th,
        .earnings-table td,
        .deductions-table td {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            font-size: 11px;
        }
        .earnings-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            font-weight: bold;
            color: #ffffff;
            text-align: left;
        }
        .deductions-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            font-weight: bold;
            color: #ffffff;
            text-align: left;
        }
        .earnings-table th:last-child,
        .deductions-table th:last-child {
            text-align: right;
        }
        .earnings-table td,
        .deductions-table td {
            text-align: left;
            background-color: #ffffff;
        }
        .earnings-table td:last-child,
        .deductions-table td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .earnings-table .total-row {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            color: white;
            font-weight: bold;
        }
        .deductions-table .total-row {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            color: white;
            font-weight: bold;
        }
        .earnings-table .total-row td,
        .deductions-table .total-row td {
            color: white !important;
            font-size: 12px;
            font-weight: bold;
        }
        .net-payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }
        .net-payment-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 15px;
        }
        .net-payment-table td:first-child {
            padding-left: 0;
        }
        .net-payment-table td:last-child {
            padding-right: 0;
        }
        .net-payment-table td > table {
            min-height: 250px;
            height: 250px;
            width: 100%;
        }
        .net-salary-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            table-layout: fixed;
            min-height: 250px;
            height: 250px;
        }
        .net-salary-table th,
        .net-salary-table td {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            font-size: 11px;
        }
        .net-salary-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            font-weight: bold;
            color: #ffffff;
            font-size: 14px;
        }
        .net-salary-table td {
            background-color: #f8f9ff;
        }
        .net-salary-table tbody tr:first-child td {
            font-weight: bold;
            font-size: 16px;
            color: #185786;
        }
        .net-salary-table tbody tr td:first-child {
            font-weight: 600;
            color: #185786;
        }
        .net-salary-table tbody tr td:last-child {
            text-align: right;
        }
        .payment-info-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            table-layout: fixed;
            min-height: 250px;
            height: 250px;
        }
        .payment-info-table th,
        .payment-info-table td {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            font-size: 11px;
        }
        .payment-info-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            font-weight: bold;
            color: #ffffff;
            font-size: 14px;
        }
        .payment-info-table td {
            background-color: #f8f9ff;
        }
        .payment-info-table td:first-child {
            font-weight: 600;
            color: #185786;
        }
        .payment-info-table td:last-child {
            text-align: right;
        }
        .payment-status {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .payment-status.paid {
            color: #28a745;
            border: 1px solid #28a745;
            background-color: transparent;
        }
        .payment-status.unpaid {
            color: #dc3545;
            border: 1px solid #dc3545;
            background-color: transparent;
        }
        .payment-status.partial {
            color: #ffc107;
            border: 1px solid #ffc107;
            background-color: transparent;
        }
        .notes-section {
            margin-bottom: 25px;
        }
        .notes-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }
        .notes-table th {
            background: linear-gradient(135deg, #185786 0%, #2298d4 100%);
            padding: 10px 15px;
            font-weight: bold;
            color: #ffffff;
            text-align: left;
            font-size: 14px;
        }
        .notes-table td {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            background-color: #f8f9ff;
            font-size: 11px;
        }
        .notes-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .notes-list li {
            margin-bottom: 8px;
            padding-left: 0;
        }
        .notes-list li:last-child {
            margin-bottom: 0;
        }
        .notes-list li::before {
            content: "• ";
            color: #185786;
            font-weight: bold;
            margin-right: 5px;
        }
        .seal-container {
            position: relative;
            text-align: left;
            margin-top: 20px;
        }
        .seal-container img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }
        .footer-text {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
            font-style: italic;
        }
        .text-right {
            text-align: right;
        }
        .reason-text {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="payslip-container">
        {{-- Header --}}
        <div class="header">
            @php
                $globalData = Auth::user();
            @endphp
            <div class="header-logo">
                @php
                    $globalData = Auth::user();
                    $email = $globalData->company_data->email_1 ?? '';
                    $phone = $globalData->company_data->phone_1 ?? '';
                    $address = $globalData->company_data->address ?? '';
                @endphp
                <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
                    <tr>
                        <td style="text-align: center; margin: 0; padding: 0; border: none; line-height: 1;">
                            @if(env('UNDER_DEVELOPMENT') == true)
                                <strong style="font-size: 16px; font-family: {{ language_font(strip_tags($globalData->company_data->company_name ?? 'N/A')) }}; margin: 0; padding: 0; display: block; line-height: 1;">{{ $globalData->company_data->company_name ?? 'N/A' }}</strong>
                            @else
                                @if(!empty($globalData->company_data->dark_logo_url))
                                    <img alt="{{ $globalData->company_data->company_name ?? 'N/A' }}" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" style="height: 30px; max-width: 200px; margin: 0; padding: 0; display: block; line-height: 1;" />
                                @endif
                            @endif
                        </td>
                    </tr>
                    @if($address)
                    <tr>
                        <td style="text-align: center; margin: 0; padding: 0; padding-top: 5px; border: none; font-size: 10px; line-height: 1;">
                            <div style="font-family: {{ language_font(strip_tags($address)) }}; margin: 0; padding: 0; line-height: 1.2;">
                                {!! $address !!}
                            </div>
                        </td>
                    </tr>
                    @endif
                    @if($email || $phone)
                    <tr>
                        <td style="text-align: center; margin: 0; padding: 0; padding-top: 5px; border: none; font-size: 10px; line-height: 1;">
                            <div style="margin: 0; padding: 0; line-height: 1.2;">
                                @if($email)
                                <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email'] ?? 'Email')) }};">{{ $getCurrentTranslation['email'] ?? 'Email' }}:</span> <span style="font-family: arial;">{{ $email }}</span>
                                @endif
                                @if($email && $phone) <span style="margin: 0 5px;">|</span> @endif
                                @if($phone)
                                <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone'] ?? 'Phone')) }};">{{ $getCurrentTranslation['phone'] ?? 'Phone' }}:</span> <span style="font-family: arial;">{{ $phone }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endif
                </table>
            </div>


            <div class="header-title" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['salary_pay_slip'] ?? 'Salary Pay Slip')) }};">
                {{ $getCurrentTranslation['salary_pay_slip'] ?? 'Salary Pay Slip' }}
            </div>
        </div>

        {{-- Company Information and Employee Information Tables --}}
        <table class="info-tables-container" style="width: 100%; table-layout: fixed; padding:0">
            <tr>
                <td style="width: 50%; padding-left:0">
                    <table class="company-info-table" style="width: 100%; table-layout: fixed;">
                        <thead>
                            <tr>
                                <th colspan="2" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['company_information'] ?? 'Company Information')) }};">{{ $getCurrentTranslation['company_information'] ?? 'Company Information' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2" style="font-family: {{ language_font(strip_tags($globalData->company_data->company_name ?? 'N/A')) }}; padding: 10px 15px; vertical-align: top; word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; max-width: 100%; box-sizing: border-box; width: 100%;">
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['company_name'] ?? 'Company Name')) }};">{{ $getCurrentTranslation['company_name'] ?? 'Company Name' }}:</strong> 
                                        <span style="font-family: {{ language_font(strip_tags($globalData->company_data->company_name ?? 'N/A')) }};">{{ $globalData->company_data->company_name ?? 'N/A' }}</span>
                                    </div>
                                    @if($address)
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['address'] ?? 'Address')) }};">{{ $getCurrentTranslation['address'] ?? 'Address' }}:</strong> 
                                        <span style="font-family: {{ language_font(strip_tags($address)) }};">{!! $address !!}</span>
                                    </div>
                                    @endif
                                    @if($phone)
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['contact'] ?? 'Contact')) }};">{{ $getCurrentTranslation['contact'] ?? 'Contact' }}:</strong> 
                                        <span style="font-family: arial;">{{ $phone }}</span>
                                    </div>
                                    @endif
                                    @if($email)
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 0;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email'] ?? 'Email')) }};">{{ $getCurrentTranslation['email'] ?? 'Email' }}:</strong> 
                                        <span style="font-family: arial;">{{ $email }}</span>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; padding-right:0">
                    <table class="employee-info-table" style="width: 100%; table-layout: fixed;">
                        <thead>
                            <tr>
                                <th colspan="2" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee_information'] ?? 'Employee Information')) }};">{{ $getCurrentTranslation['employee_information'] ?? 'Employee Information' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2" style="font-family: {{ language_font(strip_tags($salary->employee->name ?? 'N/A')) }}; padding: 10px 15px; vertical-align: top; word-wrap: break-word; word-break: break-word; overflow-wrap: break-word; max-width: 100%; box-sizing: border-box; width: 100%;">
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['employee_name'] ?? 'Employee Name')) }};">{{ $getCurrentTranslation['employee_name'] ?? 'Employee Name' }}:</strong> 
                                        <span style="font-family: {{ language_font(strip_tags($salary->employee->name ?? 'N/A')) }};">{{ $salary->employee->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['designation'] ?? 'Designation')) }};">{{ $getCurrentTranslation['designation'] ?? 'Designation' }}:</strong> 
                                        <span style="font-family: {{ language_font(strip_tags($salary->employee->designation?->name ?? 'N/A')) }};">{{ $salary->employee->designation?->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['department'] ?? 'Department')) }};">{{ $getCurrentTranslation['department'] ?? 'Department' }}:</strong> 
                                        <span style="font-family: {{ language_font(strip_tags($salary->employee->department?->name ?? 'N/A')) }};">{{ $salary->employee->department?->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 12px;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['pay_month'] ?? 'Pay Month')) }};">{{ $getCurrentTranslation['pay_month'] ?? 'Pay Month' }}:</strong> 
                                        <span style="font-family: {{ language_font(strip_tags(($monthNames[$salary->month] ?? $salary->month) . ' ' . $salary->year)) }};">{{ $monthNames[$salary->month] ?? $salary->month }} {{ $salary->year }}</span>
                                    </div>
                                    @if($salary->payment_date)
                                    <div class="info-line" style="padding-top: 0; padding-bottom: 0;">
                                        <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_date'] ?? 'Payment Date')) }};">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}:</strong> 
                                        <span style="font-family: arial;">{{ \Carbon\Carbon::parse($salary->payment_date)->format('d F Y') }}</span>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Earnings and Deductions Tables --}}
        <table class="tables-section-table">
            <tr>
                <td style="padding-left:0">
                    <table class="earnings-table">
                        <thead>
                            <tr>
                                <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['earnings'] ?? 'Earnings')) }};">{{ $getCurrentTranslation['earnings'] ?? 'Earnings' }}</th>
                                <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['amount'] ?? 'Amount')) }};">{{ $getCurrentTranslation['amount'] ?? 'Amount' }} <span style="font-family: arial;">({{Auth::user()->company_data->currency->short_name ?? ''}})</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['basic_salary'] ?? 'Basic Salary')) }};">{{ $getCurrentTranslation['basic_salary'] ?? 'Basic Salary' }}</td>
                                <td class="text-right" style="font-family: arial;">{{ number_format($salary->base_salary, 2) }}</td>
                            </tr>
                            @if($salary->bonus > 0)
                            <tr>
                                <td style="vertical-align: middle">
                                    <div style="font-family: {{ language_font(strip_tags($getCurrentTranslation['incentive_bonus'] ?? 'Incentive / Bonus')) }};">{{ $getCurrentTranslation['incentive_bonus'] ?? 'Incentive / Bonus' }}</div>
                                    @if($salary->bonus_note)
                                    @php
                                        $bonusNoteText = strip_tags($salary->bonus_note);
                                        $bonusNoteDisplay = strlen($bonusNoteText) > 55 ? \Str::limit($bonusNoteText, 55) : $bonusNoteText;
                                    @endphp
                                    <div class="reason-text" style="font-family: {{ language_font($bonusNoteText) }};">{{ $bonusNoteDisplay }}</div>
                                    @endif
                                </td>
                                <td class="text-right" style="font-family: arial; vertical-align: middle;">{{ number_format($salary->bonus, 2) }}</td>
                            </tr>
                            @else
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['incentive_bonus'] ?? 'Incentive / Bonus')) }};">{{ $getCurrentTranslation['incentive_bonus'] ?? 'Incentive / Bonus' }}</td>
                                <td class="text-right" style="font-family: arial;">0.00</td>
                            </tr>
                            @endif
                            <tr class="total-row">
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_earnings'] ?? 'Total Earnings')) }}; color: white !important;">{{ $getCurrentTranslation['total_earnings'] ?? 'Total Earnings' }}</td>
                                <td class="text-right" style="font-family: arial; color: white !important;">{{ number_format($salary->base_salary + $salary->bonus, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="padding-right:0">
                    <table class="deductions-table">
                        <thead>
                            <tr>
                                <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['deductions'] ?? 'Deductions')) }};">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</th>
                                <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['amount'] ?? 'Amount')) }};">{{ $getCurrentTranslation['amount'] ?? 'Amount' }} <span style="font-family: arial;">({{Auth::user()->company_data->currency->short_name ?? ''}})</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="vertical-align: middle">
                                    <div style="font-family: {{ language_font(strip_tags($getCurrentTranslation['deductions'] ?? 'Deductions')) }};">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</div>
                                    @if($salary->deduction_note)
                                    @php
                                        $deductionNoteText = strip_tags($salary->deduction_note);
                                        $deductionNoteDisplay = strlen($deductionNoteText) > 55 ? \Str::limit($deductionNoteText, 55) : $deductionNoteText;
                                    @endphp
                                    <div class="reason-text" style="font-family: {{ language_font($deductionNoteText) }};">{{ $deductionNoteDisplay }}</div>
                                    @endif
                                </td>
                                <td class="text-right" style="font-family: arial; vertical-align: middle;">{{ number_format($salary->deductions, 2) }}</td>
                            </tr>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['other_deductions'] ?? 'Other Deductions')) }};">{{ $getCurrentTranslation['other_deductions'] ?? 'Other Deductions' }}</td>
                                <td class="text-right" style="font-family: arial;">0.00</td>
                            </tr>
                            <tr class="total-row">
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_deductions'] ?? 'Total Deductions')) }}; color: white !important;">{{ $getCurrentTranslation['total_deductions'] ?? 'Total Deductions' }}</td>
                                <td class="text-right" style="font-family: arial; color: white !important;">{{ number_format($salary->deductions, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Net Salary and Payment Info Tables --}}
        <table class="net-payment-table" style="width: 100%; table-layout: fixed;">
            <tr>
                <td style="width: 50%; padding-left:0">
                    <table class="net-salary-table" style="width: 100%; table-layout: fixed; min-height: 250px; height: 250px;">
                        <thead>
                            <tr>
                                <th colspan="2" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['net_salary'] ?? 'Net Salary')) }};">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['net_salary'] ?? 'Net Salary')) }};">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</td>
                                <td class="text-right" style="font-family: arial;">{{ number_format($salary->net_salary, 2) }} <span style="font-family: arial;">({{Auth::user()->company_data->currency->short_name ?? ''}})</span></td>
                            </tr>
                            <tr>
                                <td colspan="2" style="height: 35px; padding: 10px 15px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="height: 35px; padding: 10px 15px;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="height: 35px; padding: 10px 15px;">&nbsp;</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 50%; padding-right:0">
                    <table class="payment-info-table" style="width: 100%; table-layout: fixed; min-height: 250px; height: 250px;">
                        <thead>
                            <tr>
                                <th colspan="2" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_information'] ?? 'Payment Information')) }};">{{ $getCurrentTranslation['payment_information'] ?? 'Payment Information' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_method'] ?? 'Payment Method')) }};">{{ $getCurrentTranslation['payment_method'] ?? 'Payment Method' }}</td>
                                <td class="text-right" style="font-family: {{ language_font(strip_tags($salary->payment_method ?? 'N/A')) }};">{{ $salary->payment_method ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_status'] ?? 'Payment Status')) }};">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</td>
                                <td class="text-right">
                                    <span class="payment-status {{ strtolower($salary->payment_status ?? 'unpaid') }}" style="font-family: {{ language_font(strip_tags($salary->payment_status ?? 'Unpaid')) }};">{{ $salary->payment_status ?? 'Unpaid' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['paid_amount'] ?? 'Paid Amount')) }};">{{ $getCurrentTranslation['paid_amount'] ?? 'Paid Amount' }}</td>
                                <td class="text-right" style="font-family: arial;">{{ number_format($salary->paid_amount ?? 0, 2) }} <span style="font-family: arial;">({{Auth::user()->company_data->currency->short_name ?? ''}})</span></td>
                            </tr>
                            <tr>
                                <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['due_amount'] ?? 'Due Amount')) }};">{{ $getCurrentTranslation['due_amount'] ?? 'Due Amount' }}</td>
                                <td class="text-right" style="font-family: arial;">{{ number_format(($salary->net_salary - ($salary->paid_amount ?? 0)), 2) }} <span style="font-family: arial;">({{Auth::user()->company_data->currency->short_name ?? ''}})</span></td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Notes Section --}}
        <div class="notes-section">
            <table class="notes-table">
                <thead>
                    <tr>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['notes'] ?? 'Notes')) }};">{{ $getCurrentTranslation['notes'] ?? 'Notes' }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <ul class="notes-list">
                                <li style="font-family: {{ language_font(strip_tags($getCurrentTranslation['this_is_a_system_generated_document'] ?? 'This is a system generated document and does not require a signature.')) }};">
                                    {{ $getCurrentTranslation['this_is_a_system_generated_document'] ?? 'This is a system generated document and does not require a signature.' }}
                                </li>
                                <li style="font-family: {{ language_font(strip_tags($getCurrentTranslation['no_signature_required'] ?? 'No signature required.')) }};">
                                    {{ $getCurrentTranslation['no_signature_required'] ?? 'No signature required.' }}
                                </li>
                                <li style="font-family: {{ language_font(strip_tags($getCurrentTranslation['for_any_query_please_contact_accounts_department'] ?? 'For any query, please contact Accounts Department.')) }};">
                                    {{ $getCurrentTranslation['for_any_query_please_contact_accounts_department'] ?? 'For any query, please contact Accounts Department.' }}
                                </li>
                            </ul>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Seal --}}
        @if(env('UNDER_DEVELOPMENT') != true && !empty($globalData->company_data->dark_seal))
        <div class="seal-container">
            <img src="{{ $globalData->company_data->dark_seal }}" alt="Company Seal" />
        </div>
        @else
            <span>Company Seal Here</span>
        @endif

    </div>
</body>
</html>