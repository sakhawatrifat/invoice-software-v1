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
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item">{{ $getCurrentTranslation['sticky_note_list'] ?? 'sticky_note_list' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($createRoute) && !empty($createRoute))
					<a href="{{ $createRoute }}" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['add_new'] ?? 'add_new' }}</a>
				@endif
			</div>
		</div>
	</div>

	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<!--begin::Accordion-->
			<div class="card rounded border mt-5 p-0 bg-white">
				<div class="accordion" id="kt_accordion_sticky_note_filter">
					<div class="accordion-item">
						<h2 class="accordion-header" id="kt_accordion_sticky_note_filter_header">
							<button class="accordion-button fs-4 fw-semibold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_sticky_note_filter_body" aria-expanded="true" aria-controls="kt_accordion_sticky_note_filter_body">
								<i class="fa fa-filter" aria-hidden="true"></i> &nbsp;
								{{ $getCurrentTranslation['filter'] ?? 'filter' }}
							</button>
						</h2>
						<div id="kt_accordion_sticky_note_filter_body" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_sticky_note_filter_header" data-bs-parent="#kt_accordion_sticky_note_filter">
							<div class="accordion-body">
								<form class="filter-data-form">
									<div class="row">
										<div class="col-md-3 mb-2">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['priority'] ?? 'priority' }}:</label>
												<select class="form-select" name="priority" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_priority'] ?? 'select_priority' }}">
													<option value="">{{ $getCurrentTranslation['all'] ?? 'All' }}</option>
													<option value="Highest" {{ request()->priority == 'Highest' ? 'selected' : '' }}>Highest</option>
													<option value="Medium" {{ request()->priority == 'Medium' ? 'selected' : '' }}>Medium</option>
													<option value="Lower" {{ request()->priority == 'Lower' ? 'selected' : '' }}>Lower</option>
													<option value="Optional" {{ request()->priority == 'Optional' ? 'selected' : '' }}>Optional</option>
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-2">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['assigned_users'] ?? 'assigned_users' }}:</label>
												<select class="form-select" name="assigned_user_id" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_user'] ?? 'select_user' }}">
													<option value="">{{ $getCurrentTranslation['all_users'] ?? 'All Users' }}</option>
													@foreach($filterUsers ?? [] as $u)
														<option value="{{ $u->id }}" {{ request()->assigned_user_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-2">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['deadline_daterange'] ?? 'Deadline Daterange' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													<div class="cursor-pointer dateRangePicker future-date {{ request()->deadline_date_range ? 'filled' : 'empty' }}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>
														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="deadline_date_range" data-value="{{ request()->deadline_date_range ?? '' }}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-3 mb-2">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['reminder_daterange'] ?? 'Reminder Daterange' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													<div class="cursor-pointer dateRangePicker future-date {{ request()->reminder_date_range ? 'filled' : 'empty' }}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>
														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="reminder_date_range" data-value="{{ request()->reminder_date_range ?? '' }}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-3 mb-2">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}:</label>
												<select class="form-select" name="status" data-control="select2" data-placeholder="{{ $getCurrentTranslation['all_statuses'] ?? 'All Statuses' }}">
													<option value="">{{ $getCurrentTranslation['all_statuses'] ?? 'All Statuses' }}</option>
													<option value="Pending" {{ request()->status == 'Pending' ? 'selected' : '' }}>{{ $getCurrentTranslation['pending'] ?? 'Pending' }}</option>
													<option value="In Progress" {{ request()->status == 'In Progress' ? 'selected' : '' }}>{{ $getCurrentTranslation['in_progress'] ?? 'In Progress' }}</option>
													<option value="Completed" {{ request()->status == 'Completed' ? 'selected' : '' }}>{{ $getCurrentTranslation['completed'] ?? 'Completed' }}</option>
													<option value="Cancelled" {{ request()->status == 'Cancelled' ? 'selected' : '' }}>{{ $getCurrentTranslation['cancelled'] ?? 'Cancelled' }}</option>
												</select>
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
						{{ $getCurrentTranslation['sticky_note_list'] ?? 'sticky_note_list' }}
					</h3>
				</div>
				<table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
					<thead>
						<tr>
							<th>#</th>
							<th>{{ $getCurrentTranslation['note_title'] ?? 'note_title' }}</th>
							<th>{{ $getCurrentTranslation['deadline'] ?? 'deadline' }}</th>
							<th>{{ $getCurrentTranslation['reminder_datetime'] ?? 'reminder_datetime' }}</th>
							<th>{{ $getCurrentTranslation['status'] ?? 'status' }}</th>
							<th>{{ $getCurrentTranslation['priority'] ?? 'priority' }}</th>
							<th>{{ $getCurrentTranslation['assigned_users'] ?? 'assigned_users' }}</th>
							<th>{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.formScripts')
<script>
var dataTable;
var baseUrl = '{{ $dataTableRoute }}';
$(document).ready(function() {
	var queryString = window.location.search;
	var formData = $('.filter-data-form').serialize();
	var finalUrl = baseUrl;
	if (queryString) {
		finalUrl += queryString + (formData ? '&' + formData : '');
	} else if (formData) {
		finalUrl += '?' + formData;
	}

	dataTable = $('#datatable').DataTable({
		processing: true,
		serverSide: true,
		ajax: finalUrl,
		searching: true,
		dom: 'lfrtip',
		order: [[1, 'asc']],
		columns: [
			{ data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
			{ data: 'note_title', name: 'note_title' },
			{ data: 'deadline', name: 'deadline' },
			{ data: 'reminder_datetime', name: 'reminder_datetime' },
			{ data: 'status', name: 'status', orderable: false, searchable: false },
			{ data: 'priority', name: 'priority', orderable: false, searchable: false },
			{ data: 'assigned_users', name: 'assigned_users', orderable: false, searchable: false },
			{ data: 'action', name: 'action', orderable: false, searchable: false }
		]
	});

	$(document).on('click', '.filter-data-btn', function(e) {
		e.preventDefault();
		var formData = [];
		$('.filter-data-form').find('input, textarea').each(function() {
			var name = $(this).attr('name');
			var value = $(this).val();
			var isFixed = $(this).hasClass('fixed-value');
			if (name && (value !== '' || isFixed)) {
				formData.push({ name: name, value: value });
			}
		});
		$('.filter-data-form select').each(function() {
			var name = $(this).attr('name');
			if (!name) return;
			var values = $(this).val();
			var isFixed = $(this).hasClass('fixed-value');
			if (values !== null && (values !== '' || isFixed)) {
				if (Array.isArray(values)) {
					values.forEach(function(val) { formData.push({ name: name + '[]', value: val }); });
				} else {
					formData.push({ name: name, value: values });
				}
			}
		});
		var query = $.param(formData);
		var newUrl = baseUrl + (query ? '?' + query : '');
		dataTable.ajax.url(newUrl).load();
	});

	$('.filter-reset-btn').on('click', function() {
		setTimeout(function() {
			dataTable.ajax.url(baseUrl).load();
		}, 100);
	});
});
</script>
@include('common._partials.tableAjaxScripts')
@endpush
