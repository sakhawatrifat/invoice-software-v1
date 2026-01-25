<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['gross_profit_loss_report'] ?? 'Gross Profit Loss Report' }}</title>
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
            <h1>{{ $getCurrentTranslation['gross_profit_loss_report'] ?? 'Gross Profit Loss Report' }}</h1>
            <p><strong>{{ $getCurrentTranslation['invoice_date_range'] ?? 'Invoice Date Range' }}:</strong> {{ $invoiceDateRangeStr }}</p>
            <p><strong>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    {{-- Profit/Loss Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['profit_loss_summary'] ?? 'Profit/Loss Summary' }}</h3>
        <table>
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
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['paid_and_due_summary'] ?? 'Paid and Due Summary' }}</h3>
        <table>
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

    {{-- Payment Status Summary --}}
    @if(isset($paymentStatusSummary) && $paymentStatusSummary->count() > 0)
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px;">{{ $getCurrentTranslation['summary_by_payment_status'] ?? 'Summary by Payment Status' }}</h3>
        <table>
            <thead>
                <tr>
                    <th>{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
                    <th class="text-center">{{ $getCurrentTranslation['total_count'] ?? 'Count' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['total_purchase_amount'] ?? 'Purchase' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['total_selling_amount'] ?? 'Selling' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'Cancellation Fee' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['total_profit_loss'] ?? 'Profit/Loss' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['total_paid'] ?? 'Paid' }}</th>
                    <th class="text-right">{{ $getCurrentTranslation['total_due'] ?? 'Due' }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($paymentStatusSummary as $status => $data)
                @php
                    $netProfit = $data['total_profit'] - ($data['total_cancellation_fee'] ?? 0);
                    $isProfit = $netProfit >= 0;
                    $profitLossValue = $isProfit ? number_format($netProfit, 2) : '-' . number_format(abs($netProfit), 2);
                    $profitLossStyle = $isProfit ? 'color: #28a745;' : 'color: #dc3545;';
                @endphp
                <tr>
                    <td>{{ $status }}</td>
                    <td class="text-center">{{ $data['count'] }}</td>
                    <td class="text-right">{{ number_format($data['total_purchase_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right">{{ number_format($data['total_selling_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right">{{ number_format($data['total_cancellation_fee'] ?? 0, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="{{ $profitLossStyle }}">{{ $profitLossValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right">{{ number_format($data['total_paid_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right">{{ number_format($data['total_due_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>{{ $getCurrentTranslation['report_generated_by'] ?? 'Report Generated By' }}: {{ Auth::user()->name ?? 'System' }}</p>
        <p>{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
