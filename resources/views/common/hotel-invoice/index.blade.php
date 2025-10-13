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
					
					<li class="breadcrumb-item">{{ $getCurrentTranslation['hotel_invoice_list'] ?? 'hotel_invoice_list' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($createRoute) && !empty($createRoute))
					<a href="{{ $createRoute }}" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['add_new_invoice'] ?? 'add_new_invoice' }}</a>
				@endif
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						{{ $getCurrentTranslation['hotel_invoice_list'] ?? 'hotel_invoice_list' }}
					</h3>
				</div>
				<table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
					<thead>
						<tr>
							<th>#</th>
							<th>{{ $getCurrentTranslation['user'] ?? 'user' }}</th>
							<th>{{ $getCurrentTranslation['pin_number'] ?? 'pin_number' }}</th>
							<th>{{ $getCurrentTranslation['booking_number'] ?? 'booking_number' }}</th>
							<th>{{ $getCurrentTranslation['hotel'] ?? 'hotel' }}</th>
							<th>{{ $getCurrentTranslation['check_in_and_checkout'] ?? 'check_in_and_checkout' }}</th>
							<th>{{ $getCurrentTranslation['guest_info'] ?? 'guest_info' }}</th>
							<th>{{ $getCurrentTranslation['total_price'] ?? 'total_price' }}</th>
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

var queryString = window.location.search;
var baseUrl = '{{ $dataTableRoute }}';
// Combine base URL and current query string
var finalUrl = baseUrl + queryString;

var dataTable = $('#datatable').DataTable({
	processing: true,
	serverSide: true,
	ajax: finalUrl,
	searching: true,
	dom: 'lfrtip',
	columns: [
		{ data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },

		// User (FK from users table)
		{ data: 'user_id', name: 'user_id', orderable: false, searchable: true },

		// Pin number & booking number
		{ data: 'pin_number', name: 'pin_number' },
		{ data: 'booking_number', name: 'booking_number' },

		// Hotel info (name + maybe image)
		{ data: 'hotel_name', name: 'hotel_name' },

		// Check-in & Check-out (formatted column in datatable backend)
		{ data: 'check_in_and_checkout', name: 'check_in_and_checkout', orderable: false, searchable: false },

		// Guest Info (from JSON guestInfo)
		{ data: 'guest_info', name: 'guest_info', orderable: false, searchable: false },

		// Total price
		{ data: 'total_price', name: 'total_price' },

		// Payment status (enum: Paid/Unpaid)
		// { data: 'payment_status', name: 'payment_status' },

		// Created date
		{ data: 'created_at', name: 'created_at' },

		// Created by (FK user)
		{ data: 'created_by', name: 'created_by', orderable: false, searchable: true },

		// Actions (edit/delete buttons)
		{ data: 'action', name: 'action', orderable: false, searchable: false }
	]

});

</script>
@include('common._partials.tableAjaxScripts')
@endpush