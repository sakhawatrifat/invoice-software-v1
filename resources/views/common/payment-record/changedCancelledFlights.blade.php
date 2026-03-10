@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $upcomingFlightStart = \Carbon\Carbon::today()->format('Y/m/d');
    $upcomingFlightEnd = \Carbon\Carbon::today()->addDays(30)->format('Y/m/d');
    $upcomingFlightDateRange = $upcomingFlightStart . '-' . $upcomingFlightEnd;
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
                        <a href="{{ route('ticket.reminder.index') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['ticket_reminder'] ?? 'Ticket Reminder' }}</a> &nbsp; -
                    </li>
                    <li class="breadcrumb-item">{{ $getCurrentTranslation['changed_cancelled_flights'] ?? 'Rescheduled & Cancelled Flights' }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <a href="{{ route('flight.list') }}?flight_date_range={{ $upcomingFlightDateRange }}" class="btn btn-sm fw-bold btn-primary">
                    <i class="fa-solid fa-list"></i> {{ $getCurrentTranslation['upcomming_flights'] ?? 'Upcoming Flights' }}
                </a>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card rounded border mt-4">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $getCurrentTranslation['changed_cancelled_flights'] ?? 'Rescheduled & Cancelled Flights' }}</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">{{ $getCurrentTranslation['changed_cancelled_flights_desc'] ?? 'Check all upcoming flights for cancellations or schedule changes. Results are stored in session and shown below until you clear them.' }}</p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button type="button" class="btn btn-primary" id="btn-check-all">
                            <i class="fa-solid fa-rotate-right"></i> {{ $getCurrentTranslation['check_all_upcoming_flight'] ?? 'Check All Upcoming Flight' }}
                        </button>
                        <form method="post" action="{{ route('flight.changedCancelled.clear') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-secondary" id="btn-clear-result">
                                <i class="fa-solid fa-eraser"></i> {{ $getCurrentTranslation['clear_result_data'] ?? 'Clear Result Data' }}
                            </button>
                        </form>
                    </div>
                    <div id="check-background-status" class="alert alert-info d-none mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2" role="status">
                        <span class="d-flex align-items-center">
                            <i class="fa-solid fa-spinner fa-spin me-2"></i>
                            <span id="check-background-status-text">{{ $getCurrentTranslation['checking_background_message'] ?? 'Checking in background. You can use other tabs; this page will refresh when done.' }}</span>
                        </span>
                        <button type="button" class="btn btn-sm btn-warning" id="btn-stop-check">
                            <i class="fa-solid fa-stop"></i> {{ $getCurrentTranslation['stop_check'] ?? 'Stop Check' }}
                        </button>
                    </div>
                    @if(empty($results))
                        <div class="text-center text-muted py-5">
                            {{ $getCurrentTranslation['no_changed_cancelled_result'] ?? 'No result data. Click "Check All Upcoming Flight" to scan for cancelled or rescheduled flights.' }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-rounded table-striped border gy-7 gs-7">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th class="text-start">{{ $getCurrentTranslation['trip_info'] ?? 'Trip info' }}</th>
                                        <th>{{ $getCurrentTranslation['status'] ?? 'Status' }}</th>
                                        <th>{{ $getCurrentTranslation['action'] ?? 'Action' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $index => $item)
                                        @php
                                            $row = $item['payment'];
                                            $countsLine = passengerCountsLineHtml($row->ticket?->passengers ?? []);
                                            $countsBlock = $countsLine !== '' ? '<div class="mb-1">' . $countsLine . '</div>' : '';
                                            $upcomingDeparture = $row->ticket?->upcoming_departure_date ?? null;
                                            $segmentBadge = $row->ticket?->upcoming_segment_badge ?? null;
                                            $segmentBadgeLabel = $segmentBadge === 'Return' ? ($getCurrentTranslation['segment_return'] ?? 'Return') : ($segmentBadge === 'Outbound' ? ($getCurrentTranslation['segment_outbound'] ?? 'Outbound') : '');
                                            $badgeHtml = $segmentBadgeLabel ? ' <span class="badge badge-' . ($segmentBadge === 'Return' ? 'info' : 'primary') . ' ms-1">' . e($segmentBadgeLabel) . '</span>' : '';
                                            $departureLabel = $getCurrentTranslation['departure_label'] ?? 'Departure';
                                            $departureLine = $upcomingDeparture ? '<strong>' . $departureLabel . ':</strong> ' . \Carbon\Carbon::parse($upcomingDeparture)->format('Y-m-d, H:i') . $badgeHtml . '<br>' : '';
                                            $return = $row->return_date_time ? date('Y-m-d, H:i', strtotime($row->return_date_time)) : 'N/A';
                                            $airline = $row->airline->name ?? 'N/A';
                                            $introductionSource = $row->introductionSource->name ?? 'N/A';
                                            $flightStatusMailCount = (int)($row->flight_status_mail_count ?? 0);
                                            $flightStatusMailCountLabel = $getCurrentTranslation['flight_status_mail_count'] ?? 'Flight status mail count';
                                        @endphp
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <div style="max-width: 280px; line-height: 1.6; text-align: left;">
                                                    <strong>{{ $getCurrentTranslation['passenger_name_label'] ?? 'Passenger name' }}:</strong> {{ $row->client_name }}<br>
                                                    {!! $countsBlock !!}
                                                    <strong>{{ $getCurrentTranslation['passenger_phone_label'] ?? 'Phone' }}:</strong> {{ $row->client_phone ?? 'N/A' }}<br>
                                                    <strong>{{ $getCurrentTranslation['passenger_email_label'] ?? 'Email' }}:</strong> {{ $row->client_email ?? 'N/A' }}<br>
                                                    <strong>{{ $getCurrentTranslation['trip_type_label'] ?? 'Trip type' }}:</strong> {{ $row->trip_type }}<br>
                                                    <strong>{{ $getCurrentTranslation['airline_label'] ?? 'Airline' }}:</strong> {{ $airline }}<br>
                                                    <strong>{{ $getCurrentTranslation['flight_route_label'] ?? 'Flight route' }}:</strong> {{ $row->flight_route }}<br>
                                                    {!! $departureLine !!}
                                                    <strong>{{ $getCurrentTranslation['return_label'] ?? 'Return' }}:</strong> {{ $return }}<br>
                                                    <strong>{{ $getCurrentTranslation['introduction_source_label'] ?? 'Introduction source' }}:</strong> {{ $introductionSource }}<br>
                                                    <strong>{{ $flightStatusMailCountLabel }}:</strong> <span class="badge badge-info">{{ $flightStatusMailCount }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if($item['has_cancelled'])
                                                    <span class="badge bg-danger me-1">{{ $getCurrentTranslation['flight_cancelled'] ?? 'Flight cancelled' }}</span>
                                                @endif
                                                @if($item['has_schedule_changed'])
                                                    <span class="badge bg-warning text-dark">{{ $getCurrentTranslation['schedule_changed'] ?? 'Schedule changed' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(($row->ticket->document_type ?? '') === 'ticket' && hasPermission('payment.flight_status'))
                                                    <a href="{{ route('payment.flight.status', $row->id) }}" class="btn btn-sm btn-success my-1" title="{{ $getCurrentTranslation['check_flight_status'] ?? 'Check Flight Status' }}">
                                                        <i class="fa-solid fa-plane-departure"></i>
                                                    </a>
                                                @endif
                                                @if(hasPermission('payment.show'))
                                                    <a href="{{ route('payment.show', $row->id) }}" class="btn btn-sm btn-info my-1" title="Details"><i class="fa-solid fa-pager"></i></a>
                                                @endif
                                                @if(hasPermission('payment.edit'))
                                                    <a href="{{ route('payment.edit', $row->id) }}" class="btn btn-sm btn-primary my-1" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
$(function() {
    var checkInProgress = false;
    var checkUrl = '{{ route('flight.changedCancelled.check') }}';
    var stopUrl = '{{ route('flight.changedCancelled.stop') }}';
    var statusUrl = '{{ route('flight.changedCancelled.status') }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
    var pollInterval = null;
    var beforeUnloadHandler = function(e) {
        if (checkInProgress) {
            e.preventDefault();
            if (e.returnValue !== undefined) e.returnValue = '';
            return '';
        }
    };
    function stopPolling() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
        checkInProgress = false;
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        $('#btn-check-all').prop('disabled', false);
        $('#btn-clear-result').prop('disabled', false);
        $('#check-background-status').addClass('d-none');
        if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
    }
    function pollStatus() {
        $.getJSON(statusUrl, function(data) {
            if (data && !data.running) {
                stopPolling();
                var msg = '{{ $getCurrentTranslation['check_completed'] ?? 'Check completed.' }}';
                if (typeof toastr !== 'undefined') toastr.success(msg); else alert(msg);
                window.location.reload();
            }
        });
    }

    $('#btn-stop-check').on('click', function() {
        if (!checkInProgress) return;
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: stopUrl,
            method: 'POST',
            data: { _token: csrfToken },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function() {
                stopPolling();
                var msg = '{{ $getCurrentTranslation['check_stopped'] ?? 'Check stopped.' }}';
                if (typeof toastr !== 'undefined') toastr.info(msg); else alert(msg);
            },
            error: function() { stopPolling(); $btn.prop('disabled', false); }
        });
    });

    $('#btn-check-all').on('click', function() {
        if (checkInProgress) return;
        checkInProgress = true;
        $('#btn-check-all').prop('disabled', true);
        $('#btn-clear-result').prop('disabled', true);
        $('#check-background-status').removeClass('d-none');
        $('#btn-stop-check').prop('disabled', false);
        if (typeof $ !== 'undefined' && $.fn) {
            $('.r-preloader').css('display', 'flex').show();
        }
        window.addEventListener('beforeunload', beforeUnloadHandler);

        $.ajax({
            url: checkUrl,
            method: 'POST',
            data: { _token: csrfToken },
            timeout: 30000,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(res) {
                if (res && res.started) {
                    window.removeEventListener('beforeunload', beforeUnloadHandler);
                    if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
                    $('#check-background-status').removeClass('d-none');
                    $('#check-background-status-text').text(res.message || '{{ $getCurrentTranslation['checking_background_message'] ?? 'Checking in background. You can use other tabs; this page will refresh when done.' }}');
                    pollInterval = setInterval(pollStatus, 4000);
                    pollStatus();
                    return;
                }
                stopPolling();
                var msg = (res.message || '{{ $getCurrentTranslation['check_completed'] ?? 'Check completed.' }}') + (res.count !== undefined ? ' ' + res.count + ' {{ $getCurrentTranslation['flights_with_changes_found'] ?? 'flight(s) with changes found.' }}' : '');
                if (typeof toastr !== 'undefined') toastr.success(msg); else alert(msg);
                window.location.reload();
            },
            error: function(xhr) {
                stopPolling();
                var msg = '{{ $getCurrentTranslation['something_went_wrong'] ?? 'Something went wrong.' }}';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                else if (xhr && xhr.status === 419) msg = '{{ $getCurrentTranslation['session_expired_reload'] ?? 'Session expired. Please reload the page and try again.' }}';
                else if (xhr && xhr.status === 403) msg = '{{ $getCurrentTranslation['permission_denied'] ?? 'Permission denied.' }}';
                if (typeof toastr !== 'undefined') toastr.error(msg); else alert(msg);
            }
        });
    });
});
</script>
@endpush
