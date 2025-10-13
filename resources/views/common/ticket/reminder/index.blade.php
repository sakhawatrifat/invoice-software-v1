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
					
					<li class="breadcrumb-item">{{ $getCurrentTranslation['ticket_reminder'] ?? 'ticket_reminder' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				{{-- @if((isset($createRoute) && !empty($createRoute)))
					<div class="btn-group">
						<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
							{{ $getCurrentTranslation['add_new'] ?? 'add_new' }}
						</button>
						<div class="dropdown-menu p-0">
							<a href="{{ $createRoute }}?document_type=ticket" class="dropdown-item btn btn-sm fw-bold btn-success">{{ $getCurrentTranslation['ticket'] ?? 'ticket' }}</a>
							<a href="{{ $createRoute }}?document_type=invoice" class="dropdown-item btn btn-sm fw-bold btn-info">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</a>
						</div>
					</div>
				@endif --}}
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						{{ $getCurrentTranslation['passenger_list_for_upcomming_flights'] ?? 'passenger_list_for_upcomming_flights' }}
					</h3>
				</div>
            <table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
				<thead>
					<tr>
						<th>#</th>
						<th>{{ $getCurrentTranslation['company'] ?? 'company' }}</th>
						<th>{{ $getCurrentTranslation['passenger'] ?? 'passenger' }}</th>
						<th>{{ $getCurrentTranslation['email'] ?? 'email' }}</th>
						<th>{{ $getCurrentTranslation['phone'] ?? 'phone' }}</th>
						<th>{{ $getCurrentTranslation['flight_date_time'] ?? 'flight_date_time' }}</th>
						<th>{{ $getCurrentTranslation['created_at'] ?? 'created_at' }}</th>
						<th>{{ $getCurrentTranslation['created_by'] ?? 'created_by' }}</th>
						<th>{{ $getCurrentTranslation['reminder_status'] ?? 'reminder_status' }}</th>
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
		{ data: 'user_id', name: 'user_id' },
		{ data: 'name', name: 'name', orderable: true, searchable: true },
		{ data: 'email', name: 'email' },
		{ data: 'phone', name: 'phone' },
		{ data: 'flight_date_time', name: 'flight_date_time', orderable: false, searchable: false },
		{ data: 'created_at', name: 'created_at' },
		{ data: 'created_by', name: 'created_by', orderable: false, searchable: true },
		{ data: 'reminder_status', name: 'reminder_status', orderable: false, searchable: false },
		{ data: 'action', name: 'action', orderable: false, searchable: false }
	]
});

</script>
@include('common._partials.tableAjaxScripts')
@endpush