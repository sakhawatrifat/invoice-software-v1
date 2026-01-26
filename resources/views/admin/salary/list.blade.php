@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
	$getCurrentTranslation = getCurrentTranslation();
	$monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				<a href="{{ route('admin.salary.index') }}" class="btn btn-sm fw-bold btn-primary">
					<i class="fas fa-plus"></i> {{ $getCurrentTranslation['generate_salary'] ?? 'Generate Salary' }}
				</a>
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
						@if(isset($month) && $month !== '' && $month !== 'all' && isset($year) && $year !== 'all')
							- {{ $monthNames[$month] ?? $month }}/{{ $year }}
						@elseif(isset($year) && $year !== 'all')
							- {{ $getCurrentTranslation['all_months'] ?? 'All Months' }}/{{ $year }}
						@elseif(isset($year) && $year === 'all')
							- {{ $getCurrentTranslation['all_years'] ?? 'All Years' }}
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
							<form method="GET" action="{{ route('admin.salary.list') }}">
								<div class="row">
									<div class="col-md-4">
										<div class="input-item">
											<label class="form-label">{{ $getCurrentTranslation['employee'] ?? 'Employee' }}:</label>
											<select class="form-select" name="employee_id" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
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
									<div class="col-md-4">
										<div class="input-item">
											<label class="form-label">{{ $getCurrentTranslation['month'] ?? 'Month' }}:</label>
											<select class="form-select" name="month[]" multiple data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
												@for($i = 1; $i <= 12; $i++)
													<option value="{{ $i }}" {{ (isset($months) && is_array($months) && in_array($i, $months)) ? 'selected' : '' }}>
														{{ $monthNames[$i] }}
													</option>
												@endfor
											</select>
										</div>
									</div>
									<div class="col-md-4">
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
									<div class="col-md-12 d-flex align-items-end justify-content-end my-4">
										<button type="submit" class="btn btn-primary btn-sm me-3">{{ $getCurrentTranslation['filter'] ?? 'Filter' }}</button>
										<a href="{{ route('admin.salary.list') }}" class="btn btn-secondary btn-sm">{{ $getCurrentTranslation['reset'] ?? 'Reset' }}</a>
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
									<th class="fw-semibold">{{ $getCurrentTranslation['employee'] ?? 'employee' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['designation'] ?? 'designation' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['paid_amount'] ?? 'Paid Amount' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['due_amount'] ?? 'Due Amount' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($salaries as $salary)
									<tr>
										<td class="ps-3">{{ $loop->iteration }}</td>
										<td><strong>{{ $salary->employee->name ?? 'N/A' }}</strong></td>
										<td>{{ $salary->employee->designation->name ?? 'N/A' }}</td>
										<td>{{ number_format($salary->base_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
										<td>
											@if($salary->deductions > 0)
												<span class="text-danger">{{ number_format($salary->deductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
												@if($salary->deduction_note)
													<br>
													<button type="button" class="btn btn-sm btn-link text-danger p-0 mt-1 view-note" 
														data-note="{{ $salary->deduction_note }}"
														data-title="{{ $getCurrentTranslation['deduction_note'] ?? 'Deduction Note' }}"
														data-bs-toggle="modal" 
														data-bs-target="#noteModal">
														<i class="fas fa-info-circle"></i> {{ $getCurrentTranslation['view_note'] ?? 'View Note' }}
													</button>
												@endif
											@else
												<span class="text-muted">0.00 ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@endif
										</td>
										<td>
											@if($salary->bonus > 0)
												<span class="text-success">{{ number_format($salary->bonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
												@if($salary->bonus_note)
													<br>
													<button type="button" class="btn btn-sm btn-link text-success p-0 mt-1 view-note" 
														data-note="{{ $salary->bonus_note }}"
														data-title="{{ $getCurrentTranslation['bonus_note'] ?? 'Bonus Note' }}"
														data-bs-toggle="modal" 
														data-bs-target="#noteModal">
														<i class="fas fa-info-circle"></i> {{ $getCurrentTranslation['view_note'] ?? 'View Note' }}
													</button>
												@endif
											@else
												<span class="text-muted">0.00 ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@endif
										</td>
										<td class="fw-bold">{{ number_format($salary->net_salary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</td>
										<td>
											<span class="text-success fw-bold">{{ number_format($salary->paid_amount ?? 0, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
										</td>
										<td>
											@php
												$dueAmount = $salary->net_salary - ($salary->paid_amount ?? 0);
											@endphp
											@if($dueAmount > 0)
												<span class="text-danger fw-bold">{{ number_format($dueAmount, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
											@else
												<span class="text-muted">0.00 ({{Auth::user()->company_data->currency->short_name ?? ''}})</span>
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
											<button type="button" class="btn btn-sm btn-info edit-salary" 
												data-salary-id="{{ $salary->id }}">
												<i class="fas fa-edit"></i> {{ $getCurrentTranslation['edit'] ?? 'Edit' }}
											</button>
											<button type="button" class="btn btn-sm btn-danger delete-salary" 
												data-salary-id="{{ $salary->id }}"
												data-employee-name="{{ $salary->employee->name ?? 'N/A' }}">
												<i class="fas fa-trash"></i> {{ $getCurrentTranslation['delete'] ?? 'Delete' }}
											</button>
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
							<tfoot class="table-secondary">
								<tr>
									<td colspan="3" class="fw-bold bg-secondary text-end ps-3">
										<strong>{{ $getCurrentTranslation['total'] ?? 'Total' }}:</strong>
									</td>
									<td class="fw-bold bg-secondary">
										<strong>{{ number_format($totalBaseSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
									</td>
									<td class="fw-bold bg-secondary">
										@if($totalDeductions > 0)
											<strong class="text-danger">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@else
											<strong class="text-muted">{{ number_format($totalDeductions, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@endif
									</td>
									<td class="fw-bold bg-secondary">
										@if($totalBonus > 0)
											<strong class="text-success">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@else
											<strong class="text-muted">{{ number_format($totalBonus, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@endif
									</td>
									<td class="fw-bold bg-secondary">
										<strong>{{ number_format($totalNetSalary, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
									</td>
									<td class="fw-bold bg-secondary">
										<strong class="text-success">{{ number_format($totalPaid, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
									</td>
									<td class="fw-bold bg-secondary">
										@if($totalDue > 0)
											<strong class="text-danger">{{ number_format($totalDue, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@else
											<strong class="text-muted">{{ number_format($totalDue, 2) }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</strong>
										@endif
									</td>
									<td colspan="3" class="bg-secondary"></td>
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
				<p id="noteContent" class="mb-0"></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $getCurrentTranslation['close'] ?? 'Close' }}</button>
			</div>
		</div>
	</div>
</div>

{{-- Edit Salary Modal --}}
<div class="modal fade" id="editSalaryModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">{{ $getCurrentTranslation['edit_salary'] ?? 'Edit Salary' }}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="editSalaryContent">
				<div class="text-center">
					<div class="spinner-border" role="status">
						<span class="visually-hidden">Loading...</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
<script>
$(document).ready(function() {
	let currentSalaryId = null;
	
	// Handle note modal
	$('#noteModal').on('show.bs.modal', function (event) {
		const button = $(event.relatedTarget);
		const note = button.data('note');
		const title = button.data('title');
		const modal = $(this);
		modal.find('.modal-title').text(title);
		modal.find('#noteContent').text(note);
	});
	
	$('.edit-salary').on('click', function() {
		currentSalaryId = $(this).data('salary-id');
		
		$('#editSalaryModal').modal('show');
		$('#editSalaryContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
		
		$('.r-preloader').show();
		
		$.ajax({
			url: '{{ route("admin.salary.getDetails", ":id") }}'.replace(':id', currentSalaryId),
			method: 'GET',
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			success: function(response) {
				$('.r-preloader').hide();
				if (response.success) {
					const salary = response.data;
					const currency = '{{Auth::user()->company_data->currency->short_name ?? ''}}';
					const formHtml = `
						<form id="updateSalaryForm">
							<input type="hidden" name="_method" value="PUT">
							<div class="row">
								<div class="col-md-12 mb-3">
									<strong>{{ $getCurrentTranslation['employee'] ?? 'employee' }}:</strong> ${salary.employee.name}
									${salary.employee.designation ? '<br><small class="text-muted">(' + salary.employee.designation.name + ')</small>' : ''}
								</div>
								<div class="col-md-12 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['base_salary'] ?? 'Base Salary' }} (${currency}):</label>
									<input type="text" min="0" class="form-control number-validate" name="base_salary" value="${salary.base_salary}" required>
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['deductions'] ?? 'Deductions' }} (${currency}):</label>
									<input type="text" min="0" class="form-control number-validate" name="deductions" value="${salary.deductions || 0}">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['deduction_note'] ?? 'Deduction Note' }}:</label>
									<textarea class="form-control" name="deduction_note" rows="1">${salary.deduction_note || ''}</textarea>
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['bonus'] ?? 'Bonus' }} (${currency}):</label>
									<input type="text" min="0" class="form-control number-validate" name="bonus" value="${salary.bonus || 0}">
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['bonus_note'] ?? 'Bonus Note' }}:</label>
									<textarea class="form-control" name="bonus_note" rows="1">${salary.bonus_note || ''}</textarea>
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['payment_status'] ?? 'Payment Status' }}:</label>
									<select class="form-select" name="payment_status" id="paymentStatusSelect" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
										<option value="Unpaid" ${salary.payment_status == 'Unpaid' ? 'selected' : ''}>Unpaid</option>
										<option value="Partial" ${salary.payment_status == 'Partial' ? 'selected' : ''}>Partial</option>
										<option value="Paid" ${salary.payment_status == 'Paid' ? 'selected' : ''}>Paid</option>
									</select>
								</div>
								<div class="col-md-6 mb-3" id="paymentMethodContainer" style="display: none;">
									<label class="form-label">{{ $getCurrentTranslation['payment_method'] ?? 'Payment Method' }}: <span class="text-danger">*</span></label>
									<select class="form-select" name="payment_method" id="paymentMethodSelect" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
										<option value="">-- {{ $getCurrentTranslation['select_payment_method'] ?? 'Select Payment Method' }} --</option>
										<option value="Bank Transfer" ${salary.payment_method == 'Bank Transfer' ? 'selected' : ''}>Bank Transfer</option>
										<option value="Card Payments" ${salary.payment_method == 'Card Payments' ? 'selected' : ''}>Card Payments</option>
										<option value="Cheque" ${salary.payment_method == 'Cheque' ? 'selected' : ''}>Cheque</option>
										<option value="bKash" ${salary.payment_method == 'bKash' ? 'selected' : ''}>bKash</option>
										<option value="Nagad" ${salary.payment_method == 'Nagad' ? 'selected' : ''}>Nagad</option>
										<option value="Rocket" ${salary.payment_method == 'Rocket' ? 'selected' : ''}>Rocket</option>
										<option value="Upay" ${salary.payment_method == 'Upay' ? 'selected' : ''}>Upay</option>
									</select>
								</div>
								<div class="col-md-6 mb-3" id="paidAmountContainer" style="display: none;">
									<label class="form-label">{{ $getCurrentTranslation['paid_amount'] ?? 'Paid Amount' }} (${currency}): <span class="text-danger">*</span></label>
									<input type="text" class="form-control number-validate" name="paid_amount" id="paidAmountInput" value="${salary.paid_amount || 0}" placeholder="0.00">
									<small class="text-muted">{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}: <span id="netSalaryDisplay">${(parseFloat(salary.base_salary || 0) - parseFloat(salary.deductions || 0) + parseFloat(salary.bonus || 0)).toFixed(2)}</span> ${currency}</small>
								</div>
								<div class="col-md-6 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['payment_date'] ?? 'Payment Date' }}: <span id="paymentDateRequired" class="text-danger d-none">*</span></label>
									<input type="text" placeholder="{{ $getCurrentTranslation['date_placeholder'] ?? 'date_placeholder' }}" class="form-control mb-2 flatpickr-input date" name="payment_date" id="paymentDateInput" value="${salary.payment_date || ''}">
								</div>
								<div class="col-md-12 mb-3">
									<label class="form-label">{{ $getCurrentTranslation['payment_note'] ?? 'Payment Note' }}:</label>
									<textarea class="form-control" name="payment_note" rows="2">${salary.payment_note || ''}</textarea>
								</div>
								<div class="col-md-12 mb-3">
									<div class="alert alert-info mb-0" style="background-color: #e7f3ff; border-left: 4px solid #0d6efd;">
										<strong>{{ $getCurrentTranslation['total_salary'] ?? 'Total Salary' }} (${currency}):</strong>
										<span id="totalSalaryDisplay" class="fw-bold fs-4 text-primary ms-2">${(parseFloat(salary.base_salary || 0) - parseFloat(salary.deductions || 0) + parseFloat(salary.bonus || 0)).toFixed(2)}</span>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $getCurrentTranslation['close'] ?? 'Close' }}</button>
								<button type="submit" class="btn btn-primary">{{ $getCurrentTranslation['update'] ?? 'Update' }}</button>
							</div>
						</form>
					`;
					$('#editSalaryContent').html(formHtml);
					
					// Calculate total salary function
					function calculateTotalSalary() {
						const baseSalary = parseFloat($('#editSalaryContent').find('input[name="base_salary"]').val()) || 0;
						const deductions = parseFloat($('#editSalaryContent').find('input[name="deductions"]').val()) || 0;
						const bonus = parseFloat($('#editSalaryContent').find('input[name="bonus"]').val()) || 0;
						const totalSalary = baseSalary - deductions + bonus;
						$('#totalSalaryDisplay').text(totalSalary.toFixed(2));
						// Update net salary display in both places
						$('#netSalaryDisplay').text(totalSalary.toFixed(2));
						// Also update in the small tag under paid amount input if it exists
						const paidAmountSmall = $('#editSalaryContent').find('#paidAmountInput').next('small');
						if (paidAmountSmall.length) {
							const currency = '{{Auth::user()->company_data->currency->short_name ?? ''}}';
							paidAmountSmall.html(`{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}: <span id="netSalaryDisplay">${totalSalary.toFixed(2)}</span> ${currency}`);
						}
					}
					
					// Initialize Select2 for select dropdowns
					setTimeout(function() {
						$('#editSalaryContent').find('select[data-control="select2"]').each(function() {
							var $select = $(this);
							if (!$select.hasClass('select2-hidden-accessible')) {
								var placeholder = $select.data('placeholder') || "Select an option";
								$select.select2({
									placeholder: placeholder,
									width: '100%'
								});
							}
						});
					}, 50);
					
					// Initialize flatpickr for date input
					setTimeout(function() {
						$('#editSalaryContent').find('.flatpickr-input.date').flatpickr({
							dateFormat: 'Y-m-d',
							altInput: true,
							altFormat: 'Y-m-d',
							allowInput: false
						});
					}, 100);
					
					// Function to toggle payment fields visibility
					function togglePaymentFields() {
						const paymentStatus = $('#editSalaryContent').find('select[name="payment_status"]').val();
						const paymentDateInput = $('#editSalaryContent').find('#paymentDateInput');
						const paymentDateRequired = $('#editSalaryContent').find('#paymentDateRequired');
						const paymentMethodContainer = $('#editSalaryContent').find('#paymentMethodContainer');
						const paymentMethodSelect = $('#editSalaryContent').find('#paymentMethodSelect');
						const paidAmountContainer = $('#editSalaryContent').find('#paidAmountContainer');
						const paidAmountInput = $('#editSalaryContent').find('#paidAmountInput');
						const netSalary = calculateNetSalaryFromForm();
						
						if (paymentStatus && paymentStatus !== 'Unpaid') {
							// Show payment date and payment method
							paymentDateInput.prop('required', true);
							paymentDateRequired.removeClass('d-none');
							paymentMethodContainer.show();
							paymentMethodSelect.prop('required', true);
							
							// Initialize Select2 for payment method if not already initialized
							if (paymentMethodSelect.length && !paymentMethodSelect.hasClass('select2-hidden-accessible')) {
								var placeholder = paymentMethodSelect.data('placeholder') || "Select an option";
								paymentMethodSelect.select2({
									placeholder: placeholder,
									width: '100%'
								});
							}
							
							if (!paymentDateInput.val()) {
								paymentDateInput.addClass('is-invalid');
							} else {
								paymentDateInput.removeClass('is-invalid');
							}
							
							// Show paid amount only for Partial
							if (paymentStatus === 'Partial') {
								paidAmountContainer.show();
								paidAmountInput.prop('required', true);
								// Set max value to net salary
								paidAmountInput.attr('max', netSalary);
							} else {
								paidAmountContainer.hide();
								paidAmountInput.prop('required', false);
								// For Paid, set paid_amount to net_salary
								if (paymentStatus === 'Paid') {
									paidAmountInput.val(netSalary.toFixed(2));
								}
							}
						} else {
							// Hide all payment fields for Unpaid
							paymentDateInput.prop('required', false);
							paymentDateRequired.addClass('d-none');
							paymentDateInput.removeClass('is-invalid');
							paymentMethodContainer.hide();
							paymentMethodSelect.prop('required', false);
							paidAmountContainer.hide();
							paidAmountInput.prop('required', false);
							paidAmountInput.val(0);
						}
					}
					
					// Function to calculate net salary from form inputs
					function calculateNetSalaryFromForm() {
						const baseSalary = parseFloat($('#editSalaryContent').find('input[name="base_salary"]').val()) || 0;
						const deductions = parseFloat($('#editSalaryContent').find('input[name="deductions"]').val()) || 0;
						const bonus = parseFloat($('#editSalaryContent').find('input[name="bonus"]').val()) || 0;
						return baseSalary - deductions + bonus;
					}
					
					// Function to validate paid amount
					function validatePaidAmount() {
						const paidAmountInput = $('#editSalaryContent').find('#paidAmountInput');
						if (paidAmountInput.length && paidAmountInput.is(':visible')) {
							const paidAmount = parseFloat(paidAmountInput.val()) || 0;
							const netSalary = calculateNetSalaryFromForm();
							const currency = '{{Auth::user()->company_data->currency->short_name ?? ''}}';
							
							// Update the net salary display
							$('#netSalaryDisplay').text(netSalary.toFixed(2));
							
							// Show error only if netSalary > 0 and paidAmount > netSalary
							if (netSalary > 0 && paidAmount > netSalary) {
								paidAmountInput.addClass('is-invalid');
								paidAmountInput.next('small').html(`<span class="text-danger">Paid amount cannot exceed net salary (${netSalary.toFixed(2)} ${currency})</span>`);
							} else if (paidAmount > 0 && netSalary <= 0) {
								// If net salary is 0 or negative, show warning
								paidAmountInput.addClass('is-invalid');
								paidAmountInput.next('small').html(`<span class="text-danger">Net salary must be greater than 0</span>`);
							} else {
								paidAmountInput.removeClass('is-invalid');
								paidAmountInput.next('small').html(`{{ $getCurrentTranslation['net_salary'] ?? 'Net Salary' }}: <span id="netSalaryDisplay">${netSalary.toFixed(2)}</span> ${currency}`);
							}
						}
					}
					
					// Update net salary display when base_salary, deductions, or bonus changes
					$('#editSalaryContent').on('input', 'input[name="base_salary"], input[name="deductions"], input[name="bonus"]', function() {
						calculateTotalSalary();
						const netSalary = calculateNetSalaryFromForm();
						$('#netSalaryDisplay').text(netSalary.toFixed(2));
						// Update max for paid_amount if Partial
						if ($('#editSalaryContent').find('select[name="payment_status"]').val() === 'Partial') {
							$('#paidAmountInput').attr('max', netSalary);
						}
						// Validate paid amount when net salary changes
						validatePaidAmount();
					});
					
					// Validate paid amount doesn't exceed net salary
					$('#editSalaryContent').on('input', '#paidAmountInput', function() {
						validatePaidAmount();
					});
					
					// Calculate initial net salary immediately after form loads
					calculateTotalSalary();
					
					// Check on load
					setTimeout(function() {
						// Recalculate to ensure values are correct
						calculateTotalSalary();
						togglePaymentFields();
						// Validate paid amount on initial load
						validatePaidAmount();
					}, 100);
					
					// Bind change event to payment status
					$('#editSalaryContent').on('change', 'select[name="payment_status"]', function() {
						togglePaymentFields();
					});
					
					// Validate payment date on change
					$('#editSalaryContent').on('change', '#paymentDateInput', function() {
						const paymentDateInput = $(this);
						if (paymentDateInput.val()) {
							paymentDateInput.removeClass('is-invalid');
						} else {
							const paymentStatus = $('#editSalaryContent').find('select[name="payment_status"]').val();
							if (paymentStatus && paymentStatus !== 'Unpaid') {
								paymentDateInput.addClass('is-invalid');
							}
						}
					});
				}
			},
			error: function() {
				$('.r-preloader').hide();
				$('#editSalaryContent').html('<div class="alert alert-danger">Error loading salary details</div>');
			}
		});
	});

	// Delete salary
	$(document).on('click', '.delete-salary', function() {
		const salaryId = $(this).data('salary-id');
		const employeeName = $(this).data('employee-name');
		
		Swal.fire({
			icon: 'warning',
			title: '{{ $getCurrentTranslation["confirm_delete"] ?? "Confirm Delete" }}',
			text: '{{ $getCurrentTranslation["are_you_sure_you_want_to_delete_this_salary"] ?? "Are you sure you want to delete this salary record?" }}',
			showCancelButton: true,
			confirmButtonText: '{{ $getCurrentTranslation["yes_delete"] ?? "Yes, Delete" }}',
			cancelButtonText: '{{ $getCurrentTranslation["cancel"] ?? "Cancel" }}',
			confirmButtonColor: '#d33',
			cancelButtonColor: '#3085d6',
		}).then((result) => {
			if (result.isConfirmed) {
				$('.r-preloader').show();
				
				$.ajax({
					url: '{{ route("admin.salary.destroy", ":id") }}'.replace(':id', salaryId),
					method: 'DELETE',
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					success: function(response) {
						$('.r-preloader').hide();
						if (response.success) {
							Swal.fire({
								icon: 'success',
								title: '{{ $getCurrentTranslation["success"] ?? "Success" }}',
								text: response.message,
								showConfirmButton: false,
								timer: 1500
							}).then(() => {
								location.reload();
							});
						} else {
							Swal.fire({
								icon: 'error',
								title: '{{ $getCurrentTranslation["error"] ?? "Error" }}',
								text: response.message || '{{ $getCurrentTranslation["failed_to_delete_salary"] ?? "Failed to delete salary." }}',
							});
						}
					},
					error: function(xhr) {
						$('.r-preloader').hide();
						let errorMsg = '{{ $getCurrentTranslation["failed_to_delete_salary"] ?? "Failed to delete salary." }}';
						if (xhr.responseJSON && xhr.responseJSON.message) {
							errorMsg = xhr.responseJSON.message;
						}
						Swal.fire({
							icon: 'error',
							title: '{{ $getCurrentTranslation["error"] ?? "Error" }}',
							text: errorMsg,
						});
					}
				});
			}
		});
	});

	// Update salary form submission
	$(document).on('submit', '#updateSalaryForm', function(e) {
		e.preventDefault();
		
		if (!currentSalaryId) {
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'Salary ID not found.',
			});
			return;
		}
		
		// Validate payment date if payment status is not Unpaid
		const paymentStatus = $(this).find('select[name="payment_status"]').val();
		const paymentDate = $(this).find('input[name="payment_date"]').val();
		
		if (paymentStatus && paymentStatus !== 'Unpaid' && !paymentDate) {
			Swal.fire({
				icon: 'error',
				title: 'Validation Error',
				text: 'Payment date is required when payment status is not Unpaid.',
			});
			$(this).find('input[name="payment_date"]').addClass('is-invalid').focus();
			return;
		}
		
		const form = $(this);
		const formData = form.serialize();
		
		$('.r-preloader').show();
		
		$.ajax({
			url: '{{ route("admin.salary.update", ":id") }}'.replace(':id', currentSalaryId),
			method: 'POST',
			data: formData,
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			success: function(response) {
				$('.r-preloader').hide();
				if (response.success) {
					Swal.fire({
						icon: 'success',
						title: 'Success',
						text: response.message,
						showCloseButton: true
					}).then(() => {
						location.reload();
					});
				}
			},
			error: function(xhr) {
				$('.r-preloader').hide();
				let errorMsg = 'Failed to update salary.';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					errorMsg = xhr.responseJSON.message;
				}
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: errorMsg,
				});
			}
		});
	});
});
</script>
@endpush
