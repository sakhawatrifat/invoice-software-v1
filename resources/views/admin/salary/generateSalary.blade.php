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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['generate_salary'] ?? 'Generate Salary' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				<a href="{{ route('admin.salary.list') }}" class="btn btn-sm fw-bold btn-primary">
					<i class="fa-solid fa-arrow-left"></i>
					{{ $getCurrentTranslation['salary_list'] ?? 'Salary List' }}
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
						{{ $getCurrentTranslation['generate_salary'] ?? 'Generate Salary' }}
					</h3>
				</div>
				<div class="card-body px-0">
					<form id="salaryGenerateForm" method="POST" action="{{ route('admin.salary.generate') }}">
						@csrf
						<div class="row">
							<div class="col-md-6 mb-5">
								<div class="input-item">
									<label class="form-label">{{ $getCurrentTranslation['month'] ?? 'Month' }}: <span class="text-danger">*</span></label>
									<select class="form-select" name="month" required data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
										<option value="">-- {{ $getCurrentTranslation['select_month'] ?? 'Select Month' }} --</option>
										@for($i = 1; $i <= 12; $i++)
											<option value="{{ $i }}" {{ $i == Carbon\Carbon::now()->month ? 'selected' : '' }}>
												{{ Carbon\Carbon::create()->month($i)->format('F') }}
											</option>
										@endfor
									</select>
									@error('month')
										<span class="text-danger text-sm">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6 mb-5">
								<div class="input-item">
									<label class="form-label">{{ $getCurrentTranslation['year'] ?? 'Year' }}: <span class="text-danger">*</span></label>
									<select class="form-select" name="year" required data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
										<option value="">-- {{ $getCurrentTranslation['select_year'] ?? 'Select Year' }} --</option>
										@for($i = Carbon\Carbon::now()->year; $i >= Carbon\Carbon::now()->year - 5; $i--)
											<option value="{{ $i }}" {{ $i == Carbon\Carbon::now()->year ? 'selected' : '' }}>
												{{ $i }}
											</option>
										@endfor
									</select>
									@error('year')
										<span class="text-danger text-sm">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-12 mb-5">
								<div class="input-item">
									<label class="form-label">{{ $getCurrentTranslation['select_employees'] ?? 'Select Employees' }}: <span class="text-danger">*</span></label>
									<div class="card border p-3" style="max-height: 400px; overflow-y: auto;">
										<div class="mb-3">
											<button type="button" class="btn btn-sm btn-primary" id="selectAllEmployees">
												{{ $getCurrentTranslation['select_all'] ?? 'Select All' }}
											</button>
											<button type="button" class="btn btn-sm btn-secondary" id="deselectAllEmployees">
												{{ $getCurrentTranslation['deselect_all'] ?? 'Deselect All' }}
											</button>
											<button type="button" class="btn btn-sm btn-warning" id="checkDuplicates">
												<i class="fas fa-search"></i> {{ $getCurrentTranslation['check_duplicates'] ?? 'Check Duplicates' }}
											</button>
										</div>
										<div id="duplicateWarning" class="alert alert-warning d-none mb-3">
											<strong><i class="fas fa-exclamation-triangle"></i> {{ $getCurrentTranslation['duplicate_warning'] ?? 'Warning' }}:</strong>
											<span id="duplicateEmployeesList"></span>
										</div>
										<div class="row">
											@foreach($employees as $employee)
												<div class="col-md-4 mb-2">
													<div class="form-check">
														<input class="form-check-input employee-checkbox" type="checkbox" 
															name="employee_ids[]" 
															value="{{ $employee->id }}" 
															id="employee_{{ $employee->id }}"
															data-employee-name="{{ $employee->name }}"
															{{ ($employee->is_staff == 1 || $employee->id == 1) ? 'checked' : '' }}>
														<label class="form-check-label" for="employee_{{ $employee->id }}">
															<strong>{{ $employee->name }}</strong>
															<br><small class="text-muted">({{ $employee->designation?->name ?? 'N/A' }})</small>
															@if($employee->is_staff == 0)
																<span class="badge badge-warning ms-2">{{ $getCurrentTranslation['non_staff'] ?? 'Non Staff' }}</span>
															@endif
															<span class="duplicate-badge badge badge-danger d-none ms-2" id="badge_{{ $employee->id }}">
																{{ $getCurrentTranslation['duplicate'] ?? 'Duplicate' }}
															</span>
														</label>
													</div>
												</div>
											@endforeach
										</div>
									</div>
									@error('employee_ids')
										<span class="text-danger text-sm">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-12">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-file-invoice-dollar"></i> {{ $getCurrentTranslation['generate_salary'] ?? 'Generate Salary' }}
								</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
