<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .segment-header { background: #e9ecef; font-weight: bold; }
        .col-match { text-align: center; }
    </style>
</head>
<body>
<h2>{{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }} – {{ $payment->payment_invoice_id ?? 'N/A' }}</h2>
@php
    $liveSegments = $liveData ?? [];
    $segIdx = 0;
@endphp
@if(empty($systemSegments))
    <p>{{ $getCurrentTranslation['no_flight_data'] ?? 'No flight data in system.' }}</p>
@else
    @foreach($systemSegments as $seg)
        @php
            $liveDep = $liveSegments[$segIdx]['departure'] ?? null;
            $liveArr = $liveSegments[$segIdx]['arrival'] ?? null;
            $sysDep = $seg['departure_date_time'] ? \Carbon\Carbon::parse($seg['departure_date_time'])->format('Y-m-d H:i') : 'N/A';
            $sysArr = $seg['arrival_date_time'] ? \Carbon\Carbon::parse($seg['arrival_date_time'])->format('Y-m-d H:i') : 'N/A';
            $liveDepStr = null;
            $liveArrStr = null;
            if ($liveDep) {
                $liveDepStr = $liveDep['departureDateTime'] ?? $liveDep['scheduledTime'] ?? null;
                if ($liveDepStr) $liveDepStr = \Carbon\Carbon::parse($liveDepStr)->format('Y-m-d H:i');
            }
            if ($liveArr) {
                $liveArrStr = $liveArr['arrivalDateTime'] ?? $liveArr['scheduledTime'] ?? null;
                if ($liveArrStr) $liveArrStr = \Carbon\Carbon::parse($liveArrStr)->format('Y-m-d H:i');
            }
            $depMatch = $liveDepStr ? ($sysDep === $liveDepStr) : null;
            $arrMatch = $liveArrStr ? ($sysArr === $liveArrStr) : null;
        @endphp
        <table>
            <tr class="segment-header">
                <td colspan="4">{{ $seg['airline'] ?? 'N/A' }} {{ $seg['flight_number'] ?? '' }} @if($seg['is_transit'] ?? false) ({{ $getCurrentTranslation['transit'] ?? 'Transit' }}) @endif</td>
            </tr>
            <tr>
                <th>{{ $getCurrentTranslation['field'] ?? 'Field' }}</th>
                <th>{{ $getCurrentTranslation['system_data'] ?? 'System' }}</th>
                <th>{{ $getCurrentTranslation['live_status_data'] ?? 'Live' }}</th>
                <th class="col-match">{{ $getCurrentTranslation['match'] ?? 'Match' }}</th>
            </tr>
            <tr>
                <td>{{ $getCurrentTranslation['departure_label'] ?? 'Departure' }}</td>
                <td>{{ $sysDep }}</td>
                <td>{{ $liveDepStr ?? '—' }}</td>
                <td class="col-match">@if($depMatch === true) ✓ @elseif($depMatch === false) ✗ @else — @endif</td>
            </tr>
            <tr>
                <td>{{ $getCurrentTranslation['arrival_label'] ?? 'Arrival' }}</td>
                <td>{{ $sysArr }}</td>
                <td>{{ $liveArrStr ?? '—' }}</td>
                <td class="col-match">@if($arrMatch === true) ✓ @elseif($arrMatch === false) ✗ @else — @endif</td>
            </tr>
        </table>
        @if($liveDep || $liveArr)
            @php $segIdx++; @endphp
        @endif
    @endforeach
@endif
</body>
</html>
