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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['expense_report'] ?? 'Expense Report' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@php
					$exportUrl = route('admin.expense.exportPdf', request()->all());
				@endphp
				<a href="{{ $exportUrl }}" class="btn btn-sm fw-bold btn-danger" target="_blank">
					<i class="fas fa-file-pdf"></i> {{ $getCurrentTranslation['export_pdf'] ?? 'Export PDF' }}
				</a>
			</div>
		</div>
	</div>

	<style>
		.table.report-table th,
		.table.report-table td{
			padding-left: 15px;
			padding-right: 15px;
		}
	</style>

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
								<form class="filter-data-form" method="get">
									<div class="row">
										<div class="col-md-3 mb-3">
											<div class="input-item-wrap mb-5">
												<label class="form-label">{{ $getCurrentTranslation['date_range'] ?? 'date_range' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														$selectedDateRange = $defaultDateRange ?? '';
													@endphp
													<div class="cursor-pointer dateRangePicker {{$selectedDateRange ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>
														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="date_range" data-value="{{$selectedDateRange ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['expense_category'] ?? 'Expense Category' }}:</label>
												<select class="form-select" name="category_id" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
													<option value="all" {{ (isset($categoryId) && $categoryId === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all'] ?? 'All' }}
													</option>
													@foreach($categories as $category)
														<option value="{{ $category->id }}" {{ (isset($categoryId) && $categoryId == $category->id) ? 'selected' : '' }}>
															{{ $category->name }}
														</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['for'] ?? 'For' }}:</label>
												<select class="form-select" name="for_user_id" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
													<option value="all" {{ (isset($forUserId) && $forUserId === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all'] ?? 'All' }}
													</option>
													@foreach($users as $user)
														<option value="{{ $user->id }}" {{ (isset($forUserId) && $forUserId == $user->id) ? 'selected' : '' }}>
															{{ $user->name }}
															@if($user->designation)
																({{ $user->designation->name }})
															@endif
														</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}:</label>
												<select class="form-select" name="payment_status" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
													<option value="all" {{ (isset($paymentStatus) && $paymentStatus === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all'] ?? 'All' }}
													</option>
													<option value="Paid" {{ (isset($paymentStatus) && $paymentStatus == 'Paid') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['paid'] ?? 'Paid' }}
													</option>
													<option value="Unpaid" {{ (isset($paymentStatus) && $paymentStatus == 'Unpaid') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['unpaid'] ?? 'Unpaid' }}
													</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-0">
												<a class="btn btn-secondary btn-sm me-3" href="{{ route('admin.expense.report') }}">
													{{ $getCurrentTranslation['reset'] ?? 'reset' }}
												</a>
												<button type="submit" class="btn btn-primary btn-sm">
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
						{{ $getCurrentTranslation['expense_report'] ?? 'Expense Report' }}
					</h3>
				</div>
				<div class="card-body px-0">
					{{-- ================= SUMMARY CARDS ================= --}}
					<div class="row">
						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_expenses'] ?? 'Total Expenses' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ $totalCount }}</h2>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-danger text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_amount'] ?? 'Total Amount' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-danger">{{ number_format($totalAmount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-success text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-success">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
									<small class="text-muted">({{ $paidCount }} {{ $getCurrentTranslation['expenses'] ?? 'expenses' }})</small>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-warning text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-warning">{{ number_format($totalUnpaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
									<small class="text-muted">({{ $unpaidCount }} {{ $getCurrentTranslation['expenses'] ?? 'expenses' }})</small>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= EXPENSE TABLE ================= --}}
					<div class="table-responsive mt-5">
						<table class="table table-bordered table-striped table-hover align-middle mb-0 report-table">
							<thead class="table-secondary">
								<tr>
									<th class="fw-semibold ps-3">#</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['expense_category'] ?? 'Expense Category' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['for'] ?? 'For' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['title'] ?? 'Title' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['amount'] ?? 'Amount' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['expense_date'] ?? 'Expense Date' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_method'] ?? 'Payment Method' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['created_by'] ?? 'Created By' }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($expenses as $expense)
									<tr>
										<td class="ps-3">{{ $loop->iteration }}</td>
										<td>{{ $expense->category->name ?? 'N/A' }}</td>
										<td>
											@if($expense->forUser)
												{{ $expense->forUser->name }}
												@if($expense->forUser->designation)
													<br><small class="text-muted">({{ $expense->forUser->designation->name }})</small>
												@endif
											@else
												<span class="text-muted">N/A</span>
											@endif
										</td>
										<td>{{ $expense->title }}</td>
										<td class="fw-bold text-danger">{{ number_format($expense->amount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
										<td>{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') : 'N/A' }}</td>
										<td>{{ $expense->payment_method ?? 'N/A' }}</td>
										<td>
											<span class="badge 
												@if($expense->payment_status == 'Paid') badge-success
												@else badge-danger
												@endif">
												{{ $expense->payment_status }}
											</span>
										</td>
										<td>{{ $expense->creator->name ?? 'N/A' }}</td>
									</tr>
								@empty
									<tr>
										<td colspan="9" class="p-10 text-center">
											{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}
										</td>
									</tr>
								@endforelse
							</tbody>
							@if($expenses->count() > 0)
							<tfoot class="table-secondary">
								<tr>
									<td colspan="4" class="fw-bold text-end ps-3">
										{{ $getCurrentTranslation['total'] ?? 'Total' }}:
									</td>
									<td class="fw-bold">
										<strong class="text-danger">{{ number_format($totalAmount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
									</td>
									<td colspan="4"></td>
								</tr>
							</tfoot>
							@endif
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.dateRangeScripts')
@endpush
