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
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}"
                           class="text-muted text-hover-primary">
                            {{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}
                        </a> &nbsp; -
                    </li>
                    <li class="breadcrumb-item">
                        {{ $getCurrentTranslation['check_customer_history'] ?? 'Check Customer History' }}
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            @if(isset($leadInfo) && $leadInfo)
                <div class="card rounded border bg-white mb-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $getCurrentTranslation['lead_details'] ?? 'lead_details' }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['customer_full_name_label'] ?? 'customer_full_name_label' }}</div>
                                <div class="text-muted">{{ $leadInfo->customer_full_name }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_email_label'] ?? 'lead_email_label' }}</div>
                                <div class="text-muted">{{ $leadInfo->email ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_phone_label'] ?? 'lead_phone_label' }}</div>
                                <div class="text-muted">{{ $leadInfo->phone ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['company_name_label'] ?? 'company_name_label' }}</div>
                                <div class="text-muted">{{ $leadInfo->company_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_source_label'] ?? 'lead_source_label' }}</div>
                                <div class="text-muted">{{ optional($leadInfo->source)->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_status_label'] ?? 'lead_status_label' }}</div>
                                <div class="text-muted">{{ $leadInfo->status ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="card rounded border bg-white mb-5">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $getCurrentTranslation['customer_history_search'] ?? 'customer_history_search' }}
                    </h3>
                </div>
                <div class="card-body">
                    <form method="get" action="{{ route('customerHistory.index') }}">
                        @if(isset($leadInfo) && $leadInfo)
                            <input type="hidden" name="lead_id" value="{{ $leadInfo->id }}">
                        @endif
                        <div class="row align-items-end">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-item mb-0">
                                    <label class="form-label">{{ $getCurrentTranslation['customer_history_search'] ?? 'customer_history_search' }}:</label>
                                    <input type="text"
                                           class="form-control"
                                           name="search"
                                           value="{{ $search }}"
                                           placeholder="{{ $getCurrentTranslation['customer_search_placeholder'] ?? 'Name / Email / Phone' }}">
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary mt-2 mt-md-0">
                                    {{ $getCurrentTranslation['search'] ?? 'Search' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($stats)
                <div class="card rounded border bg-white mb-10">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $getCurrentTranslation['customer_history_summary'] ?? 'customer_history_summary' }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-5">
                                <div class="border p-4 rounded">
                                    <div class="fw-bold mb-1">{{ $getCurrentTranslation['total_ticket_booked_label'] ?? 'total_ticket_booked_label' }}</div>
                                    <div class="fs-3 fw-bold">{{ $stats['total_tickets'] }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-5">
                                <div class="border p-4 rounded">
                                    <div class="fw-bold mb-1">{{ $getCurrentTranslation['total_ticket_confirmed_label'] ?? 'total_ticket_confirmed_label' }}</div>
                                    <div class="fs-3 fw-bold text-success">{{ $stats['confirmed'] }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-5">
                                <div class="border p-4 rounded">
                                    <div class="fw-bold mb-1">{{ $getCurrentTranslation['total_ticket_cancelled_label'] ?? 'total_ticket_cancelled_label' }}</div>
                                    <div class="fs-3 fw-bold text-danger">{{ $stats['cancelled'] }}</div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-5">
                                <div class="border p-4 rounded">
                                    <div class="fw-bold mb-1">{{ $getCurrentTranslation['total_ticket_on_hold_label'] ?? 'total_ticket_on_hold_label' }}</div>
                                    <div class="fs-3 fw-bold text-warning">{{ $stats['on_hold'] }}</div>
                                </div>
                            </div>
                        </div>
                        @if($stats['last_ticket'])
                            <div class="row mt-5">
                                <div class="col-md-6 mb-5">
                                    <div class="border p-4 rounded h-100">
                                        <div class="fw-bold mb-1">{{ $getCurrentTranslation['last_ticket_booked_label'] ?? 'last_ticket_booked_label' }}</div>
                                        <div class="fs-6">
                                            {{ $getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label' }}:
                                            <strong>{{ $stats['last_ticket']->invoice_id ?? 'N/A' }}</strong><br>
                                            {{ $getCurrentTranslation['booking_status_label'] ?? 'booking_status_label' }}:
                                            <strong>{{ $stats['last_ticket']->booking_status ?? 'N/A' }}</strong><br>
                                            {{ $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label' }}:
                                            <strong>{{ optional($stats['last_ticket']->invoice_date)->format('Y-m-d') }}</strong><br>
                                            @php $primaryPassenger = $stats['primary_passenger'] ?? null; @endphp
                                            @if($primaryPassenger)
                                                {{ $getCurrentTranslation['customer_full_name_label'] ?? 'customer_full_name_label' }}:
                                                <strong>{{ $primaryPassenger->name }}</strong><br>
                                                {{ $getCurrentTranslation['customer_email_label'] ?? 'customer_email_label' }}:
                                                <strong>{{ $primaryPassenger->email ?? 'N/A' }}</strong><br>
                                                {{ $getCurrentTranslation['customer_phone_label'] ?? 'customer_phone_label' }}:
                                                <strong>{{ $primaryPassenger->phone ?? 'N/A' }}</strong>
                                            @endif
                                        </div>
                                        @if(hasPermission('ticket.show'))
                                            @php
                                                $lastTicket = $stats['last_ticket'];
                                                $detailsUrl = route('ticket.show', $lastTicket->id);
                                            @endphp
                                            <div class="mt-3">
                                                @if(hasPermission('ticket.multiLayout') && $lastTicket->document_type == 'ticket')
                                                    <button type="button"
                                                            class="btn btn-sm btn-info show-ticket-btn"
                                                            data-url="{{ $detailsUrl }}">
                                                        <i class="fa-solid fa-pager"></i>
                                                        {{ $getCurrentTranslation['details'] ?? 'details' }}
                                                    </button>
                                                @else
                                                    <a href="{{ $detailsUrl }}" class="btn btn-sm btn-info">
                                                        <i class="fa-solid fa-pager"></i>
                                                        {{ $getCurrentTranslation['details'] ?? 'details' }}
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mb-5">
                                    <div class="border p-4 rounded h-100">
                                        <div class="fw-bold mb-1">{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}</div>
                                        <div class="fs-6 mb-3">
                                            {{ implode(', ', $stats['pax_types']->toArray()) ?: 'N/A' }}
                                        </div>
                                        <div class="fw-bold mb-1">{{ $getCurrentTranslation['gender_label'] ?? 'gender_label' }}</div>
                                        <div class="fs-6 mb-3">
                                            {{ implode(', ', $stats['genders']->toArray()) ?: 'N/A' }}
                                        </div>
                                        <div class="fw-bold mb-1">{{ $getCurrentTranslation['date_of_birth_label'] ?? 'date_of_birth_label' }}</div>
                                        <div class="fs-6">
                                            {{ implode(', ', $stats['date_of_births']->toArray()) ?: 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($passengers->isNotEmpty())
                <div class="card rounded border bg-white">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $getCurrentTranslation['customer_ticket_history_label'] ?? 'customer_ticket_history_label' }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-rounded table-striped border gy-5 gs-5">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ $getCurrentTranslation['customer_name_label'] ?? 'customer_name_label' }}</th>
                                    <th>{{ $getCurrentTranslation['customer_email_label'] ?? 'customer_email_label' }}</th>
                                    <th>{{ $getCurrentTranslation['customer_phone_label'] ?? 'customer_phone_label' }}</th>
                                    <th>{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}</th>
                                    <th>{{ $getCurrentTranslation['date_of_birth_label'] ?? 'date_of_birth_label' }}</th>
                                    <th>{{ $getCurrentTranslation['gender_label'] ?? 'gender_label' }}</th>
                                    <th>{{ $getCurrentTranslation['booking_status_label'] ?? 'booking_status_label' }}</th>
                                    <th>{{ $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label' }}</th>
                                    <th>{{ $getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label' }}</th>
                                    <th>{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($passengers as $index => $p)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $p->name }}</td>
                                        <td>{{ $p->email }}</td>
                                        <td>{{ $p->phone }}</td>
                                        <td>{{ $p->pax_type }}</td>
                                        <td>{{ $p->date_of_birth }}</td>
                                        <td>{{ $p->gender }}</td>
                                        <td>{{ optional($p->ticket)->booking_status }}</td>
                                        <td>{{ optional(optional($p->ticket)->invoice_date)->format('Y-m-d') }}</td>
                                        <td>{{ optional($p->ticket)->invoice_id }}</td>
                                        <td>
                                            @if(hasPermission('ticket.show') && $p->ticket)
                                                @php
                                                    $ticketDetailsUrl = route('ticket.show', $p->ticket->id);
                                                @endphp
                                                @if(hasPermission('ticket.multiLayout') && $p->ticket->document_type == 'ticket')
                                                    <button type="button"
                                                            class="btn btn-sm btn-info show-ticket-btn"
                                                            data-url="{{ $ticketDetailsUrl }}">
                                                        <i class="fa-solid fa-pager"></i>
                                                    </button>
                                                @else
                                                    <a href="{{ $ticketDetailsUrl }}" class="btn btn-sm btn-info">
                                                        <i class="fa-solid fa-pager"></i>
                                                    </a>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @elseif(request()->has('search') && $search !== '')
                <div class="alert alert-warning mt-5">
                    {{ $getCurrentTranslation['no_customer_history_found'] ?? 'no_customer_history_found' }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

