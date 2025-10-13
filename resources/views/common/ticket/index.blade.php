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
		{ data: 'document_type', name: 'document_type' },
		{ data: 'user_id', name: 'user_id', orderable: false, searchable: true },
		{ data: 'invoice_date', name: 'invoice_date' },
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

</script>
@include('common._partials.tableAjaxScripts')
@endpush