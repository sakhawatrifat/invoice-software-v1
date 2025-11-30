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
					
					@if(request()->document_type == 'ticket')
						<li class="breadcrumb-item">{{ $getCurrentTranslation['ticket_list'] ?? 'ticket_list' }}</li>
					@elseif(request()->document_type == 'invoice')
						<li class="breadcrumb-item">{{ $getCurrentTranslation['invoice_list'] ?? 'invoice_list' }}</li>
					@elseif(request()->document_type == 'ticket-invoice')
						<li class="breadcrumb-item">{{ $getCurrentTranslation['ticket_and_invoice_list'] ?? 'ticket_and_invoice_list' }}</li>
					@elseif(request()->document_type == 'quotation')
						<li class="breadcrumb-item">{{ $getCurrentTranslation['quotation_list'] ?? 'quotation_list' }}</li>
					@elseif(request()->has('data_for') && request()->data_for == 'agent')
						<li class="breadcrumb-item">{{ $getCurrentTranslation['agent_document_list'] ?? 'agent_document_list' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['all_document_list'] ?? 'all_document_list' }}</li>
					@endif
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if((isset($createRoute) && !empty($createRoute)))
					<div class="btn-group">
						<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
							{{ $getCurrentTranslation['add_new'] ?? 'add_new' }}
						</button>
						<div class="dropdown-menu p-0">
							<a href="{{ $createRoute }}?document_type=ticket" class="dropdown-item btn btn-sm fw-bold btn-success">{{ $getCurrentTranslation['ticket'] ?? 'ticket' }}</a>
							<a href="{{ $createRoute }}?document_type=invoice" class="dropdown-item btn btn-sm fw-bold btn-info">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</a>
							<a href="{{ $createRoute }}?document_type=quotation" class="dropdown-item btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['quotation'] ?? 'quotation' }}</a>
						</div>
					</div>
				@endif
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<!--begin::Accordion-->
			<div class="card rounded border mt-5 p-0 bg-white">
				<div class="accordion" id="kt_accordion_1">
					<div class="accordion-item">
						<h2 class="accordion-header" id="kt_accordion_1_header_1">
							<button class="accordion-button fs-4 fw-semibold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_1_body_1" aria-expanded="true" aria-controls="kt_accordion_1_body_1">
								<i class="fa fa-filter" aria-hidden="true"></i> &nbsp;
								{{ $getCurrentTranslation['filter'] ?? 'filter' }}
							</button>
						</h2>
						<div id="kt_accordion_1_body_1" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_1_header_1" data-bs-parent="#kt_accordion_1">
							<div class="accordion-body">
								<form class="filter-data-form">
									<input class="fixed-value" type="hidden" name="data_for" value="{{ request()->data_for ?? '' }}">
									<input class="fixed-value" type="hidden" name="document_type" value="{{ request()->document_type ?? '' }}">
									<div class="row">
										@if(request()->has('data_for') && request()->data_for == 'agent')
											<div class="col-md-4">
												<div class="form-item mb-5">
													@php
														$options = ['Ticket', 'Invoice', 'Quotation'];

														$selected = request()->document_type ?? '';
													@endphp
													<label class="form-label">{{ $getCurrentTranslation['document_type_label'] ?? 'document_type_label' }}:</label>
													<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['document_type_placeholder'] ?? 'document_type_placeholder' }}" name="document_type">
														<option value="all">----</option>
														@foreach($options as $option)
															<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
														@endforeach
													</select>
													@error('document_type')
														<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
													@enderror
												</div>
											</div>
										@endif
										<div class="col-md-4">
											<div class="form-item mb-5">
												@php
													$options = ['One Way', 'Round Trip', 'Multi City'];

													$selected = request()->trip_type ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['trip_type_placeholder'] ?? 'trip_type_placeholder' }}" name="trip_type">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('trip_type')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-4">
											<div class="input-item">
												@php
													$options = getWhereInModelData('Airline', 'status', [1]);
													$selected = request()->airline_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="airline_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-4">
											<div class="form-item mb-5">
												@php
													$options = ['On Hold', 'Processing', 'Confirmed', 'Cancelled'];

													$selected = request()->booking_status ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['booking_status_label'] ?? 'booking_status_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['booking_status_placeholder'] ?? 'booking_status_placeholder' }}" name="booking_status">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-4">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['flight_date_range_label'] ?? 'flight_date_range_label' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													<div class="cursor-pointer dateRangePicker future-date {{request()->flight_date_range ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="flight_date_range" data-value="{{request()->flight_date_range ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('flight_date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-4">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['invoice_date_range_label'] ?? 'invoice_date_range_label' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														$selectedDateRange = request()->invoice_date_range ?? null;
													@endphp
													<div class="cursor-pointer dateRangePicker {{$selectedDateRange ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="invoice_date_range" data-value="{{$selectedDateRange ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('invoice_date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-0">
												<button type="reset" class="btn btn-secondary btn-sm filter-reset-btn datatable-filter me-3">
													{{ $getCurrentTranslation['reset'] ?? 'reset' }}
												</button>
												<button type="button" class="btn btn-primary btn-sm filter-data-btn">
													{{ $getCurrentTranslation['filter'] ?? 'filter' }}
												</button>
											</div>
										</div>

										


									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!--end::Accordion-->
			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						@if(request()->document_type == 'ticket')
							{{ $getCurrentTranslation['ticket_list'] ?? 'ticket_list' }}
						@elseif(request()->document_type == 'invoice')
							{{ $getCurrentTranslation['invoice_list'] ?? 'invoice_list' }}
						@elseif(request()->document_type == 'ticket-invoice')
							{{ $getCurrentTranslation['ticket_and_invoice_list'] ?? 'ticket_and_invoice_list' }}
						@elseif(request()->document_type == 'quotation')
							{{ $getCurrentTranslation['quotation_list'] ?? 'quotation_list' }}
						@elseif(request()->has('data_for') && request()->data_for == 'agent')
							{{ $getCurrentTranslation['agent_document_list'] ?? 'agent_document_list' }}
						@else
							{{ $getCurrentTranslation['all_document_list'] ?? 'all_document_list' }}
						@endif
					</h3>
				</div>
            <table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
				<thead>
					<tr>
						<th>#</th>
						<th>{{ $getCurrentTranslation['type'] ?? 'type' }}</th>
						<th>{{ $getCurrentTranslation['user'] ?? 'user' }}</th>
						<th style="width: 125px">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</th>
						{{-- <th>Invoice ID</th> --}}
						<th>{{ $getCurrentTranslation['reservation'] ?? 'reservation' }}</th>
						{{-- <th>Trip Type</th>
						<th>Ticket Type</th> --}}
						<th>{{ $getCurrentTranslation['status'] ?? 'status' }}</th>
						<th>{{ $getCurrentTranslation['created_at'] ?? 'created_at' }}</th>
						<th>{{ $getCurrentTranslation['created_by'] ?? 'created_by' }}</th>
						<th>{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
					</tr>
				</thead>
			</table>

			</div>
		</div>
		<!--end::Content container-->
	</div>
</div>

@endsection

@push('script')
<script>

	var dataTable;
	$(document).ready(function() {
		// Serialize form data
		var formData = $('.filter-data-form').serialize(); // e.g., "field1=value1&field2=value2"

		// Get base URL and current query string
		var queryString = window.location.search; // existing URL params
		var baseUrl = '{{ $dataTableRoute }}';

		// Combine base URL + existing query string + serialized form data
		var finalUrl = baseUrl;
		if (queryString) {
			finalUrl += queryString + (formData ? '&' + formData : '');
		} else if (formData) {
			finalUrl += '?' + formData;
		}

		// Initialize DataTable
		dataTable = $('#datatable').DataTable({
			processing: true,
			serverSide: true,
			ajax: finalUrl,
			searching: true,
			dom: 'lfrtip',
			columns: [
				{ data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
				{ data: 'document_type', name: 'document_type' },
				{ data: 'user_id', name: 'user_id', orderable: false, searchable: true },
				{ data: 'invoice_date', name: 'invoice_date', orderable: false, searchable: true },
				// { data: 'invoice_id', name: 'invoice_id' },
				{ data: 'reservation_number', name: 'reservation_number' },
				// { data: 'trip_type', name: 'trip_type' },
				// { data: 'ticket_type', name: 'ticket_type' },
				{ data: 'booking_status', name: 'booking_status' },
				//{ data: 'booking_status', name: 'booking_status', orderable: false, searchable: false },
				{ data: 'created_at', name: 'created_at' },
				{ data: 'created_by', name: 'created_by', orderable: false, searchable: true },
				{ data: 'action', name: 'action', orderable: false, searchable: false }
			]
		});

		$(document).on('click', '.filter-data-btn', function(e) {
			e.preventDefault();

			var formData = [];

			// Handle all inputs (text, hidden, etc.)
			$('.filter-data-form').find('input, textarea').each(function() {
				var name = $(this).attr('name');
				var value = $(this).val();
				var isFixed = $(this).hasClass('fixed-value');

				// Include if:
				// - It has a name, and
				// - (Either it's not empty OR it’s marked as fixed-value)
				if (name && (value !== '' || isFixed)) {
					formData.push({ name: name, value: value });
				}
			});

			// Handle all selects (including Select2)
			$('.filter-data-form select').each(function() {
				var name = $(this).attr('name');
				if (!name) return;

				var values = $(this).val(); // array or string
				var isFixed = $(this).hasClass('fixed-value');

				if (values !== null && (values !== '' || isFixed)) {
					if (Array.isArray(values)) {
						values.forEach(function(val) {
							formData.push({ name: name + '[]', value: val });
						});
					} else {
						formData.push({ name: name, value: values });
					}
				}
			});

			// Convert to query string
			var query = $.param(formData);
			var newUrl = baseUrl + (queryString ? queryString + '&' : '?') + query;

			// Reload DataTable
			dataTable.ajax.url(newUrl).load();
		});


	});

</script>
@include('common._partials.tableAjaxScripts')
@endpush