@php
    $t = $translations ?? [];
    $label = function($key, $fallback = null) use ($t) {
        return $t[$key] ?? $fallback ?? $key;
    };
    $mainFlights = $ticket->allFlights->filter(fn($f) => is_null($f->parent_id))->values();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $label('ticket', 'Ticket') }} – {{ $ticket->reservation_number ?? 'N/A' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(145deg, #f0f4ff 0%, #e8eeff 50%, #f5f7fa 100%);
            min-height: 100vh;
            color: #1a1d29;
            padding: 24px 16px;
            line-height: 1.5;
        }
        .container { max-width: 720px; margin: 0 auto; }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
            padding: 28px 32px;
            margin-bottom: 20px;
        }
        .card-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6366f1;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eef0ff;
        }
        .header-row {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
        }
        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        .logo-wrap img { max-height: 44px; width: auto; }
        .company-name { font-weight: 700; font-size: 18px; color: #1a1d29; }
        .booking-badge {
            font-size: 14px;
            font-weight: 600;
            color: #6366f1;
        }
        .booking-id { font-size: 15px; font-weight: 600; color: #374151; }
        .status-pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-on-hold { background: #fef3c7; color: #b45309; }
        .status-processing { background: #dbeafe; color: #1d4ed8; }
        .status-confirmed { background: #d1fae5; color: #047857; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }
        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px 32px;
            margin-top: 12px;
            font-size: 14px;
            color: #4b5563;
        }
        .meta-row strong { color: #374151; margin-right: 6px; }
        .flight-block {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            background: #fafbfc;
        }
        .flight-block.has-transits { margin-bottom: 0; }
        .flight-block:last-child { margin-bottom: 0; }
        .flight-route {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px 24px;
            margin-bottom: 12px;
        }
        .city { font-size: 18px; font-weight: 700; color: #1a1d29; }
        .time { font-size: 15px; color: #4b5563; }
        .date { font-size: 13px; color: #6b7280; }
        .flight-arrow {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        .airline-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e5e7eb;
        }
        .airline-row img { max-height: 28px; width: auto; }
        .airline-name { font-weight: 600; font-size: 14px; color: #374151; }
        .flight-journey {
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .flight-journey:last-child { margin-bottom: 0; }
        .transit-note {
            text-align: center;
            padding: 10px 16px;
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            background: #f8fafc;
            border-top: 1px dashed #e5e7eb;
            border-bottom: 1px dashed #e5e7eb;
        }
        .transit-block {
            background: #f8fafc;
            border-left: 4px solid #94a3b8;
            margin: 0;
            border-radius: 0;
        }
        .transit-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
            margin-bottom: 8px;
        }
        .passenger-table { width: 100%; border-collapse: collapse; }
        .passenger-table th {
            text-align: left;
            padding: 12px 14px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #6b7280;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        .passenger-table td {
            padding: 14px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #374151;
        }
        .passenger-table tr:last-child td { border-bottom: 0; }
        .fare-table { width: 100%; border-collapse: collapse; }
        .fare-table th, .fare-table td {
            padding: 10px 14px;
            text-align: left;
            font-size: 14px;
        }
        .fare-table th { color: #6b7280; font-weight: 600; }
        .fare-table .total-row { font-weight: 700; font-size: 15px; color: #1a1d29; background: #f8fafc; }
        .text-muted { color: #6b7280; }

        /* Table scroll wrapper for mobile */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Mobile responsive */
        @media (max-width: 768px) {
            body { padding: 12px 10px; font-size: 15px; }
            .container { max-width: 100%; padding: 0 4px; }
            .card { padding: 18px 14px; margin-bottom: 14px; border-radius: 12px; }
            .card-title { font-size: 12px; margin-bottom: 12px; }
            .header-row { flex-direction: column; align-items: flex-start; gap: 14px; margin-bottom: 16px; }
            .header-row > div:last-child { text-align: left; width: 100%; }
            .logo-wrap { gap: 10px; }
            .logo-wrap img { max-height: 36px; }
            .company-name { font-size: 16px; }
            .booking-id { font-size: 14px; word-break: break-all; }
            .meta-row { gap: 5px 20px; font-size: 13px; flex-direction: column; }
            .flight-journey { margin-bottom: 18px; border-radius: 10px; }
            .flight-block { padding: 14px 12px; margin-bottom: 12px; border-radius: 10px; }
            .flight-route {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 10px;
            }
            .flight-route > div:first-child,
            .flight-route > div:last-child { width: 100%; }
            .flight-arrow {
                width: 100%;
                padding: 6px 0;
                margin: 4px 0;
                border-top: 1px dashed #e5e7eb;
                border-bottom: 1px dashed #e5e7eb;
                font-size: 12px;
                justify-content: center;
            }
            .city { font-size: 16px; }
            .time { font-size: 14px; }
            .date { font-size: 12px; }
            .airline-row { margin-top: 10px; padding-top: 10px; flex-wrap: wrap; gap: 8px; }
            .airline-name { font-size: 13px; }
            .transit-note { padding: 8px 12px; font-size: 11px; }
            .transit-badge { font-size: 10px; }
            .passenger-table,
            .fare-table { font-size: 13px; display: block; }
            .passenger-table thead,
            .fare-table thead { display: none; }
            .passenger-table tr,
            .fare-table tr { display: block; border-bottom: 1px solid #e5e7eb; padding: 12px 0; }
            .passenger-table tr:last-child,
            .fare-table tr.total-row { border-bottom: none; }
            .passenger-table td,
            .fare-table td { display: block; padding: 4px 0 8px; border: none; }
            .passenger-table td:first-child,
            .fare-table td:first-child { padding-top: 0; font-weight: 600; color: #1a1d29; }
            .passenger-table td::before,
            .fare-table td::before {
                content: attr(data-label);
                display: block;
                font-size: 11px;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #6b7280;
                margin-bottom: 2px;
            }
            .passenger-table td:first-child::before { content: ''; }
            .fare-table .total-row td { padding: 10px 0 4px; }
            .fare-table .total-row td::before { font-weight: 600; }
            .fare-table .total-row td.total-label-cell { display: none; }
            tbody{
                display: block
            }
        }

        @media (max-width: 480px) {
            body { padding: 10px 8px; }
            .card { padding: 14px 12px; }
            .company-name { font-size: 15px; }
            .city { font-size: 15px; }
            .flight-block { padding: 12px 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header: logo, company, booking id, status --}}
        <div class="card">
            <div class="header-row">
                <div class="logo-wrap">
                    @if($ticket->user && $ticket->user->company)
                        @if($ticket->user->company->dark_logo_url ?? null)
                            <img src="{{ $ticket->user->company->dark_logo_url }}" alt="">
                        @endif
                        <div>
                            <div class="company-name">{{ $ticket->user->company->company_name ?? '—' }}</div>
                            @if($ticket->user->company->tagline ?? null)
                                <div class="text-muted" style="font-size: 13px;">{{ $ticket->user->company->tagline }}</div>
                            @endif
                        </div>
                    @else
                        <div class="company-name">—</div>
                    @endif
                </div>
                <div style="text-align: right;">
                    <div class="booking-badge">{{ $ticket->booking_type ?? 'e-Ticket' }}</div>
                    <div class="booking-id">{{ $label('booking_id', 'Booking ID') }}: {{ $ticket->reservation_number ?? 'N/A' }}</div>
                    @php
                        $status = $ticket->booking_status ?? '';
                        $statusClass = 'status-on-hold';
                        $statusText = $status;
                        if ($status === 'On Hold') { $statusClass = 'status-on-hold'; $statusText = $t['on_hold'] ?? 'On Hold'; }
                        elseif ($status === 'Processing') { $statusClass = 'status-processing'; $statusText = $t['processing'] ?? 'Processing'; }
                        elseif ($status === 'Confirmed') { $statusClass = 'status-confirmed'; $statusText = $t['confirmed'] ?? 'Confirmed'; }
                        elseif ($status === 'Cancelled') { $statusClass = 'status-cancelled'; $statusText = $t['cancelled'] ?? 'Cancelled'; }
                        else { $statusText = $t['default'] ?? $status ?: '—'; }
                    @endphp
                    <div style="margin-top: 8px;"><span class="status-pill {{ $statusClass }}">{{ $statusText }}</span></div>
                </div>
            </div>
            <div class="meta-row">
                <span><strong>{{ $label('trip_type', 'Trip') }}:</strong> {{ $ticket->trip_type ?? '—' }}</span>
                <span><strong>{{ $label('ticket_type_label', 'Class') }}:</strong> {{ $ticket->ticket_type ?? '—' }}</span>
                @if($ticket->invoice_date)
                    <span><strong>{{ $label('issue_date_label', 'Issue date') }}:</strong> {{ \Carbon\Carbon::parse($ticket->invoice_date)->format('d M Y') }}</span>
                @endif
            </div>
        </div>

        {{-- Flight itineraries: each main flight with its transits grouped under it --}}
        @if($mainFlights->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ $label('flight_itineraries', 'Flight itineraries') }}</div>
                @foreach($mainFlights as $flight)
                    <div class="flight-journey">
                        {{-- Main flight segment --}}
                        @php $transits = $flight->transits ?? collect(); @endphp
                        <div class="flight-block{{ $transits->isNotEmpty() ? ' has-transits' : '' }}">
                            <div class="flight-route">
                                <div>
                                    <div class="city">{{ extractPrimaryCity($flight->leaving_from) }}</div>
                                    <div class="time">{{ $flight->departure_date_time ? \Carbon\Carbon::parse($flight->departure_date_time)->format('H:i') : '—' }}</div>
                                    <div class="date">{{ $flight->departure_date_time ? \Carbon\Carbon::parse($flight->departure_date_time)->format('d M Y') : '—' }}</div>
                                </div>
                                <div class="flight-arrow">
                                    <span>{{ $flight->total_fly_time ?? '—' }}</span>
                                </div>
                                <div>
                                    <div class="city">{{ extractPrimaryCity($flight->going_to) }}</div>
                                    <div class="time">{{ $flight->arrival_date_time ? \Carbon\Carbon::parse($flight->arrival_date_time)->format('H:i') : '—' }}</div>
                                    <div class="date">{{ $flight->arrival_date_time ? \Carbon\Carbon::parse($flight->arrival_date_time)->format('d M Y') : '—' }}</div>
                                </div>
                            </div>
                            <div class="airline-row">
                                @if($flight->airline && ($flight->airline->logo_url ?? null))
                                    <img src="{{ $flight->airline->logo_url }}" alt="">
                                @endif
                                <span class="airline-name">{{ $flight->airline->name ?? '—' }} · {{ $flight->flight_number ?? '—' }}</span>
                            </div>
                        </div>
                        {{-- Transits under this flight (if any) --}}
                        @if($transits->isNotEmpty())
                            @foreach($transits as $transit)
                                <div class="transit-note">{{ $label('transit_time', 'Transit') }} {{ $transit->total_transit_time ?? '' }}</div>
                                <div class="flight-block transit-block">
                                    <span class="transit-badge">{{ $label('transit_time', 'Transit') }}</span>
                                    <div class="flight-route">
                                        <div>
                                            <div class="city">{{ extractPrimaryCity($transit->leaving_from) }}</div>
                                            <div class="time">{{ $transit->departure_date_time ? \Carbon\Carbon::parse($transit->departure_date_time)->format('H:i') : '—' }}</div>
                                            <div class="date">{{ $transit->departure_date_time ? \Carbon\Carbon::parse($transit->departure_date_time)->format('d M Y') : '—' }}</div>
                                        </div>
                                        <div class="flight-arrow"><span>{{ $transit->total_fly_time ?? '—' }}</span></div>
                                        <div>
                                            <div class="city">{{ extractPrimaryCity($transit->going_to) }}</div>
                                            <div class="time">{{ $transit->arrival_date_time ? \Carbon\Carbon::parse($transit->arrival_date_time)->format('H:i') : '—' }}</div>
                                            <div class="date">{{ $transit->arrival_date_time ? \Carbon\Carbon::parse($transit->arrival_date_time)->format('d M Y') : '—' }}</div>
                                        </div>
                                    </div>
                                    <div class="airline-row">
                                        @if($transit->airline && ($transit->airline->logo_url ?? null))
                                            <img src="{{ $transit->airline->logo_url }}" alt="">
                                        @endif
                                        <span class="airline-name">{{ $transit->airline->name ?? '—' }} · {{ $transit->flight_number ?? '—' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Passengers --}}
        @if($ticket->passengers && $ticket->passengers->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ $label('passenger_details', 'Passenger details') }} ({{ $ticket->passengers->count() }})</div>
                <div class="table-wrap">
                    <table class="passenger-table">
                        <thead>
                            <tr>
                                <th>{{ $label('passenger_name_label', 'Passenger') }}</th>
                                <th>{{ $label('ticket_info', 'Ticket info') }}</th>
                                <th>{{ $label('baggage_allowance_label', 'Baggage') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticket->passengers as $pax)
                                <tr>
                                    <td data-label="{{ $label('passenger_name_label', 'Passenger') }}">
                                        {{ $pax->name ?? '—' }}
                                        @if($pax->gender) <span class="text-muted">({{ $pax->gender }})</span> @endif
                                        @if($pax->pax_type) <span class="text-muted">, {{ $pax->pax_type }}</span> @endif
                                        <br><span class="text-muted" style="font-size: 13px;">{{ $ticket->ticket_type ?? '—' }}</span>
                                    </td>
                                    <td data-label="{{ $label('ticket_info', 'Ticket info') }}">
                                        @if($pax->flights && $pax->flights->isNotEmpty())
                                            @foreach($pax->flights as $pf)
                                                <div><strong>{{ $label('a_pnr', 'A-PNR') }}:</strong> {{ $pf->airlines_pnr ?? '—' }}</div>
                                                @if($pf->ticket_number)
                                                    <div><strong>{{ $label('ticket_number', 'Ticket No') }}:</strong> {{ $pf->ticket_number }}</div>
                                                @endif
                                            @endforeach
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="{{ $label('baggage_allowance_label', 'Baggage') }}">{!! nl2br(e($pax->baggage_allowance ?? '—')) !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Fare summary --}}
        @if($ticket->fareSummary && $ticket->fareSummary->isNotEmpty())
            @php
                $currency = $ticket->user && $ticket->user->company && $ticket->user->company->currency
                    ? $ticket->user->company->currency->short_name
                    : 'USD';
                $firstFare = $ticket->fareSummary->first();
            @endphp
            <div class="card">
                <div class="card-title">{{ $label('invoice_summary', 'Fare summary') }}</div>
                <div class="table-wrap">
                    <table class="fare-table">
                        <thead>
                            <tr>
                                <th>{{ $label('pax_type_label', 'Pax type') }}</th>
                                <th>{{ $label('base_fare', 'Base fare') }}</th>
                                <th>{{ $label('pax_count_label', 'Count') }}</th>
                                <th>{{ $label('amount', 'Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ticket->fareSummary as $fare)
                                <tr>
                                    <td data-label="{{ $label('pax_type_label', 'Pax type') }}">{{ $fare->pax_type ?? '—' }}</td>
                                    <td data-label="{{ $label('base_fare', 'Base fare') }}">{{ $currency }} {{ number_format($fare->unit_price ?? 0, 2) }}</td>
                                    <td data-label="{{ $label('pax_count_label', 'Count') }}">{{ $fare->pax_count ?? 0 }}</td>
                                    <td data-label="{{ $label('amount', 'Amount') }}">{{ $currency }} {{ number_format($fare->total ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td colspan="2" data-label=""></td>
                                <td class="total-label-cell">{{ $label('subtotal_label', 'Subtotal') }}</td>
                                <td data-label="{{ $label('subtotal_label', 'Subtotal') }}">{{ $currency }} {{ number_format($firstFare->subtotal ?? 0, 2) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="2" data-label=""></td>
                                <td class="total-label-cell">{{ $label('discount_label', 'Discount') }} (-)</td>
                                <td data-label="{{ $label('discount_label', 'Discount') }}">{{ $currency }} {{ number_format($firstFare->discount ?? 0, 2) }}</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="2" data-label=""></td>
                                <td class="total-label-cell">{{ $label('grandtotal_label', 'Grand total') }}</td>
                                <td data-label="{{ $label('grandtotal_label', 'Grand total') }}">{{ $currency }} {{ number_format($firstFare->grandtotal ?? 0, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
