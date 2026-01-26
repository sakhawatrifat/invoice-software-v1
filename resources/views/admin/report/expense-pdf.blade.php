<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['expense_report'] ?? 'Expense Report' }}</title>
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
            width: 25%;
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
            <h1 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['expense_report'] ?? 'Expense Report')) }};">{{ $getCurrentTranslation['expense_report'] ?? 'Expense Report' }}</h1>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['date_range'] ?? 'Date Range')) }};">{{ $getCurrentTranslation['date_range'] ?? 'Date Range' }}:</strong> <span style="font-family: {{ language_font(strip_tags($dateRangeStr)) }};">{{ $dateRangeStr }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-section">
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_expenses'] ?? 'Total Expenses')) }};">{{ $getCurrentTranslation['total_expenses'] ?? 'Total Expenses' }}</div>
                    <div class="summary-card-value" style="font-family: arial;">{{ $totalCount }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_amount'] ?? 'Total Amount')) }};">{{ $getCurrentTranslation['total_amount'] ?? 'Total Amount' }}</div>
                    <div class="summary-card-value" style="color: #dc3545; font-family: arial;">{{ number_format($totalAmount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_paid'] ?? 'Total Paid')) }};">{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}</div>
                    <div class="summary-card-value" style="color: #28a745; font-family: arial;">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid')) }};">{{ $getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid' }}</div>
                    <div class="summary-card-value" style="color: #ffc107; font-family: arial;">{{ number_format($totalUnpaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Expense Table --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; font-family: {{ language_font(strip_tags($getCurrentTranslation['expense_details'] ?? 'Expense Details')) }};">{{ $getCurrentTranslation['expense_details'] ?? 'Expense Details' }}</h3>
        <table>
            <thead>
                <tr>
                    <th style="font-family: arial;">#</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['date'] ?? 'Date')) }};">{{ $getCurrentTranslation['date'] ?? 'Date' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['category'] ?? 'Category')) }};">{{ $getCurrentTranslation['category'] ?? 'Category' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['description'] ?? 'Description')) }};">{{ $getCurrentTranslation['description'] ?? 'Description' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['for_user'] ?? 'For User')) }};">{{ $getCurrentTranslation['for_user'] ?? 'For User' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['amount'] ?? 'Amount')) }};">{{ $getCurrentTranslation['amount'] ?? 'Amount' }}</th>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_status'] ?? 'Payment Status')) }};">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $index => $expense)
                <tr>
                    <td class="text-center" style="font-family: arial;">{{ $index + 1 }}</td>
                    <td style="font-family: arial;">{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') : 'N/A' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($expense->category->name ?? 'N/A')) }};">{{ $expense->category->name ?? 'N/A' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($expense->description ?? '-')) }};">{{ $expense->description ?? '-' }}</td>
                    <td style="font-family: {{ language_font(strip_tags($expense->forUser->name ?? 'N/A')) }};">{{ $expense->forUser->name ?? 'N/A' }}</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($expense->amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-center">
                        <span class="badge 
                            @if($expense->payment_status == 'Paid') badge-success
                            @else badge-danger
                            @endif" style="font-family: {{ language_font(strip_tags($expense->payment_status)) }};">
                            {{ $expense->payment_status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['no_data_found'] ?? 'No data found')) }};">{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}</td>
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
