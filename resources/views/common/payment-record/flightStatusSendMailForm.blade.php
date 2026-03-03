@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
    $defaultSubject = $getCurrentTranslation['your_flight_schedule_has_changed'] ?? 'Your Flight Schedule Has Changed';
    $ticket = $editData->ticket ?? null;
@endphp
@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!--Toolbar - same structure as ticket mail -->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'Dashboard' }}</a> &nbsp; -
                    </li>
                    @if(isset($listRoute) && !empty($listRoute))
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['full_flight_list'] ?? 'Full Flight List' }}</a> &nbsp; -
                        </li>
                    @endif
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('payment.flight.status', $editData->id) }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }}</a> &nbsp; -
                    </li>
                    <li class="breadcrumb-item">{{ $getCurrentTranslation['send_mail'] ?? 'Send mail' }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @if($ticket && hasPermission('ticket.show'))
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $getCurrentTranslation['download_ticket'] ?? 'Download ticket' }}
                        </button>
                        <div class="dropdown-menu p-0">
                            <a href="{{ route('ticket.downloadPdf', $ticket->id) }}?ticket=1&withPrice=1&layout=1" class="dropdown-item btn btn-sm fw-bold btn-success">{{ $getCurrentTranslation['with_price'] ?? 'With price' }}</a>
                            <a href="{{ route('ticket.downloadPdf', $ticket->id) }}?ticket=1&withPrice=0&layout=1" class="dropdown-item btn btn-sm fw-bold btn-info">{{ $getCurrentTranslation['without_price'] ?? 'Without price' }}</a>
                            @if($ticket->passengers && count($ticket->passengers) > 0)
                                @foreach($ticket->passengers as $passenger)
                                    <a href="{{ route('ticket.downloadPdf', $ticket->id) }}?ticket=1&withPrice=0&passenger={{ $passenger->id }}&layout=1" class="dropdown-item btn btn-sm fw-bold btn-info">{{ $passenger->name }}</a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif
                @if($ticket && $ticket->fareSummary && count($ticket->fareSummary))
                    <a href="{{ route('ticket.downloadPdf', $ticket->id) }}?invoice=1&withPrice=1" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['download_invoice'] ?? 'Download invoice' }}</a>
                @endif
                <a href="{{ route('payment.flight.status', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                    <i class="fa-solid fa-satellite-dish"></i> {{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }}
                </a>
                <a href="{{ route('payment.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                    <i class="fa-solid fa-pager"></i> {{ $getCurrentTranslation['payment_details'] ?? 'Payment Details' }}
                </a>
                @if(isset($listRoute) && !empty($listRoute))
                    <a href="{{ $listRoute }}" class="btn btn-sm fw-bold btn-light-primary">
                        <i class="fa-solid fa-arrow-left"></i> {{ $getCurrentTranslation['back_to_list'] ?? 'Back to list' }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <form method="post" action="{{ route('payment.flight.status.mailSend', $editData->id) }}" enctype="multipart/form-data" id="flight-status-mail-form">
                @csrf
                @method('PUT')

                <div class="card rounded border mt-5 bg-white append-item-container">
                    <div class="card-header">
                        <h3 class="card-title">{{ $getCurrentTranslation['mail_informations'] ?? 'Mail informations' }} – {{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }} <span class="badge badge-light-info ms-2">{{ $getCurrentTranslation['total_mail_sent'] ?? 'Total mail sent' }}: {{ $editData->flight_status_mail_count ?? 0 }}</span></h3>
                        <div class="card-toolbar"></div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label">{{ $getCurrentTranslation['to_email'] ?? 'To (email)' }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="to_email" value="{{ old('to_email', $editData->client_email ?? '') }}" required ip-required placeholder="{{ $getCurrentTranslation['customer_email'] ?? 'Customer email' }}">
                                @error('to_email')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label">{{ $getCurrentTranslation['subject'] ?? 'Subject' }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="subject" value="{{ old('subject', $defaultSubject) }}" required ip-required maxlength="255">
                                @error('subject')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label mb-0">{{ $getCurrentTranslation['mail_content_label'] ?? 'Mail content' }}:</label>
                                <br><small class="d-block mb-2">{{ $getCurrentTranslation['mail_content_note'] ?? 'Load content to fill with current flight status. Use placeholders: {passenger_automatic_name_here}, {passenger_automatic_data_here}' }}</small>
                                <button type="button" class="btn btn-sm btn-info mb-2" id="btn-load-flight-status-content">
                                    <i class="fa-solid fa-download"></i> {{ $getCurrentTranslation['load_content'] ?? 'Load content' }}
                                </button>
                                <textarea class="form-control summernote" name="mail_content" id="flight-status-mail-content" rows="10">{{ old('mail_content') }}</textarea>
                                @error('mail_content')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <hr class="border-top opacity-100">

                            <div class="col-md-12 mb-4">
                                <label class="form-label mb-0">{{ $getCurrentTranslation['cc_emails'] ?? 'CC' }}:</label>
                                <br><small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_cc'] ?? 'Select or type CC emails' }}</small>
                                <select class="form-control select-2-mail" name="cc_emails[]" multiple="multiple">
                                    @foreach(Auth::user()->company_data->cc_emails ?? [] as $ccItem)
                                        <option value="{{ $ccItem }}" selected>{{ $ccItem }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-12 mb-4">
                                <label class="form-label mb-0">{{ $getCurrentTranslation['bcc_emails'] ?? 'BCC' }}:</label>
                                <br><small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_bcc'] ?? 'Select or type BCC emails' }}</small>
                                <select class="form-control select-2-mail" name="bcc_emails[]" multiple="multiple">
                                    @foreach(Auth::user()->company_data->bcc_emails ?? [] as $bccItem)
                                        <option value="{{ $bccItem }}" selected>{{ $bccItem }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="border-top opacity-100">

                            <div class="col-md-12 mb-4">
                                <label class="form-label">{{ $getCurrentTranslation['select_attached_document_type'] ?? 'Attach document' }}:</label>
                                <div class="d-flex align-items-center form-item mb-4">
                                    @if($ticket)
                                        <div class="mb-2 pe-8">
                                            <div class="form-check">
                                                <label class="form-check-label user-select-none">
                                                    <input type="checkbox" class="form-check-input" name="document_type_ticket" value="1" checked>
                                                    {{ $getCurrentTranslation['ticket'] ?? 'Ticket' }} ({{ $getCurrentTranslation['full_ticket_with_updated_data'] ?? 'full ticket with updated data' }})
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="form-check">
                                                <label class="form-check-label user-select-none">
                                                    <input type="checkbox" class="form-check-input" name="ticket_with_price" value="1">
                                                    {{ $getCurrentTranslation['ticket'] ?? 'Ticket' }} {{ $getCurrentTranslation['with_price'] ?? 'with price' }}
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if(hasPermission('ticket.multiLayout') && $ticket)
                                <hr class="border-top opacity-100">
                                <div class="col-md-12 mb-4">
                                    <label class="form-label">{{ $getCurrentTranslation['select_ticket_layout'] ?? 'Select ticket layout' }}:</label>
                                    <div class="d-flex align-items-center form-item mb-4 gap-3">
                                        <div class="ticket-layout-card-outer">
                                            <label class="ticket-layout-card mb-1">
                                                <input type="radio" class="hidden" name="ticket_layout" value="1" checked>
                                                <img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-1.png') }}" class="ticket-img" alt="Layout 1">
                                            </label>
                                            <a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-1.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'Layout' }} 1</b></a>
                                        </div>
                                        <div class="ticket-layout-card-outer">
                                            <label class="ticket-layout-card mb-1">
                                                <input type="radio" class="hidden" name="ticket_layout" value="2">
                                                <img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-2.png') }}" class="ticket-img" alt="Layout 2">
                                            </label>
                                            <a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-2.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'Layout' }} 2</b></a>
                                        </div>
                                        <div class="ticket-layout-card-outer">
                                            <label class="ticket-layout-card mb-1">
                                                <input type="radio" class="hidden" name="ticket_layout" value="3">
                                                <img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-3.png') }}" class="ticket-img" alt="Layout 3">
                                            </label>
                                            <a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-3.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'Layout' }} 3</b></a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <hr class="border-top opacity-100">

                            <div class="col-md-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="update_flight_before_send" value="1" id="update_flight_before_send" checked>
                                    <label class="form-check-label" for="update_flight_before_send">{{ $getCurrentTranslation['update_flight_data_to_db_before_send'] ?? 'Update flight data to DB (from cached/session) before sending' }}</label>
                                </div>
                                <small class="text-muted d-block mt-1">{{ $getCurrentTranslation['update_before_send_note'] ?? 'Apply cached live updates to DB so the attached ticket PDF has updated times.' }}</small>
                            </div>

                            <div class="col-md-12 mt-4">
                                <button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
                                    <span class="indicator-label"><i class="fa-solid fa-paper-plane me-1"></i> {{ $getCurrentTranslation['send_mail'] ?? 'Send mail' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
<script>
(function() {
    var loadUrl = "{{ route('payment.flight.status.mailContentLoad', $editData->id) }}";
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
                    var content = res.mail_content;
                    if ($content.next('.note-editor').length) {
                        $content.summernote('destroy');
                    }
                    $content.val(content);
                    if (typeof initializeSummernote === 'function') initializeSummernote();
                    if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', text: res.message || '' });
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: res.icon || 'error', text: res.message || '' });
                }
            },
            error: function() {
                if (typeof $ !== 'undefined' && $.fn) $('.r-preloader').hide();
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: '{{ $getCurrentTranslation["something_went_wrong"] ?? "Something went wrong" }}' });
            }
        });
    }
    $('#btn-load-flight-status-content').on('click', loadMailContent);
    loadMailContent();
})();
</script>
@endpush
