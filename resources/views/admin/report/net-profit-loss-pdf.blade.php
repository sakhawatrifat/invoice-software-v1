<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['net_profit_loss_report'] ?? 'Net Profit Loss Report' }}</title>
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
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            text-align: center;
        }
        .page-break {
            page-break-before: always;
        }
        .no-break {
            page-break-inside: avoid;
        }
        .summary-section {
            page-break-inside: avoid;
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
            <h1>{{ $getCurrentTranslation['net_profit_loss_report'] ?? 'Net Profit Loss Report' }}</h1>
            <p><strong>{{ $getCurrentTranslation['date_range'] ?? 'Date Range' }}:</strong> {{ $dateRangeStr }}</p>
            <p><strong>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    {{-- Net Profit/Loss Summary (First Page) --}}
    <div class="summary-section no-break">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['net_profit_loss_summary'] ?? 'Net Profit/Loss Summary' }}</h3>
        <table class="no-break">
            <thead>
                <tr>
                    <th style="width: 50%;"><b>{{ $getCurrentTranslation['title'] ?? 'Title' }}</b></th>
                    <th><b>{{ $getCurrentTranslation['amount'] ?? 'Amount' }}</b></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $isProfitAfterRefund = $total_profit_after_refund >= 0;
                    $profitLossAfterRefundValue = $isProfitAfterRefund ? number_format($total_profit_after_refund, 2) : '-' . number_format(abs($total_profit_after_refund), 2);
                    $profitLossAfterRefundStyle = $isProfitAfterRefund ? 'color: #28a745;' : 'color: #dc3545;';
                @endphp
                <tr>
                    <th style="background-color: #f0f0f0;">{{ $getCurrentTranslation['gross_profit_after_refund'] ?? 'Gross Profit After Refund' }}</th>
                    <td class="text-right" style="{{ $profitLossAfterRefundStyle }}">{{ $profitLossAfterRefundValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th style="background-color: #f0f0f0; color: #dc3545;">{{ $getCurrentTranslation['total_salary'] ?? 'Total Salary' }}</th>
                    <td class="text-right" style="color: #dc3545;">-{{ number_format($total_salary_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th style="background-color: #f0f0f0; color: #ffc107;">{{ $getCurrentTranslation['total_expenses'] ?? 'Total Expenses' }}</th>
                    <td class="text-right" style="color: #ffc107;">-{{ number_format($total_expense_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @php
                    $isNetProfit = $net_profit_loss >= 0;
                    $netProfitLossLabel = $isNetProfit ? ($getCurrentTranslation['net_profit'] ?? 'Net Profit') : ($getCurrentTranslation['net_loss'] ?? 'Net Loss');
                    $netProfitLossClass = $isNetProfit ? 'color: #28a745;' : 'color: #dc3545;';
                    $netProfitLossValue = $isNetProfit ? number_format($net_profit_loss, 2) : '-' . number_format(abs($net_profit_loss), 2);
                @endphp
                <tr style="font-weight: bold; font-size: 14px; background-color: #f0f0f0;">
                    <th style="{{ $netProfitLossClass }}">{{ $netProfitLossLabel }}</th>
                    <td class="text-right" style="{{ $netProfitLossClass }}">{{ $netProfitLossValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Gross Profit Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; page-break-after: avoid;">{{ $getCurrentTranslation['gross_profit_loss_summary'] ?? 'Gross Profit/Loss Summary' }}</h3>
        <table style="page-break-inside: avoid;">
            <tbody>
                <tr>
                    <th style="width: 50%;">{{ $getCurrentTranslation['total_purchase'] ?? 'Total Purchase' }}</th>
                    <td class="text-right">{{ number_format($total_purchase_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_selling'] ?? 'Total Selling' }}</th>
                    <td class="text-right">{{ number_format($total_selling_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @php
                    $isProfit = $total_profit >= 0;
                    $profitLossLabel = $isProfit ? ($getCurrentTranslation['total_profit'] ?? 'Total Profit') : ($getCurrentTranslation['total_loss'] ?? 'Total Loss');
                    $profitLossClass = $isProfit ? 'color: #28a745;' : 'color: #dc3545;';
                    $profitLossValue = $isProfit ? number_format($total_profit, 2) : '-' . number_format(abs($total_profit), 2);
                @endphp
                <tr style="font-weight: bold; {{ $profitLossClass }}">
                    <th>{{ $profitLossLabel }}</th>
                    <td class="text-right">{{ $profitLossValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'Total Cancellation Fee' }}</th>
                    <td class="text-right">-{{ number_format($total_cancellation_fee, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @php
                    $isProfitAfterRefund = $total_profit_after_refund >= 0;
                    $profitLossAfterRefundLabel = $isProfitAfterRefund ? ($getCurrentTranslation['total_profit_after_refund'] ?? 'Total Profit After Refund') : ($getCurrentTranslation['total_loss_after_refund'] ?? 'Total Loss After Refund');
                    $profitLossAfterRefundClass = $isProfitAfterRefund ? 'color: #28a745;' : 'color: #dc3545;';
                    $profitLossAfterRefundValue = $isProfitAfterRefund ? number_format($total_profit_after_refund, 2) : '-' . number_format(abs($total_profit_after_refund), 2);
                @endphp
                <tr style="font-weight: bold; {{ $profitLossAfterRefundClass }}">
                    <th>{{ $profitLossAfterRefundLabel }}</th>
                    <td class="text-right">{{ $profitLossAfterRefundValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Paid and Due Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; page-break-after: avoid;">{{ $getCurrentTranslation['paid_and_due_summary'] ?? 'Paid and Due Summary' }}</h3>
        <table style="page-break-inside: avoid;">
            <tbody>
                <tr>
                    <th style="width: 50%;">{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}</th>
                    <td class="text-right">{{ number_format($total_paid_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_due'] ?? 'Total Due' }}</th>
                    <td class="text-right">{{ number_format($total_due_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Salary Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; page-break-after: avoid;">{{ $getCurrentTranslation['salary_summary'] ?? 'Salary Summary' }}</h3>
        <table style="page-break-inside: avoid;">
            <tbody>
                <tr>
                    <th style="width: 50%;">{{ $getCurrentTranslation['total_salary_count'] ?? 'Total Salary Count' }}</th>
                    <td class="text-center">{{ $total_salary_count }}</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_salary_amount'] ?? 'Total Salary Amount' }}</th>
                    <td class="text-right" style="color: #dc3545;">-{{ number_format($total_salary_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_partial_salary'] ?? 'Total Partial Salary' }}</th>
                    <td class="text-right" style="color: #ffc107;">-{{ number_format($total_partial_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_paid_salary'] ?? 'Total Paid Salary' }}</th>
                    <td class="text-right" style="color: #28a745;">-{{ number_format($total_paid_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_unpaid_salary'] ?? 'Total Unpaid Salary' }}</th>
                    <td class="text-right" style="color: #dc3545;">-{{ number_format($total_unpaid_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Expense Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; page-break-after: avoid;">{{ $getCurrentTranslation['expense_summary'] ?? 'Expense Summary' }}</h3>
        <table style="page-break-inside: avoid;">
            <tbody>
                <tr>
                    <th style="width: 50%;">{{ $getCurrentTranslation['total_expense_count'] ?? 'Total Expense Count' }}</th>
                    <td class="text-center">{{ $total_expense_count }}</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_expense_amount'] ?? 'Total Expense Amount' }}</th>
                    <td class="text-right" style="color: #ffc107;">-{{ number_format($total_expense_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_paid_expense'] ?? 'Total Paid Expense' }}</th>
                    <td class="text-right" style="color: #28a745;">-{{ number_format($total_paid_expense, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th>{{ $getCurrentTranslation['total_unpaid_expense'] ?? 'Total Unpaid Expense' }}</th>
                    <td class="text-right" style="color: #dc3545;">-{{ number_format($total_unpaid_expense, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>


    <div class="footer">
        <p>{{ $getCurrentTranslation['report_generated_by'] ?? 'Report Generated By' }}: {{ Auth::user()->name ?? 'System' }}</p>
        <p>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
