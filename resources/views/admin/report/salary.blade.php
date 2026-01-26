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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['salary_report'] ?? 'Salary Report' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@php
					$exportUrl = route('admin.salary.exportPdf', request()->all());
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
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['employee'] ?? 'Employee' }}:</label>
												<select class="form-select" name="employee_id">
													<option value="all" {{ (isset($employeeId) && $employeeId === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all_employee'] ?? 'All Employee' }}
													</option>
													@foreach($employees as $employee)
														<option value="{{ $employee->id }}" {{ (isset($employeeId) && $employeeId == $employee->id) ? 'selected' : '' }}>
															{{ $employee->name }}
															@if($employee->designation)
																({{ $employee->designation->name }})
															@endif
															@if($employee->is_staff == 0)
																- {{ $getCurrentTranslation['non_staff'] ?? 'Non Staff' }}
															@endif
														</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['month'] ?? 'Month' }}:</label>
												<select class="form-select" name="month">
													<option value="all" {{ (isset($month) && $month === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all_month'] ?? 'All Month' }}
													</option>
													@for($i = 1; $i <= 12; $i++)
														<option value="{{ $i }}" {{ (isset($month) && $month == $i) ? 'selected' : '' }}>
															{{ $monthNames[$i] }}
														</option>
													@endfor
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['year'] ?? 'Year' }}:</label>
												<select class="form-select" name="year">
													<option value="all" {{ (isset($year) && $year === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all_year'] ?? 'All Year' }}
													</option>
													@for($i = Carbon\Carbon::now()->year; $i >= Carbon\Carbon::now()->year - 5; $i--)
														<option value="{{ $i }}" {{ (isset($year) && $year == $i) ? 'selected' : '' }}>
															{{ $i }}
														</option>
													@endfor
												</select>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}:</label>
												<select class="form-select" name="payment_status">
													<option value="all" {{ (isset($paymentStatus) && $paymentStatus === 'all') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all'] ?? 'All' }}
													</option>
													<option value="Paid" {{ (isset($paymentStatus) && $paymentStatus == 'Paid') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['paid'] ?? 'Paid' }}
													</option>
													<option value="Partial" {{ (isset($paymentStatus) && $paymentStatus == 'Partial') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['partial'] ?? 'Partial' }}
													</option>
													<option value="Unpaid" {{ (isset($paymentStatus) && $paymentStatus == 'Unpaid') ? 'selected' : '' }}>
														{{ $getCurrentTranslation['unpaid'] ?? 'Unpaid' }}
													</option>
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-0">
												<a class="btn btn-secondary btn-sm me-3" href="{{ route('admin.salary.report') }}">
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
						{{ $getCurrentTranslation['salary_report'] ?? 'Salary Report' }}
					</h3>
				</div>
				<div class="card-body px-0">
					{{-- ================= SUMMARY CARDS ================= --}}
					<div class="row">
						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_base_salary'] ?? 'Total Base Salary' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ number_format($totalBaseSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-danger text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_deductions'] ?? 'Total Deductions' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-danger">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-success text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_bonus'] ?? 'Total Bonus' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-success">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>

						<div class="col-md-3 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-info text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_net_salary'] ?? 'Total Net Salary' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-info">{{ number_format($totalNetSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= PAYMENT STATUS SUMMARY ================= --}}
					<div class="row">
						<div class="col-md-4 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-success text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_paid'] ?? 'Total Paid' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-success">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>

						<div class="col-md-4 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-warning text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_partial'] ?? 'Total Partial' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-warning">{{ number_format($totalPartial, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>

						<div class="col-md-4 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-danger text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_unpaid'] ?? 'Total Unpaid' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-danger">{{ number_format($totalUnpaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</h2>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= SALARY TABLE ================= --}}
					<div class="table-responsive mt-5">
						<table class="table table-bordered table-striped table-hover align-middle mb-0 report-table">
							<thead class="table-secondary">
								<tr>
									<th class="fw-semibold ps-3">#</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['employee'] ?? 'employee' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['designation'] ?? 'designation' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['month'] ?? 'Month' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['year'] ?? 'Year' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['action'] ?? 'Action' }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($salaries as $salary)
									<tr>
										<td class="ps-3">{{ $loop->iteration }}</td>
										<td><strong>{{ $salary->employee->name ?? 'N/A' }}</strong></td>
										<td>{{ $salary->employee->designation->name ?? 'N/A' }}</td>
										<td>{{ $monthNames[$salary->month] ?? $salary->month }}</td>
										<td>{{ $salary->year }}</td>
										<td>{{ number_format($salary->base_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
										<td>
											@if($salary->deductions > 0)
												<span class="text-danger">{{ number_format($salary->deductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@else
												<span class="text-muted">0.00 ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@endif
										</td>
										<td>
											@if($salary->bonus > 0)
												<span class="text-success">{{ number_format($salary->bonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@else
												<span class="text-muted">0.00 ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@endif
										</td>
										<td class="fw-bold">{{ number_format($salary->net_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
										<td>
											<span class="badge 
												@if($salary->payment_status == 'Paid') badge-success
												@elseif($salary->payment_status == 'Partial') badge-warning
												@else badge-danger
												@endif">
												{{ $salary->payment_status }}
											</span>
										</td>
										<td>
											@if($salary->payment_date)
												{{ \Carbon\Carbon::parse($salary->payment_date)->format('Y-m-d') }}
											@else
												<span class="text-muted">-</span>
											@endif
										</td>
										<td>
											<a href="{{ route('admin.salary.exportPayslip', $salary->id) }}" class="btn btn-sm btn-danger" target="_blank" title="{{ $getCurrentTranslation['export_payslip'] ?? 'Export Payslip' }}">
												<i class="fas fa-file-pdf"></i> {{ $getCurrentTranslation['payslip'] ?? 'Payslip' }}
											</a>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="12" class="p-10 text-center">
											{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}
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
							@endphp
							<tfoot class="table-secondary">
								<tr>
									<td colspan="5" class="fw-bold text-end ps-3">
										{{ $getCurrentTranslation['total'] ?? 'Total' }}:
									</td>
									<td class="fw-bold">
										<strong>{{ number_format($totalBaseSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
									</td>
									<td class="fw-bold">
										@if($totalDeductions > 0)
											<strong class="text-danger">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@else
											<strong class="text-muted">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@endif
									</td>
									<td class="fw-bold">
										@if($totalBonus > 0)
											<strong class="text-success">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@else
											<strong class="text-muted">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@endif
									</td>
									<td class="fw-bold">
										<strong>{{ number_format($totalNetSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
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
@endsection
