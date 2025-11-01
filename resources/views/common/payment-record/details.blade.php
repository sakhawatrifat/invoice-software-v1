@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
	<!--Toolbar-->
	<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
		<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
			<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</a> &nbsp; - 
					</li>
					@if(isset($listRoute) && !empty($listRoute))
						<li class="breadcrumb-item text-muted">
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['payment_list'] ?? 'payment_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['payment_details'] ?? 'payment_details' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
                @if (hasPermission('payment.edit'))
                    <a href="{{ route('payment.edit', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i>
                        {{ $getCurrentTranslation['edit'] ?? 'edit' }}
                    </a>
                @endif
				@if(isset($listRoute) && !empty($listRoute))
					<a href="{{ $listRoute }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-arrow-left"></i>
						{{ $getCurrentTranslation['back_to_list'] ?? 'back_to_list' }}
					</a>
				@endif
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
            <style>
                .inv-footer-description ul, 
                .inv-footer-description ol {
                    padding-left: 20px!important;
                }
            </style>

            <!-- Payment Information -->
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">
                        {{ $getCurrentTranslation['payment_details'] ?? 'payment_details' }}
                    </h5>
                    {{-- <div class="d-flex align-items-center gap-2 gap-lg-3">
                    	<a href="{{ route('payment.downloadPdf', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['download'] ?? 'download' }}</a>
                    </div> --}}
                </div>
                <!-- Profile Information -->
                <div class="card-body">
                   <div class="container-fluid py-5">
                        <div class="row">
                            <div class="col-12">
                                <!-- Invoice Informations -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-primary text-white">
                                        <h5 class="mb-0 text-light">{{ $getCurrentTranslation['invoice_informations'] ?? 'invoice_informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['payment_invoice_id_label'] ?? 'payment_invoice_id_label' }}:</strong>
                                                <p>{{ $editData->payment_invoice_id ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['ticket_label'] ?? 'ticket_label' }}:</strong>
                                                <p>{{ $editData->ticket?->invoice_id ?? 'N/A' }} ({{ $editData->ticket?->reservation_number ?? '' }})</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['client_name_label'] ?? 'client_name_label' }}:</strong>
                                                <p>{{ $editData->client_name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['client_phone_label'] ?? 'client_phone_label' }}:</strong>
                                                <p>{{ $editData->client_phone ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['client_email_label'] ?? 'client_email_label' }}:</strong>
                                                <p>{{ $editData->client_email ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['introduction_source_label'] ?? 'introduction_source_label' }}:</strong>
                                                <p>{{ $editData->introductionSource?->name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['customer_country_label'] ?? 'customer_country_label' }}:</strong>
                                                <p>
                                                    @if(!empty($editData->country?->name))
                                                        <img src="{{ getStaticFile('flags', strtolower($editData->country->short_name)) }}"
                                                            alt="{{ $editData->country->name }}"
                                                            style="">
                                                        {{ $editData->country->name }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </p>

                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['issued_supplier_label'] ?? 'issued_supplier_label' }}:</strong>
                                                <p>{{ $editData->issued_suppliers_name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['issued_by_label'] ?? 'issued_by_label' }}:</strong>
                                                <p>{{ $editData->issuedBy?->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Documents -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-secondary text-white">
                                        <h5 class="mb-0">{{ $getCurrentTranslation['documents'] ?? 'documents' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($editData->paymentDocuments->count())
                                            <div class="d-flex justify-start flex-wrap">
                                                @foreach($editData->paymentDocuments as $doc)
                                                    @php
                                                        $extension = strtolower(pathinfo($doc->file_url, PATHINFO_EXTENSION));
                                                        $imageExtensions = ['jpg','jpeg','png','gif','webp'];
                                                        
                                                    @endphp
                                                    @if(in_array($extension, $imageExtensions))
                                                        <div class="append-prev mf-prev hover-effect m-0 mx-5" data-src="{{ $doc->file_full_url }}">
                                                            <img src="{{ $doc->file_full_url }}" alt="Document" style="max-height:100px; max-width:100px; object-fit:contain;">
                                                        </div>
                                                    @else
                                                        <a class="append-prev file-prev-thumb mt-2 mx-5" href="{{ $doc->file_full_url }}" target="_blank" 
                                                            onclick="return confirm('Are you sure you want to download this file?');" download>
                                                                @if($extension == 'pdf') 
                                                                    <i class="fas fa-file-pdf"></i>
                                                                @else
                                                                    <i class="fas fa-file-alt"></i>
                                                                @endif
                                                                {{-- {{ basename($doc->file_url) }} --}}
                                                            </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted">{{ $getCurrentTranslation['no_documents_uploaded'] ?? 'no_documents_uploaded' }}</p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Trip Informations -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-info text-white">
                                        <h5 class="mb-0 text-light">{{ $getCurrentTranslation['trip_informations'] ?? 'trip_informations' }}</h5>
                                        @if(hasPermission('ticket.show') && $editData->ticket && $editData->ticket->id)
                                            <button type="button" class="btn btn-sm btn-secondary my-1 show-ticket-btn" data-url="{{ route('ticket.show', $editData->ticket->id) }}">
                                                <b>{{ $getCurrentTranslation['view_ticket_informations'] ?? 'view_ticket_informations' }}</b>
                                                {{-- <i class="fa-solid fa-pager" style="font-size: 20px"></i> --}}
                                            </button>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @php
                                                $tripFields = [
                                                    'trip_type', 'departure_date_time', 'return_date_time', 'departure', 
                                                    'destination', 'flight_route', 'airline', 'seat_confirmation', 'mobility_assistance',
                                                    'transit_visa_application', 'halal_meal_request', 'transit_hotel', 'note'
                                                ];
                                            @endphp
                                            @foreach($tripFields as $field)
                                                @if($field == 'note')
                                                    <div class="col-md-12 mb-3">
                                                        <strong>{{ $getCurrentTranslation[$field.'_label'] ?? ucfirst(str_replace('_', ' ', $field)) }}:</strong>
                                                        <p>
                                                            @php
                                                                $cleanNote = strip_tags($editData->note ?? '');
                                                            @endphp

                                                            <mark>{{ $cleanNote !== '' ? $cleanNote : 'N/A' }}</mark>
                                                        </p>
                                                    </div>
                                                @else
                                                    <div class="col-md-4 mb-3">
                                                        <strong>{{ $getCurrentTranslation[$field.'_label'] ?? ucfirst(str_replace('_', ' ', $field)) }}:</strong>
                                                        <p>
                                                            @if($field === 'airline')
                                                                {{ $editData->airline?->name ?? 'N/A' }}

                                                            @elseif($field === 'seat_confirmation')
                                                                @php
                                                                    $options = ['Window', 'Aisle', 'Not Chosen'];
                                                                    $value = $editData->$field;
                                                                    if (empty($value)) {
                                                                        $value = 'N/A';
                                                                        $badgeClass = 'bg-light text-dark';
                                                                    } else {
                                                                        $badgeClass = match($value) {
                                                                            'Window' => 'bg-primary',
                                                                            'Aisle' => 'bg-success',
                                                                            'Not Chosen' => 'bg-warning text-dark',
                                                                            default => 'bg-light text-dark'
                                                                        };
                                                                    }
                                                                @endphp
                                                                <span class="badge {{ $badgeClass }}">{{ $value }}</span>

                                                            @elseif($field === 'mobility_assistance')
                                                                @php
                                                                    $options = ['Wheelchair', 'Baby Bassinet Seat', 'Meet & Assist', 'Not Chosen'];
                                                                    $value = $editData->$field;
                                                                    if (empty($value)) {
                                                                        $value = 'N/A';
                                                                        $badgeClass = 'bg-light text-dark';
                                                                    } else {
                                                                        $badgeClass = match($value) {
                                                                            'Wheelchair' => 'bg-primary',
                                                                            'Baby Bassinet Seat' => 'bg-info',
                                                                            'Meet & Assist' => 'bg-success',
                                                                            'Not Chosen' => 'bg-warning text-dark',
                                                                            default => 'bg-light text-dark'
                                                                        };
                                                                    }
                                                                @endphp
                                                                <span class="badge {{ $badgeClass }}">{{ $value }}</span>

                                                            @elseif($field === 'transit_visa_application')
                                                                @php
                                                                    $options = ['Need To Do', 'Done', 'No Need'];
                                                                    $value = $editData->$field;
                                                                    if (empty($value)) {
                                                                        $value = 'N/A';
                                                                        $badgeClass = 'bg-light text-dark';
                                                                    } else {
                                                                        $badgeClass = match($value) {
                                                                            'Need To Do' => 'bg-danger text-white',
                                                                            'Done' => 'bg-success',
                                                                            'No Need' => 'bg-secondary text-dark',
                                                                            default => 'bg-light text-dark'
                                                                        };
                                                                    }
                                                                @endphp
                                                                <span class="badge {{ $badgeClass }}">{{ $value }}</span>

                                                            @elseif($field === 'halal_meal_request')
                                                                @php
                                                                    $options = ['Need To Do', 'Done', 'No Need'];
                                                                    $value = $editData->$field;
                                                                    if (empty($value)) {
                                                                        $value = 'N/A';
                                                                        $badgeClass = 'bg-light text-dark';
                                                                    } else {
                                                                        $badgeClass = match($value) {
                                                                            'Need To Do' => 'bg-danger text-white',
                                                                            'Done' => 'bg-success',
                                                                            'No Need' => 'bg-secondary text-dark',
                                                                            default => 'bg-light text-dark'
                                                                        };
                                                                    }
                                                                @endphp
                                                                <span class="badge {{ $badgeClass }}">{{ $value }}</span>

                                                            @elseif($field === 'transit_hotel')
                                                                @php
                                                                    $options = ['Need To Do', 'Done', 'No Need'];
                                                                    $value = $editData->$field;
                                                                    if (empty($value)) {
                                                                        $value = 'N/A';
                                                                        $badgeClass = 'bg-light text-dark';
                                                                    } else {
                                                                        $badgeClass = match($value) {
                                                                            'Need To Do' => 'bg-danger text-white',
                                                                            'Done' => 'bg-success',
                                                                            'No Need' => 'bg-secondary text-dark',
                                                                            default => 'bg-light text-dark'
                                                                        };
                                                                    }
                                                                @endphp
                                                                <span class="badge {{ $badgeClass }}">{{ $value }}</span>

                                                            @elseif(in_array($field, ['departure_date_time', 'return_date_time']))
                                                                {{ $editData->$field ? date('Y-m-d, H:i', strtotime($editData->$field)) : 'N/A' }}

                                                            @else
                                                                {{ $editData->$field ?? 'N/A' }}
                                                            @endif
                                                        </p>

                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Owner Informations -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-warning text-dark">
                                        <h5 class="mb-0">{{ $getCurrentTranslation['card_owner_informations'] ?? 'card_owner_informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @php
                                                $cardFields = [
                                                    'transfer_to_id', 'payment_method_id', 'issued_card_type_id', 
                                                    'card_owner_id', 'card_digit'
                                                ];
                                            @endphp
                                            @foreach($cardFields as $field)
                                                <div class="col-md-4 mb-3">
                                                    <strong>{{ $getCurrentTranslation[str_replace('_id', '', $field).'_label'] ?? str_replace('_id', '', $field) }}:</strong>
                                                    <p>
                                                        @php
                                                            $relationMap = [
                                                                'transfer_to_id' => 'transferTo',
                                                                'payment_method_id' => 'paymentMethod',
                                                                'issued_card_type_id' => 'issuedCardType',
                                                                'card_owner_id' => 'cardOwner'
                                                            ];
                                                        @endphp
                                                        @if(isset($relationMap[$field]))
                                                            {{ $editData->{$relationMap[$field]}?->name ?? 'N/A' }}
                                                        @else
                                                            {{ $editData->$field ?? 'N/A' }}
                                                        @endif
                                                    </p>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Informations -->
                                <div class="card mb-4 position-relative">
                                    <div class="card-header align-items-center bg-success text-white">
                                        <h5 class="mb-0">{{ $getCurrentTranslation['payment_informations'] ?? 'payment_informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($editData->refund_payment_status == "Paid")
                                        <!-- Refunded Seal -->
                                            <div class="seal-refunded" role="img" aria-label="Refunded">
                                              <span class="seal-text">REFUNDED</span>
                                              <span class="seal-check" aria-hidden="true">✓</span>
                                            </div>
                                        @endif

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <strong>{{ $getCurrentTranslation['total_purchase_price_label'] ?? 'total_purchase_price_label' }}:</strong>
                                                <p>{{ $editData->total_purchase_price ? number_format($editData->total_purchase_price, 2) : '0.00' }} {{ Auth::user()->company_data->currency->short_name ?? '' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>{{ $getCurrentTranslation['total_selling_price_label'] ?? 'total_selling_price_label' }}:</strong>
                                                <p>{{ $editData->total_selling_price ? number_format($editData->total_selling_price, 2) : '0.00' }} {{ Auth::user()->company_data->currency->short_name ?? '' }}</p>
                                            </div>
                                        </div>

                                        <!-- Payment Collection Table -->
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th class="ps-5">#</th>
                                                        <th>{{ $getCurrentTranslation['paid_amount_label'] ?? 'paid_amount_label' }}</th>
                                                        <th class="pe-5">{{ $getCurrentTranslation['date_label'] ?? 'date_label' }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if(!empty($editData->paymentData) && is_array($editData->paymentData))
                                                        @foreach($editData->paymentData as $index => $payment)
                                                            <tr>
                                                                <td class="ps-5">{{ $index + 1 }}</td>
                                                                <td>{{ $payment['paid_amount'] ? number_format($payment['paid_amount']) : '0.00' }} {{ Auth::user()->company_data->currency->short_name ?? '' }}</td>
                                                                <td class="pe-5">{{ $payment['date'] ?? 'N/A' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td colspan="3" class="text-center">{{ $getCurrentTranslation['no_payments_recorded'] ?? 'no_payments_recorded' }}</td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-3 text-end text-danger">
                                            <h4 class="text-danger">
                                                @php
                                                    $remainingDue = $editData->total_selling_price - array_sum(array_column($editData->paymentData ?? [], 'paid_amount') ?? [0]) ?? '0';
                                                @endphp
                                                {{ $getCurrentTranslation['remaining_due'] ?? 'remaining_due' }}: <br>
                                                {{ Auth::user()->company_data->currency->short_name ?? '' }} <span>{{ number_format($remainingDue, 2) }}</span>
                                            </h4> 
                                        </div>

                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <strong>{{ $getCurrentTranslation['next_payment_deadline_label'] ?? 'next_payment_deadline_label' }}:</strong>
                                                <p>{{ $editData->next_payment_deadline ? date('Y-m-d', strtotime($editData->next_payment_deadline)) : 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>{{ $getCurrentTranslation['payment_status_label'] ?? 'payment_status_label' }}:</strong> <br>
                                                @php
                                                    $paymentBadgeClass = match ($editData->payment_status) {
                                                        'Paid' => 'badge badge-success',
                                                        'Partial' => 'badge badge-primary',
                                                        'Unpaid' => 'badge badge-danger',
                                                        default => 'badge badge-secondary',
                                                    };
                                                @endphp
                                                <span class="{{ $paymentBadgeClass }}">{{ $editData->payment_status ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Refund Informations -->
                                @if(!empty($editData->is_refund) && $editData->is_refund == 1)
                                    <div class="card mb-4 border-danger">
                                        <div class="card-header align-items-center bg-danger text-white">
                                            <h5 class="mb-0 text-white">{{ $getCurrentTranslation['refund_informations'] ?? 'refund_informations' }}</h5>
                                        </div>

                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>{{ $getCurrentTranslation['cancellation_fee_label'] ?? 'cancellation_fee_label' }}:</strong>
                                                    <p class="text-danger mb-0">
                                                        {{ Auth::user()->company_data->currency->short_name ?? '' }}
                                                        {{ number_format($editData->cancellation_fee ?? 0, 2) }}
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>{{ $getCurrentTranslation['service_fee_label'] ?? 'service_fee_label' }}:</strong>
                                                    <p class="text-info mb-0">
                                                        {{ Auth::user()->company_data->currency->short_name ?? '' }}
                                                        {{ number_format($editData->service_fee ?? 0, 2) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>{{ $getCurrentTranslation['refund_status_label'] ?? 'refund_status_label' }}:</strong> <br>
                                                    @php
                                                        $refundBadgeClass = match ($editData->refund_payment_status) {
                                                            'Paid' => 'badge badge-success',
                                                            'Unpaid' => 'badge badge-danger',
                                                            default => 'badge badge-secondary',
                                                        };
                                                    @endphp
                                                    <span class="{{ $refundBadgeClass }}">{{ $editData->refund_payment_status ?? 'N/A' }}</span>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>{{ $getCurrentTranslation['refund_note_label'] ?? 'refund_note_label' }}:</strong>
                                                    <p class="mb-0 text-muted">
                                                        {!! nl2br(e($editData->refund_note ?? '—')) !!}
                                                    </p>
                                                </div>
                                            </div>

                                            {{-- <div class="mb-3">
                                                <strong>{{ $getCurrentTranslation['refund_note_label'] ?? 'refund_note_label' }}:</strong>
                                                <p class="mb-0 text-muted">
                                                    {!! nl2br(e($editData->refund_note ?? '—')) !!}
                                                </p>
                                            </div> --}}
                                        </div>
                                    </div>
                                @endif


                            </div>
                        </div>
                    </div>
 
                </div>
            </div>


		</div>
		<!--end::Content container-->
	</div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
<script>
	//
</script>
@endpush