<script>
$(document).ready(function() {
	// Select all employees
	$('#selectAllEmployees').on('click', function() {
		$('.employee-checkbox').prop('checked', true);
	});

	// Deselect all employees
	$('#deselectAllEmployees').on('click', function() {
		$('.employee-checkbox').prop('checked', false);
		$('#duplicateWarning').addClass('d-none');
		$('.duplicate-badge').addClass('d-none');
		$('.form-check').removeClass('border border-danger rounded p-2');
	});

	// Check for duplicates
	$('#checkDuplicates').on('click', function() {
		const month = $('select[name="month"]').val();
		const year = $('select[name="year"]').val();
		const checkedEmployees = $('.employee-checkbox:checked');
		
		if (!month || !year) {
			Swal.fire({
				icon: 'warning',
				title: 'Warning',
				text: 'Please select month and year first.',
			});
			return;
		}
		
		if (checkedEmployees.length === 0) {
			Swal.fire({
				icon: 'warning',
				title: 'Warning',
				text: 'Please select at least one employee.',
			});
			return;
		}
		
		const employeeIds = checkedEmployees.map(function() {
			return $(this).val();
		}).get();
		
		$('.r-preloader').show();
		
		$.ajax({
			url: '{{ route("admin.salary.checkDuplicates") }}',
			method: 'POST',
			data: {
				month: month,
				year: year,
				employee_ids: employeeIds
			},
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			success: function(response) {
				$('.r-preloader').hide();
				if (response.duplicates && response.duplicates.length > 0) {
					// Show duplicate warning
					$('#duplicateWarning').removeClass('d-none');
					const duplicateNames = response.duplicates.map(function(dup) {
						return dup.employee_name;
					}).join(', ');
					$('#duplicateEmployeesList').text(duplicateNames);
					
					// Mark duplicate checkboxes
					$('.duplicate-badge').addClass('d-none');
					$('.form-check').removeClass('border border-danger rounded p-2');
					response.duplicates.forEach(function(dup) {
						$('#badge_' + dup.employee_id).removeClass('d-none');
						$('#employee_' + dup.employee_id).closest('.form-check').addClass('border border-danger rounded p-2');
					});
					
					Swal.fire({
						icon: 'warning',
						title: 'Duplicates Found',
						text: response.duplicates.length + ' employee(s) already have salaries for the selected month/year.',
					});
				} else {
					$('#duplicateWarning').addClass('d-none');
					$('.duplicate-badge').addClass('d-none');
					$('.form-check').removeClass('border border-danger rounded p-2');
					Swal.fire({
						icon: 'success',
						title: 'No Duplicates',
						text: 'All selected employees are ready for salary generation.',
					});
				}
			},
			error: function() {
				$('.r-preloader').hide();
				Swal.fire({
					icon: 'error',
					title: 'Error',
					text: 'Failed to check for duplicates.',
				});
			}
		});
	});

	// Form submission
	$('#salaryGenerateForm').on('submit', function(e) {
		// Check if at least one employee is selected
		if ($('.employee-checkbox:checked').length === 0) {
			e.preventDefault();
			Swal.fire({
				icon: 'error',
				title: 'Error',
				text: 'Please select at least one employee.',
			});
			return false;
		}

		$('.r-preloader').show();
		// Form will submit normally and controller will redirect
	});
});
</script>
@endpush
