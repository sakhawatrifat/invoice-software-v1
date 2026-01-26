<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $getCurrentTranslation['gross_profit_loss_report'] ?? 'Gross Profit Loss Report' }}</title>
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
            <h1 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['gross_profit_loss_report'] ?? 'Gross Profit Loss Report')) }};">{{ $getCurrentTranslation['gross_profit_loss_report'] ?? 'Gross Profit Loss Report' }}</h1>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['invoice_date_range'] ?? 'Invoice Date Range')) }};">{{ $getCurrentTranslation['invoice_date_range'] ?? 'Invoice Date Range' }}:</strong> <span style="font-family: {{ language_font(strip_tags($invoiceDateRangeStr)) }};">{{ $invoiceDateRangeStr }}</span></p>
            <p><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</strong> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
        </div>
    </div>

    {{-- Profit/Loss Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; font-family: {{ language_font(strip_tags($getCurrentTranslation['profit_loss_summary'] ?? 'Profit/Loss Summary')) }};">{{ $getCurrentTranslation['profit_loss_summary'] ?? 'Profit/Loss Summary' }}</h3>
        <table>
            <tbody>
                <tr>
                    <th style="width: 50%; font-family: {{ language_font(strip_tags($getCurrentTranslation['total_purchase'] ?? 'Total Purchase')) }};">{{ $getCurrentTranslation['total_purchase'] ?? 'Total Purchase' }}</th>
                    <td class="text-right" style="font-family: arial;">{{ number_format($total_purchase_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_selling'] ?? 'Total Selling')) }};">{{ $getCurrentTranslation['total_selling'] ?? 'Total Selling' }}</th>
                    <td class="text-right" style="font-family: arial;">{{ number_format($total_selling_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @php
                    $isProfit = $total_profit >= 0;
                    $profitLossLabel = $isProfit ? ($getCurrentTranslation['total_profit'] ?? 'Total Profit') : ($getCurrentTranslation['total_loss'] ?? 'Total Loss');
                    $profitLossClass = $isProfit ? 'color: #28a745;' : 'color: #dc3545;';
                    $profitLossValue = $isProfit ? number_format($total_profit, 2) : '-' . number_format(abs($total_profit), 2);
                @endphp
                <tr style="font-weight: bold; {{ $profitLossClass }}">
                    <th style="font-family: {{ language_font(strip_tags($profitLossLabel)) }};">{{ $profitLossLabel }}</th>
                    <td class="text-right" style="font-family: arial;">{{ $profitLossValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_cancellation_fee'] ?? 'Total Cancellation Fee')) }};">{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'Total Cancellation Fee' }}</th>
                    <td class="text-right" style="font-family: arial;">-{{ number_format($total_cancellation_fee, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @php
                    $isProfitAfterRefund = $total_profit_after_refund >= 0;
                    $profitLossAfterRefundLabel = $isProfitAfterRefund ? ($getCurrentTranslation['total_profit_after_refund'] ?? 'Total Profit After Refund') : ($getCurrentTranslation['total_loss_after_refund'] ?? 'Total Loss After Refund');
                    $profitLossAfterRefundClass = $isProfitAfterRefund ? 'color: #28a745;' : 'color: #dc3545;';
                    $profitLossAfterRefundValue = $isProfitAfterRefund ? number_format($total_profit_after_refund, 2) : '-' . number_format(abs($total_profit_after_refund), 2);
                @endphp
                <tr style="font-weight: bold; {{ $profitLossAfterRefundClass }}">
                    <th style="font-family: {{ language_font(strip_tags($profitLossAfterRefundLabel)) }};">{{ $profitLossAfterRefundLabel }}</th>
                    <td class="text-right" style="font-family: arial;">{{ $profitLossAfterRefundValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Paid and Due Summary --}}
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; font-family: {{ language_font(strip_tags($getCurrentTranslation['paid_and_due_summary'] ?? 'Paid and Due Summary')) }};">{{ $getCurrentTranslation['paid_and_due_summary'] ?? 'Paid and Due Summary' }}</h3>
        <table>
            <tbody>
                <tr>
                    <th style="width: 50%; font-family: {{ language_font(strip_tags($getCurrentTranslation['total_paid'] ?? 'Total Paid')) }};">{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}</th>
                    <td class="text-right" style="font-family: arial;">{{ number_format($total_paid_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                <tr>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_due'] ?? 'Total Due')) }};">{{ $getCurrentTranslation['total_due'] ?? 'Total Due' }}</th>
                    <td class="text-right" style="font-family: arial;">{{ number_format($total_due_amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Payment Status Summary --}}
    @if(isset($paymentStatusSummary) && $paymentStatusSummary->count() > 0)
    <div class="summary-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; font-family: {{ language_font(strip_tags($getCurrentTranslation['summary_by_payment_status'] ?? 'Summary by Payment Status')) }};">{{ $getCurrentTranslation['summary_by_payment_status'] ?? 'Summary by Payment Status' }}</h3>
        <table>
            <thead>
                <tr>
                    <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['payment_status'] ?? 'Payment Status')) }};">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
                    <th class="text-center" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_count'] ?? 'Count')) }};">{{ $getCurrentTranslation['total_count'] ?? 'Count' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_purchase_amount'] ?? 'Purchase')) }};">{{ $getCurrentTranslation['total_purchase_amount'] ?? 'Purchase' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_selling_amount'] ?? 'Selling')) }};">{{ $getCurrentTranslation['total_selling_amount'] ?? 'Selling' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_cancellation_fee'] ?? 'Cancellation Fee')) }};">{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'Cancellation Fee' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_profit_loss'] ?? 'Profit/Loss')) }};">{{ $getCurrentTranslation['total_profit_loss'] ?? 'Profit/Loss' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_paid'] ?? 'Paid')) }};">{{ $getCurrentTranslation['total_paid'] ?? 'Paid' }}</th>
                    <th class="text-right" style="font-family: {{ language_font(strip_tags($getCurrentTranslation['total_due'] ?? 'Due')) }};">{{ $getCurrentTranslation['total_due'] ?? 'Due' }}</th>
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
                    <td style="font-family: {{ language_font(strip_tags($status)) }};">{{ $status }}</td>
                    <td class="text-center" style="font-family: arial;">{{ $data['count'] }}</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($data['total_purchase_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($data['total_selling_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($data['total_cancellation_fee'] ?? 0, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="{{ $profitLossStyle }} font-family: arial;">{{ $profitLossValue }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($data['total_paid_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                    <td class="text-right" style="font-family: arial;">{{ number_format($data['total_due_amount'], 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p><span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['report_generated_by'] ?? 'Report Generated By')) }};">{{ $getCurrentTranslation['report_generated_by'] ?? 'Report Generated By' }}:</span> <span style="font-family: {{ language_font(strip_tags(Auth::user()->name ?? 'System')) }};">{{ Auth::user()->name ?? 'System' }}</span></p>
        <p><span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['generated_at'] ?? 'Generated At')) }};">{{ $getCurrentTranslation['generated_at'] ?? 'Generated At' }}:</span> <span style="font-family: arial;">{{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</span></p>
    </div>
</body>
</html>
