<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['expense_report'] ?? 'Expense Report' }}</title>
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
            width: 25%;
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
            <h1>{{ $getCurrentTranslation['expense_report'] ?? 'Expense Report' }}</h1>
            <p><strong>{{ $getCurrentTranslation['date_range'] ?? 'Date Range' }}:</strong> {{ $dateRangeStr }}</p>
            <p><strong>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="summary-section">
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_expenses'] ?? 'Total Expenses' }}</div>
                    <div class="summary-card-value">{{ $totalCount }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_amount'] ?? 'Total Amount' }}</div>
                    <div class="summary-card-value" style="color: #dc3545;">{{ number_format($totalAmount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}</div>
                    <div class="summary-card-value" style="color: #28a745;">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
                <td class="summary-card">
                    <div class="summary-card-header">{{ $getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid' }}</div>
                    <div class="summary-card-value" style="color: #ffc107;">{{ number_format($totalUnpaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Expense Table --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['expense_details'] ?? 'Expense Details' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $getCurrentTranslation['date'] ?? 'Date' }}</th>
                    <th>{{ $getCurrentTranslation['category'] ?? 'Category' }}</th>
                    <th>{{ $getCurrentTranslation['description'] ?? 'Description' }}</th>
                    <th>{{ $getCurrentTranslation['for_user'] ?? 'For User' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['amount'] ?? 'Amount' }}</th>
                    <th>{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $index => $expense)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $expense->category->name ?? 'N/A' }}</td>
                    <td>{{ $expense->description ?? '-' }}</td>
                    <td>{{ $expense->forUser->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ number_format($expense->amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-center">
                        <span class="badge 
                            @if($expense->payment_status == 'Paid') badge-success
                            @else badge-danger
                            @endif">
                            {{ $expense->payment_status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}</td>
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
