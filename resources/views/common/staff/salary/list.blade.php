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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['salary_list'] ?? 'Salary List' }}</li>
				</ul>
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						{{ $getCurrentTranslation['salary_list'] ?? 'Salary List' }}
						@if($month && $year)
							- {{ $monthNames[$month] ?? $month }}/{{ $year }}
						@endif
					</h3>
				</div>
				<div class="card-body px-0">
					@if(session('success'))
						<div class="alert alert-success alert-dismissible fade show" role="alert">
							{{ session('success') }}
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					@endif
					@if(session('error'))
						<div class="alert alert-danger alert-dismissible fade show" role="alert">
							{{ session('error') }}
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>
					@endif
					{{-- Filter --}}
					<div class="row mb-5">
						<div class="col-md-12">
							<form method="GET" action="{{ route('staff.salary.list') }}">
								<div class="row">
									<div class="col-md-4">
										<div class="input-item">
											<label class="form-label">{{ $getCurrentTranslation['month'] ?? 'Month' }}:</label>
											<select class="form-select" name="month" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
												<option value="">-- {{ $getCurrentTranslation['select_month'] ?? 'Select Month' }} --</option>
												@for($i = 1; $i <= 12; $i++)
													<option value="{{ $i }}" {{ $i == $month ? 'selected' : '' }}>
														{{ $monthNames[$i] }}
													</option>
												@endfor
											</select>
										</div>
									</div>
									<div class="col-md-4">
										<div class="input-item">
											<label class="form-label">{{ $getCurrentTranslation['year'] ?? 'Year' }}:</label>
											<select class="form-select" name="year" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
												<option value="">-- {{ $getCurrentTranslation['select_year'] ?? 'Select Year' }} --</option>
												@for($i = Carbon\Carbon::now()->year; $i >= Carbon\Carbon::now()->year - 5; $i--)
													<option value="{{ $i }}" {{ $i == $year ? 'selected' : '' }}>
														{{ $i }}
													</option>
												@endfor
											</select>
										</div>
									</div>
									<div class="col-md-4 d-flex align-items-end">
										<button type="submit" class="btn btn-primary me-3">{{ $getCurrentTranslation['filter'] ?? 'Filter' }}</button>
										<a href="{{ route('staff.salary.list') }}" class="btn btn-secondary">{{ $getCurrentTranslation['reset'] ?? 'Reset' }}</a>
									</div>
								</div>
							</form>
						</div>
					</div>

					{{-- Salary Table --}}
					<div class="table-responsive">
						<table class="table table-bordered table-striped table-hover align-middle mb-0">
							<thead class="table-secondary">
								<tr>
									<th class="fw-semibold ps-3">#</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['month'] ?? 'Month' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['year'] ?? 'Year' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['paid_amount'] ?? 'Paid Amount' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['due_amount'] ?? 'Due Amount' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['action'] ?? 'Action' }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($salaries as $salary)
									<tr>
										<td>{{ $loop->iteration }}</td>
										<td>{{ $monthNames[$salary->month] ?? $salary->month }}</td>
										<td>{{ $salary->year }}</td>
										<td>{{ number_format($salary->base_salary, 2) }}</td>
										<td>
											@if($salary->deductions > 0)
												<span class="text-danger">-{{ number_format($salary->deductions, 2) }}</span>
												@if($salary->deduction_note)
													<button type="button" class="btn btn-sm btn-link p-0 ms-1 view-note-btn" data-note="{{ $salary->deduction_note }}" data-title="{{ $getCurrentTranslation['deduction_note'] ?? 'Deduction Note' }}">
														<i class="fas fa-info-circle text-info"></i>
													</button>
												@endif
											@else
												{{ number_format(0, 2) }}
											@endif
										</td>
										<td>
											@if($salary->bonus > 0)
												<span class="text-success">+{{ number_format($salary->bonus, 2) }}</span>
												@if($salary->bonus_note)
													<button type="button" class="btn btn-sm btn-link p-0 ms-1 view-note-btn" data-note="{{ $salary->bonus_note }}" data-title="{{ $getCurrentTranslation['bonus_note'] ?? 'Bonus Note' }}">
														<i class="fas fa-info-circle text-info"></i>
													</button>
												@endif
											@else
												{{ number_format(0, 2) }}
											@endif
										</td>
										<td class="fw-bold text-primary">{{ number_format($salary->net_salary, 2) }}</td>
										<td>
											<span class="text-success fw-bold">{{ number_format($salary->paid_amount ?? 0, 2) }}</span>
										</td>
										<td>
											@php
												$dueAmount = $salary->net_salary - ($salary->paid_amount ?? 0);
											@endphp
											@if($dueAmount > 0)
												<span class="text-danger fw-bold">{{ number_format($dueAmount, 2) }}</span>
											@else
												<span class="text-muted">{{ number_format(0, 2) }}</span>
											@endif
										</td>
										<td>
											<span class="badge 
												@if($salary->payment_status == 'Paid') badge-success
												@elseif($salary->payment_status == 'Partial') badge-warning
												@else badge-danger
												@endif">
												{{ $salary->payment_status }}
											</span>
										</td>
										<td>{{ $salary->payment_date ? $salary->payment_date->format('Y-m-d') : '-' }}</td>
										<td>
											<a href="{{ route('admin.salary.exportPayslip', $salary->id) }}" class="btn btn-sm btn-danger" target="_blank" title="{{ $getCurrentTranslation['export_payslip'] ?? 'Export Payslip' }}">
												<i class="fas fa-file-pdf"></i> {{ $getCurrentTranslation['payslip'] ?? 'Payslip' }}
											</a>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="11" class="text-center p-10">
											{{ $getCurrentTranslation['no_data_found_for_selected_filter'] ?? 'No data found for selected filter.' }}
										</td>
									</tr>
								@endforelse
							</tbody>
							@if($salaries->count() > 0)
							@php
								$totalBaseSalary = $salaries->sum('base_salary');
								$totalDeductions = $salaries->sum('deductions');
								$totalBonus = $salaries->sum('bonus');
								$totalNetSalary = $salaries->sum('net_salary');
								$totalPaid = $salaries->sum('paid_amount');
								$totalDue = $salaries->sum(function($salary) {
									return $salary->net_salary - ($salary->paid_amount ?? 0);
								});
							@endphp
							<tfoot class="table-secondary">
								<tr>
									<td colspan="3" class="fw-bold text-end ps-3">
										<strong>{{ $getCurrentTranslation['total'] ?? 'Total' }}:</strong>
									</td>
									<td class="fw-bold">
										<strong>{{ number_format($totalBaseSalary, 2) }}</strong>
									</td>
									<td class="fw-bold">
										@if($totalDeductions > 0)
											<strong class="text-danger">-{{ number_format($totalDeductions, 2) }}</strong>
										@else
											<strong class="text-muted">{{ number_format($totalDeductions, 2) }}</strong>
										@endif
									</td>
									<td class="fw-bold">
										@if($totalBonus > 0)
											<strong class="text-success">+{{ number_format($totalBonus, 2) }}</strong>
										@else
											<strong class="text-muted">{{ number_format($totalBonus, 2) }}</strong>
										@endif
									</td>
									<td class="fw-bold">
										<strong class="text-primary">{{ number_format($totalNetSalary, 2) }}</strong>
									</td>
									<td class="fw-bold">
										<strong class="text-success">{{ number_format($totalPaid, 2) }}</strong>
									</td>
									<td class="fw-bold">
										@if($totalDue > 0)
											<strong class="text-danger">{{ number_format($totalDue, 2) }}</strong>
										@else
											<strong class="text-muted">{{ number_format($totalDue, 2) }}</strong>
										@endif
									</td>
									<td colspan="3"></td>
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

{{-- Note Modal --}}
<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="noteModalLabel">{{ $getCurrentTranslation['note'] ?? 'Note' }}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<p id="noteContent"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $getCurrentTranslation['close'] ?? 'Close' }}</button>
			</div>
		</div>
	</div>
</div>


@endsection


@push('script')
<script>
	$(document).ready(function() {
		$('.view-note-btn').on('click', function() {
			var note = $(this).data('note');
			var title = $(this).data('title');
			$('#noteModalLabel').text(title);
			$('#noteContent').text(note);
			$('#noteModal').modal('show');
		});
	});
</script>
@endpush