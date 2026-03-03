@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'Dashboard' }}</a> &nbsp; - 
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ $flightListUrl }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['full_flight_list'] ?? 'Full Flight List' }}</a> &nbsp; - 
                    </li>
                    <li class="breadcrumb-item">{{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <button type="button" class="btn btn-sm fw-bold btn-info" id="btn-refresh-flight-status" title="{{ $getCurrentTranslation['reload_to_check_flight_status'] ?? 'Reload to check flight status again' }}">
                    <i class="fa-solid fa-rotate-right"></i> {{ $getCurrentTranslation['refresh_flight_status'] ?? 'Refresh flight status' }}
                </button>
                <a href="{{ $flightListUrl }}" class="btn btn-sm fw-bold btn-primary">
                    <i class="fa-solid fa-arrow-left"></i> {{ $getCurrentTranslation['back_to_list'] ?? 'Back to list' }}
                </a>
                @if($payment->ticket && hasPermission('ticket.edit'))
                    <a href="{{ route('ticket.edit', $payment->ticket->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i> {{ $getCurrentTranslation['edit_ticket'] ?? 'Edit ticket' }}
                    </a>
                @endif
                <a href="{{ route('payment.show', $payment->id) }}" class="btn btn-sm fw-bold btn-primary">
                    <i class="fa-solid fa-pager"></i> {{ $getCurrentTranslation['payment_details'] ?? 'Payment Details' }}
                </a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card rounded border shadow-sm mb-5">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }} – {{ collect($systemSegments ?? [])->pluck('flight_number')->filter()->unique()->values()->implode(', ') ?: ($payment->payment_invoice_id ?? 'N/A') }}</h3>
                </div>
                <div class="card-body">
                    @if($trackError)
                        <div class="alert alert-warning">
                            <strong>{{ $getCurrentTranslation['live_status_unavailable'] ?? 'Live status unavailable' }}:</strong> {{ $trackError }}
                        </div>
                    @endif

                    {{-- System vs Live comparison section --}}
                    <div class="flight-comparison-section">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h5 class="mb-0">{{ $getCurrentTranslation['system_data'] ?? 'System Data' }} vs {{ $getCurrentTranslation['live_status_data'] ?? 'Live Status Data' }}</h5>
                            <span class="badge badge-light-info fs-8" data-bs-toggle="tooltip" title="{{ $getCurrentTranslation['comparison_legend_tooltip'] ?? 'Compare your saved flight times with current airline data.' }}">
                                <i class="fa-solid fa-circle-info"></i>
                            </span>
                        </div>
                        <div class="alert alert-light-info border border-info border-dashed d-flex flex-wrap align-items-start gap-3 mb-4 py-3">
                            <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                                <span class="text-info mt-1"><i class="fa-solid fa-database fa-lg"></i></span>
                                <div>
                                    <strong class="text-gray-800">{{ $getCurrentTranslation['system_data'] ?? 'System Data' }}</strong>
                                    <span class="text-muted d-block small">{{ $getCurrentTranslation['system_data_desc'] ?? 'Flight dates/times stored in your system when the booking was made.' }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                                <span class="text-primary mt-1"><i class="fa-solid fa-satellite-dish fa-lg"></i></span>
                                <div>
                                    <strong class="text-gray-800">{{ $getCurrentTranslation['live_status_data'] ?? 'Live Status Data' }}</strong>
                                    <span class="text-muted d-block small">{{ $getCurrentTranslation['live_data_desc'] ?? 'Current schedule from the airline; may change due to delays or reschedules.' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="d-inline-flex align-items-center gap-1 small text-muted">
                                <span class="badge bg-success">✓</span> {{ $getCurrentTranslation['match_yes_legend'] ?? 'Match' }}
                            </span>
                            <span class="d-inline-flex align-items-center gap-1 small text-muted">
                                <span class="badge bg-danger">✗</span> {{ $getCurrentTranslation['match_no_legend'] ?? 'Different' }}
                            </span>
                            <span class="d-inline-flex align-items-center gap-1 small text-muted">
                                <span class="bg-warning-subtle px-2 py-1 rounded">...</span> {{ $getCurrentTranslation['highlighted_diff'] ?? 'Highlighted = difference' }}
                            </span>
                        </div>

                        <style>
                            .flight-comparison-section .segment-card { border-left: 4px solid var(--bs-primary); }
                            .flight-comparison-section .segment-card.transit { border-left-color: var(--bs-secondary); }
                            .flight-comparison-section .col-system { background-color: rgba(var(--bs-info-rgb), 0.06); }
                            .flight-comparison-section .col-live { background-color: rgba(var(--bs-primary-rgb), 0.06); }
                            .flight-comparison-section .bg-yellow-marker { background-color: #fff3cd !important; border-radius: 4px; padding: 2px 6px; }
                            .flight-comparison-section .comparison-row { border-bottom: 1px solid #eee; }
                            .flight-comparison-section .comparison-row:last-child { border-bottom: none; }
                            .bg-warning-subtle { background-color: #fff3cd; }
                        </style>

                        @php
                            $segIdx = 0;
                            $liveSegments = $liveData ?? [];
                        @endphp

                        @if(empty($systemSegments))
                            <div class="text-muted py-4 text-center">{{ $getCurrentTranslation['no_flight_data'] ?? 'No flight data in system.' }}</div>
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
                                    $depDiffText = null;
                                    $arrDiffText = null;
                                    if ($depMatch === false && $sysDep !== 'N/A' && $liveDepStr) {
                                        try {
                                            $sysDepC = \Carbon\Carbon::parse($seg['departure_date_time']);
                                            $liveDepC = \Carbon\Carbon::parse($liveDepStr);
                                            $depDiffMins = $liveDepC->diffInMinutes($sysDepC, false);
                                            $absM = abs($depDiffMins);
                                            $depDiffText = ($depDiffMins <= 0 ? '+' : '-') . ($absM >= 60 ? (intval($absM / 60) . 'h ' . ($absM % 60) . 'm') : ($absM . 'm'));
                                        } catch (\Throwable $e) { }
                                    }
                                    if ($arrMatch === false && $sysArr !== 'N/A' && $liveArrStr) {
                                        try {
                                            $sysArrC = \Carbon\Carbon::parse($seg['arrival_date_time']);
                                            $liveArrC = \Carbon\Carbon::parse($liveArrStr);
                                            $arrDiffMins = $liveArrC->diffInMinutes($sysArrC, false);
                                            $absM = abs($arrDiffMins);
                                            $arrDiffText = ($arrDiffMins <= 0 ? '+' : '-') . ($absM >= 60 ? (intval($absM / 60) . 'h ' . ($absM % 60) . 'm') : ($absM . 'm'));
                                        } catch (\Throwable $e) { }
                                    }
                                    $sysDepAirport = $seg['leaving_from'] ?? '—';
                                    $sysArrAirport = $seg['going_to'] ?? '—';
                                    $liveDepAirport = null;
                                    $liveArrAirport = null;
                                    if ($liveDep) {
                                        $name = $liveDep['airport'] ?? $liveDep['airportCity'] ?? null;
                                        $code = $liveDep['airportCode'] ?? null;
                                        $term = isset($liveDep['terminal']) && $liveDep['terminal'] !== '' && $liveDep['terminal'] !== null ? ' T-' . trim($liveDep['terminal']) : '';
                                        $liveDepAirport = $name ? (($code && $name !== $code) ? trim($name) . ' (' . $code . ')' . $term : trim($name) . $term) : ($code ? $code . $term : null);
                                    }
                                    if ($liveArr) {
                                        $name = $liveArr['airport'] ?? $liveArr['airportCity'] ?? null;
                                        $code = $liveArr['airportCode'] ?? null;
                                        $term = isset($liveArr['terminal']) && $liveArr['terminal'] !== '' && $liveArr['terminal'] !== null ? ' T-' . trim($liveArr['terminal']) : '';
                                        $liveArrAirport = $name ? (($code && $name !== $code) ? trim($name) . ' (' . $code . ')' . $term : trim($name) . $term) : ($code ? $code . $term : null);
                                    }
                                @endphp
                                <div class="card segment-card {{ $seg['is_transit'] ? 'transit' : '' }} mb-4 overflow-hidden">
                                    <div class="card-header py-2 px-3 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <strong class="text-gray-800">
                                            <i class="fa-solid fa-plane-departure text-gray-500 me-1"></i>
                                            {{ $seg['airline'] ?? 'N/A' }} {{ $seg['flight_number'] ?? '' }}
                                            @if($seg['is_transit'])
                                                <span class="badge badge-secondary ms-1">{{ $getCurrentTranslation['transit'] ?? 'Transit' }}</span>
                                            @endif
                                        </strong>
                                        @if($depMatch === false || $arrMatch === false)
                                            <span class="badge bg-warning text-dark">{{ $getCurrentTranslation['schedule_changed'] ?? 'Schedule changed' }}</span>
                                        @endif
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="row g-0">
                                            <div class="col-12 col-md-3 border-end comparison-row py-2 px-3 bg-light">
                                                <span class="text-muted small text-uppercase fw-semibold">{{ $getCurrentTranslation['field'] ?? 'Field' }}</span>
                                            </div>
                                            <div class="col-12 col-md-3 border-end col-system comparison-row py-2 px-3">
                                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fa-solid fa-database text-info me-1"></i>{{ $getCurrentTranslation['system_data'] ?? 'System' }}</span>
                                            </div>
                                            <div class="col-12 col-md-3 border-end col-live comparison-row py-2 px-3">
                                                <span class="text-muted small text-uppercase fw-semibold d-block mb-1"><i class="fa-solid fa-satellite-dish text-primary me-1"></i>{{ $getCurrentTranslation['live_status_data'] ?? 'Live' }}</span>
                                            </div>
                                            <div class="col-12 col-md-3 comparison-row py-2 px-3 bg-light text-center">
                                                <span class="text-muted small text-uppercase fw-semibold">{{ $getCurrentTranslation['match'] ?? 'Match' }}</span>
                                            </div>
                                        </div>
                                        <div class="row g-0 comparison-row">
                                            <div class="col-12 col-md-3 border-end py-2 px-3 bg-light">{{ $getCurrentTranslation['departure_label'] ?? 'Departure' }}</div>
                                            <div class="col-12 col-md-3 border-end col-system py-2 px-3">
                                                <span class="{{ $depMatch === false ? 'bg-yellow-marker' : '' }}">{{ $sysDep }}</span>
                                                @if($sysDepAirport && $sysDepAirport !== '—')<br><span class="text-muted small">{{ $sysDepAirport }}</span>@endif
                                            </div>
                                            <div class="col-12 col-md-3 border-end col-live py-2 px-3">
                                                <span class="{{ $depMatch === false ? 'bg-yellow-marker' : '' }}">{{ $liveDepStr ?? '—' }}</span>
                                                @if($liveDepAirport)<br><span class="text-muted small">{{ $liveDepAirport }}</span>@endif
                                            </div>
                                            <div class="col-12 col-md-3 py-2 px-3 bg-light text-center">
                                                @if($depMatch === true)<span class="badge bg-success">{{ $getCurrentTranslation['yes'] ?? 'Yes' }}</span>
                                                @elseif($depMatch === false)
                                                    <span class="badge bg-danger">{{ $getCurrentTranslation['no'] ?? 'No' }}</span>
                                                    @if($depDiffText)<br><span class="small text-muted">{{ $depDiffText }}</span>@endif
                                                @else <span class="text-muted">—</span> @endif
                                            </div>
                                        </div>
                                        <div class="row g-0 comparison-row">
                                            <div class="col-12 col-md-3 border-end py-2 px-3 bg-light">{{ $getCurrentTranslation['arrival_label'] ?? 'Arrival' }}</div>
                                            <div class="col-12 col-md-3 border-end col-system py-2 px-3">
                                                <span class="{{ $arrMatch === false ? 'bg-yellow-marker' : '' }}">{{ $sysArr }}</span>
                                                @if($sysArrAirport && $sysArrAirport !== '—')<br><span class="text-muted small">{{ $sysArrAirport }}</span>@endif
                                            </div>
                                            <div class="col-12 col-md-3 border-end col-live py-2 px-3">
                                                <span class="{{ $arrMatch === false ? 'bg-yellow-marker' : '' }}">{{ $liveArrStr ?? '—' }}</span>
                                                @if($liveArrAirport)<br><span class="text-muted small">{{ $liveArrAirport }}</span>@endif
                                            </div>
                                            <div class="col-12 col-md-3 py-2 px-3 bg-light text-center">
                                                @if($arrMatch === true)<span class="badge bg-success">{{ $getCurrentTranslation['yes'] ?? 'Yes' }}</span>
                                                @elseif($arrMatch === false)
                                                    <span class="badge bg-danger">{{ $getCurrentTranslation['no'] ?? 'No' }}</span>
                                                    @if($arrDiffText)<br><span class="small text-muted">{{ $arrDiffText }}</span>@endif
                                                @else <span class="text-muted">—</span> @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if($liveDep || $liveArr)
                                    @php $segIdx++; @endphp
                                @endif
                            @endforeach
                        @endif
                    </div>

                    @if($datetimeMismatch && hasPermission('payment.flight_status'))
                        <div class="alert alert-info mt-4">
                            <strong>{{ $getCurrentTranslation['flight_datetime_changed'] ?? 'Flight date/time has changed.' }}</strong>
                            {{ $getCurrentTranslation['update_flight_data_or_mail'] ?? 'You can update system data from the live API and optionally notify the customer.' }}
                        </div>
                        <div class="d-flex gap-2 flex-wrap mt-3">
                            <button type="button" class="btn btn-primary" id="btn-update-flight-only">
                                <i class="fa-solid fa-refresh"></i> {{ $getCurrentTranslation['update_flight_data'] ?? 'Update flight data' }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card rounded border shadow-sm mb-5">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ $getCurrentTranslation['passengers_with_transit'] ?? 'Passengers & transit data' }}</h5>
                </div>
                <div class="card-body">
                    @forelse($ticket->passengers ?? [] as $pax)
                        <div class="border rounded p-3 mb-3">
                            <strong>{{ $pax->name ?? 'N/A' }}</strong> ({{ $pax->pax_type ?? 'N/A' }})
                            @if($pax->email ?? null)
                                <br><span class="text-muted small">{{ $getCurrentTranslation['email_label'] ?? 'Email' }}: {{ $pax->email }}</span>
                            @endif
                            @if(($pax->flights ?? collect())->isNotEmpty())
                                <table class="table table-sm table-bordered mt-2 mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ $getCurrentTranslation['pnr'] ?? 'PNR' }}</th>
                                            <th>{{ $getCurrentTranslation['ticket_number'] ?? 'Ticket No' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pax->flights as $pf)
                                            <tr>
                                                <td>{{ $pf->airlines_pnr ?? '—' }}</td>
                                                <td>{{ $pf->ticket_number ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ $getCurrentTranslation['no_passengers'] ?? 'No passengers.' }}</p>
                    @endforelse
                </div>
            </div>

            @if(hasPermission('payment.flight_status'))
            @php
                $defaultSubject = $getCurrentTranslation['your_flight_schedule_has_changed'] ?? 'Your Flight Schedule Has Changed';
            @endphp
            <style>
                .customer-name-checkbox-wrap { white-space: nowrap; }
            </style>
            <div class="card rounded border shadow-sm mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $getCurrentTranslation['mail_informations'] ?? 'Mail informations' }} – {{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }} <span class="badge badge-light-info ms-2">{{ $getCurrentTranslation['total_mail_sent'] ?? 'Total sent' }}: {{ $payment->flight_status_mail_count ?? 0 }}</span></h3>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('payment.flight.status.mailSend', $payment->id) }}" enctype="multipart/form-data" id="flight-status-mail-form">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-item mail-content-wrapper">
                                    <label class="form-label mb-0">{{ $getCurrentTranslation['to_email'] ?? 'To (email)' }} <span class="text-danger">*</span></label>
                                    <br>
                                    <select class="form-control select-2-mail" name="to_email[]" multiple="multiple" required>
                                        @if(!empty($customerEmail))
                                            <option value="{{ $customerEmail }}" selected>{{ $customerEmail }}</option>
                                        @endif
                                        @foreach($ticket->passengers ?? [] as $p)
                                            @if(!empty($p->email) && ($p->email ?? '') != ($customerEmail ?? ''))
                                                <option value="{{ $p->email }}">{{ $p->email }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_cc'] ?? 'Select or type email(s)' }}</small>
                                    @error('to_email')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ $getCurrentTranslation['subject'] ?? 'Subject' }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" value="{{ old('subject', $defaultSubject) }}" required maxlength="255">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">{{ $getCurrentTranslation['mail_content_label'] ?? 'Mail content' }}</label>
                                <button type="button" class="btn btn-sm btn-info mb-2" id="btn-load-flight-status-content"><i class="fa-solid fa-download"></i> {{ $getCurrentTranslation['load_content'] ?? 'Load content' }}</button>
                                <textarea class="form-control summernote" name="mail_content" id="flight-status-mail-content" rows="8"></textarea>
                            </div>

                            <hr class="border-top opacity-100">

                            <div class="col-md-12">
                                <div class="form-item mb-5 mail-content-wrapper">
                                    <label class="form-label mb-0">{{ $getCurrentTranslation['cc_emails'] ?? 'cc_emails' }}:</label>
                                    <br>
                                    <small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_cc'] ?? 'select_or_type_cc' }}</small>
                                    <select class="form-control select-2-mail" name="cc_emails[]" multiple="multiple">
                                        @foreach(Auth::user()->company_data->cc_emails ?? [] as $ccItem)
                                            <option value="{{ $ccItem }}" selected>{{ $ccItem }}</option>
                                        @endforeach
                                    </select>
                                    @error('cc_emails')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <hr class="border-top opacity-100">

                            <div class="col-md-12">
                                <div class="form-item mb-5 mail-content-wrapper">
                                    <label class="form-label mb-0">{{ $getCurrentTranslation['bcc_emails'] ?? 'bcc_emails' }}:</label>
                                    <br>
                                    <small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_bcc'] ?? 'select_or_type_bcc' }}</small>
                                    <select class="form-control select-2-mail" name="bcc_emails[]" multiple="multiple">
                                        @foreach(Auth::user()->company_data->bcc_emails ?? [] as $bccItem)
                                            <option value="{{ $bccItem }}" selected>{{ $bccItem }}</option>
                                        @endforeach
                                    </select>
                                    @error('bcc_emails')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <hr class="border-top opacity-100">
                            <div class="col-md-12 mb-3">
                                <input type="hidden" name="document_type_ticket" value="0">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="document_type_ticket" value="1" id="attach_ticket_pdf" checked>
                                    <label class="form-check-label" for="attach_ticket_pdf">{{ $getCurrentTranslation['attach_ticket_pdf'] ?? 'Attach ticket PDF' }}</label>
                                </div>
                            </div>
                            @if(hasPermission('ticket.multiLayout'))
                                @if($payment->ticket && ($payment->ticket->document_type ?? '') == 'ticket')
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label">{{ $getCurrentTranslation['select_ticket_layout'] ?? 'select_ticket_layout' }}:</label>
                                        <div class="d-flex align-items-center form-item mb-4 gap-3">
                                            <div class="ticket-layout-card-outer">
                                                <label class="ticket-layout-card mb-1">
                                                    <input type="radio" class="hidden" name="ticket_layout" value="1" checked>
                                                    <img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-1.png') }}" class="ticket-img" alt="Layout 1">
                                                </label>
                                                <a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-1.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'layout' }} 1</b></a>
                                            </div>
                                            <div class="ticket-layout-card-outer">
                                                <label class="ticket-layout-card mb-1">
                                                    <input type="radio" class="hidden" name="ticket_layout" value="2">
                                                    <img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-2.png') }}" class="ticket-img" alt="Layout 2">
                                                </label>
                                                <a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-2.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'layout' }} 2</b></a>
                                            </div>
                                            <div class="ticket-layout-card-outer">
                                                <label class="ticket-layout-card mb-1">
                                                    <input type="radio" class="hidden" name="ticket_layout" value="3">
                                                    <img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-3.png') }}" class="ticket-img" alt="Layout 3">
                                                </label>
                                                <a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-3.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'layout' }} 3</b></a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_flight_before_send" value="1" id="update_flight_before_send" checked>
                                    <label class="form-check-label" for="update_flight_before_send">{{ $getCurrentTranslation['update_flight_data_to_db_before_send'] ?? 'Update flight data to DB before sending' }}</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary form-submit-btn ajax-submit"><span class="indicator-label"><i class="fa-solid fa-paper-plane me-1"></i> {{ $getCurrentTranslation['send_mail'] ?? 'Send mail' }}</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('script')
<script>
(function() {
    // No dialog, confirm, or alert when reloading via F5 / Ctrl+F5 / browser refresh – page just reloads and uses session.
    var url = "{{ route('payment.flight.status.update', $payment->id) }}";
    var csrf = "{{ csrf_token() }}";
    var confirmTitle = "{{ $getCurrentTranslation['refresh_flight_status'] ?? 'Refresh flight status' }}";
    var confirmText = "{{ $getCurrentTranslation['reload_to_check_flight_status'] ?? 'Reload the page to fetch the latest flight status from the airline API again?' }}";

    function showReloadConfirmSwal(callback) {
        if (typeof Swal === 'undefined') {
            if (confirm(confirmText)) callback();
            return;
        }
        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: "{{ $getCurrentTranslation['yes'] ?? 'Yes' }}",
            cancelButtonText: "{{ $getCurrentTranslation['cancel'] ?? 'Cancel' }}"
        }).then(function(result) {
            if (result.isConfirmed) callback();
        });
    }

    // Only "Refresh flight status" button calls the API; then reload. F5/browser refresh just reload (backend uses session, no API).
    function doRefreshFlightStatus() {
        showReloadConfirmSwal(function() {
            var btn = document.getElementById('btn-refresh-flight-status');
            if (btn) btn.disabled = true;
            if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').show();
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: '_token=' + encodeURIComponent(csrf)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
                if (btn) btn.disabled = false;
                if (data.is_success === 1) {
                    window.location.reload();
                } else {
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.message || 'Error.' }); else alert(data.message || 'Error.');
                }
            })
            .catch(function() {
                if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
                if (btn) btn.disabled = false;
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: 'Request failed.' }); else alert('Request failed.');
            });
        });
    }

    document.getElementById('btn-refresh-flight-status')?.addEventListener('click', doRefreshFlightStatus);

    @if($datetimeMismatch && hasPermission('payment.flight_status'))
    function doUpdate() {
        var btnOnly = document.getElementById('btn-update-flight-only');
        if (btnOnly) btnOnly.disabled = true;
        if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').show();
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: '_token=' + encodeURIComponent(csrf) + '&send_mail=0'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.is_success) {
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', text: data.message || 'Done.' }).then(function() { window.location.reload(); });
                else { alert(data.message || 'Done.'); window.location.reload(); }
            } else {
                if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
                if (btnOnly) btnOnly.disabled = false;
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: data.message || 'Error.' }); else alert(data.message || 'Error.');
            }
        })
        .catch(function() {
            if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
            if (btnOnly) btnOnly.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: 'Request failed.' }); else alert('Request failed.');
        });
    }
    document.getElementById('btn-update-flight-only')?.addEventListener('click', doUpdate);
    @endif

    @if(hasPermission('payment.flight_status'))
    (function() {
        var loadUrl = "{{ route('payment.flight.status.mailContentLoad', $payment->id) }}";
        var csrf = "{{ csrf_token() }}";
        var $content = $('#flight-status-mail-content');
        function loadMailContent() {
            if (!$content.length) return;
            if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').show();
            $.ajax({
                url: loadUrl,
                type: 'POST',
                data: { _token: csrf },
                dataType: 'json',
                success: function(res) {
                    if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
                    if (res.is_success === 1 && res.mail_content) {
                        if ($content.next('.note-editor').length) $content.summernote('destroy');
                        $content.val(res.mail_content);
                        if (typeof initializeSummernote === 'function') initializeSummernote();
                    }
                },
                error: function() { if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide(); }
            });
        }
        $('#btn-load-flight-status-content').on('click', loadMailContent);
        loadMailContent();
    })();
    @endif
})();
</script>
@if(hasPermission('payment.flight_status'))
@include('common._partials.formScripts')
@endif
@endpush
@endsection
