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
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'Dashboard' }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item text-muted">
						<a href="{{ $type === 'email' ? route('marketing.email.form') : route('marketing.whatsapp.form') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation[$type === 'email' ? 'email_marketing' : 'whatsapp_marketing'] ?? ($type === 'email' ? 'Email Marketing' : 'WhatsApp Marketing') }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item">{{ $pageTitle ?? '' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if($type === 'email')
					<a href="{{ route('marketing.email.form') }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-envelope"></i>
						{{ $getCurrentTranslation['compose_email'] ?? 'Compose Email' }}
					</a>
				@else
					<a href="{{ route('marketing.whatsapp.form') }}" class="btn btn-sm fw-bold btn-success">
						<i class="fa-brands fa-whatsapp"></i>
						{{ $getCurrentTranslation['compose_whatsapp'] ?? 'Compose WhatsApp' }}
					</a>
				@endif
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<!-- Filter accordion (same design as missing-ticket-payments) -->
			<div class="card rounded border mt-5 p-0 bg-white">
				<div class="accordion" id="kt_accordion_marketing_sent">
					<div class="accordion-item">
						<h2 class="accordion-header" id="kt_accordion_marketing_sent_header_1">
							<button class="accordion-button fs-4 fw-semibold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_marketing_sent_body_1" aria-expanded="true" aria-controls="kt_accordion_marketing_sent_body_1">
								<i class="fa fa-filter" aria-hidden="true"></i> &nbsp;
								{{ $getCurrentTranslation['filter'] ?? 'Filter' }}
							</button>
						</h2>
						<div id="kt_accordion_marketing_sent_body_1" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_marketing_sent_header_1" data-bs-parent="#kt_accordion_marketing_sent">
							<div class="accordion-body">
								<form class="filter-data-form">
									<div class="row">
										<div class="col-md-4">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['sent_date_range_label'] ?? 'Sent Date Range' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														$selectedSentDateRange = request()->sent_date_range ?? ($defaultSentDateRange ?? null);
													@endphp
													<div class="cursor-pointer dateRangePicker {{ $selectedSentDateRange ? 'filled' : 'empty' }}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>
														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="sent_date_range" value="{{ $selectedSentDateRange ?? '' }}" data-value="{{ $selectedSentDateRange ?? '' }}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-0">
												<button type="reset" class="btn btn-secondary btn-sm filter-reset-btn datatable-filter me-3">
													{{ $getCurrentTranslation['reset'] ?? 'Reset' }}
												</button>
												<button type="button" class="btn btn-primary btn-sm filter-data-btn">
													{{ $getCurrentTranslation['filter'] ?? 'Filter' }}
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

			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">{{ $pageTitle ?? '' }}</h3>
				</div>
				<table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
					<thead>
						<tr>
							<th>#</th>
							<th>{{ $getCurrentTranslation['subject'] ?? 'Subject' }}</th>
							<th>{{ $getCurrentTranslation['recipients_count'] ?? 'Recipients' }}</th>
							<th>{{ $getCurrentTranslation['sent_date_time'] ?? 'Sent Date & Time' }}</th>
							<th>{{ $getCurrentTranslation['created_by'] ?? 'Created By' }}</th>
							<th>{{ $getCurrentTranslation['action'] ?? 'Action' }}</th>
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
$(document).ready(function() {
	var formData = $('.filter-data-form').serialize();
	var queryString = window.location.search;
	var baseUrl = '{{ $dataTableRoute }}';
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
		columns: [
			{ data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
			{ data: 'subject_short', name: 'subject' },
			{ data: 'recipients_count', name: 'recipients_count', orderable: false, searchable: false },
			{ data: 'sent_date_time_formatted', name: 'sent_date_time' },
			{ data: 'created_by_name', name: 'created_by_name', orderable: false, searchable: true },
			{ data: 'action', name: 'action', orderable: false, searchable: false }
		]
	});

	$(document).on('click', '.filter-data-btn', function(e) {
		e.preventDefault();
		var formDataArr = [];
		$('.filter-data-form').find('input, textarea').each(function() {
			var name = $(this).attr('name');
			var value = $(this).val();
			if (name && value !== '') {
				formDataArr.push({ name: name, value: value });
			}
		});
		$('.filter-data-form select').each(function() {
			var name = $(this).attr('name');
			if (!name) return;
			var values = $(this).val();
			if (values !== null && values !== '') {
				if (Array.isArray(values)) {
					values.forEach(function(val) { formDataArr.push({ name: name + '[]', value: val }); });
				} else {
					formDataArr.push({ name: name, value: values });
				}
			}
		});
		var query = $.param(formDataArr);
		var newUrl = baseUrl + (query ? '?' + query : '');
		dataTable.ajax.url(newUrl).load();
	});
});
setTimeout(function() {
	clearDateRange();
	dateRangePickerUpdate();
}, 500);
</script>
@include('common._partials.tableAjaxScripts')
@endpush
