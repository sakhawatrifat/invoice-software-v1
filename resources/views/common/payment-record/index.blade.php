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
					
					<li class="breadcrumb-item">{{ $getCurrentTranslation['payment_list'] ?? 'payment_list' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($createRoute) && !empty($createRoute))
					<a href="{{ $createRoute }}" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['add_new_payment'] ?? 'add_new_payment' }}</a>
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
								Filters
							</button>
						</h2>
						<div id="kt_accordion_1_body_1" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_1_header_1" data-bs-parent="#kt_accordion_1">
							<div class="accordion-body">
								<form class="filter-data-form">
									<div class="row">
										<div class="col-md-3">
											<div class="input-item">
												@php
													$options = getWhereInModelData('IntroductionSource', 'status', [1]);
													$selected = request()->introduction_source_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['introduction_source_label'] ?? 'introduction_source_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['introduction_source_placeholder'] ?? 'introduction_source_placeholder' }}" name="introduction_source_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = getAllModelData('Country');

													$selected = request()->customer_country_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['customer_country_label'] ?? 'customer_country_label' }}:</label>
												<select class="form-select select2-with-images" data-class="flag" data-placeholder="{{ $getCurrentTranslation['customer_country_placeholder'] ?? 'customer_country_placeholder' }}" name="customer_country_id" >
													<option value="">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" data-image="{{ getStaticFile('flags', strtolower($option->short_name))}}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item">
												@php
													// Fetch active suppliers
													$options = getWhereInModelData('IssuedSupplier', 'status', [1]);

													// Ensure $selected is an array (from editData)
													$selected = request()->issued_supplier_ids ?? [];
													if (!is_array($selected)) {
														$selected = json_decode($selected, true) ?? [];
													}
												@endphp

												<label class="form-label">
													{{ $getCurrentTranslation['issued_supplier_label'] ?? 'issued_supplier_label' }}:
												</label>

												<select class="form-select" 
														data-control="select2" 
														data-placeholder="{{ $getCurrentTranslation['issued_supplier_placeholder'] ?? 'issued_supplier_placeholder' }}" 
														name="issued_supplier_ids[]" 
														multiple>
													@foreach($options as $option)
														<option value="{{ $option->id }}" 
															{{ in_array($option->id, $selected) ? 'selected' : '' }}>
															{{ $option->name }}
														</option>
													@endforeach
												</select>
											</div>
										</div>


										<div class="col-md-3">
											<div class="input-item">
												@php
													$options = getWhereInModelData('IssuedBy', 'status', [1]);
													$selected = request()->issued_by_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['issued_by_label'] ?? 'issued_by_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['issued_by_placeholder'] ?? 'issued_by_placeholder' }}" name="issued_by_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
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

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = getCities();

													$selected = request()->departure ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['departure_label'] ?? 'departure_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['departure_placeholder'] ?? 'departure_placeholder' }}" name="departure">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('departure')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = getCities();

													$selected = request()->destination ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['destination_label'] ?? 'destination_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['destination_placeholder'] ?? 'destination_placeholder' }}" name="destination">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('destination')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-3">
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

										<div class="col-md-3">
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

										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-5">
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
						{{ $getCurrentTranslation['payment_list'] ?? 'payment_list' }}
					</h3>
				</div>
				<table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
					<thead>
						<tr>
							<th>#</th>
							<th>{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</th>
							<th>{{ $getCurrentTranslation['client_info'] ?? 'client_info' }}</th>
							<th>{{ $getCurrentTranslation['trip_info'] ?? 'trip_info' }}</th>
							<th>{{ $getCurrentTranslation['total_price'] ?? 'total_price' }}</th>
							<th>{{ $getCurrentTranslation['issued_by'] ?? 'issued_by' }}</th>
							{{-- <th>{{ $getCurrentTranslation['payment_status'] ?? 'payment_status' }}</th> --}}
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
				{ data: 'payment_invoice_id', name: 'payment_invoice_id' },
				{ data: 'client_name', name: 'client_name' },
				{ data: 'trip_type', name: 'trip_type' },
				{ data: 'total_selling_price', name: 'total_selling_price' },
				{ data: 'issued_by_id', name: 'issued_by_id' },
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
				if (name && $(this).val() !== '') {
					formData.push({ name: name, value: $(this).val() });
				}
			});

			// Handle all selects, including Select2
			$('.filter-data-form select').each(function() {
				var name = $(this).attr('name');
				if (!name) return;

				var values = $(this).val(); // array for multi-select, string for single select
				if (values !== null && values !== '') {
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
			var query = $.param(formData); // properly encodes arrays and single values
			var newUrl = baseUrl + (queryString ? queryString + '&' : '?') + query;

			// Reload DataTable
			dataTable.ajax.url(newUrl).load();
		});

	});


</script>
@include('common._partials.tableAjaxScripts')
@endpush