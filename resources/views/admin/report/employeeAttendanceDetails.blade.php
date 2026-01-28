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
					<li class="breadcrumb-item text-muted">
						<a href="{{ route('admin.attendance.report') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['attendance_report'] ?? 'attendance_report' }}</a> &nbsp; - 
					</li>
					<li class="breadcrumb-item">{{ $employee->name ?? 'Employee Details' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@php
					$exportUrl = route('admin.attendance.employeeDetailsExportPdf', ['employeeId' => $employee->id]) . '?' . http_build_query(request()->all());
				@endphp
				<a href="{{ $exportUrl }}" class="btn btn-sm fw-bold btn-danger" target="_blank">
					<i class="fas fa-file-pdf"></i> {{ $getCurrentTranslation['export_pdf'] ?? 'Export PDF' }}
				</a>
				<a href="{{ route('admin.attendance.report') }}" class="btn btn-sm fw-bold btn-secondary">{{ $getCurrentTranslation['back_to_list'] ?? 'back_to_list' }}</a>
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						{{ $getCurrentTranslation['employee_attendance_details'] ?? 'Employee Attendance Details' }} - {{ $employee->name ?? 'N/A' }}
					</h3>
					<p class="text-muted mb-0">
						<strong>{{ $getCurrentTranslation['designation'] ?? 'designation' }}:</strong> {{ $employee->designation?->name ?? 'N/A' }} |
						<strong>{{ $getCurrentTranslation['department'] ?? 'department' }}:</strong> {{ $employee->department?->name ?? 'N/A' }} | 
						<strong>{{ $getCurrentTranslation['email'] ?? 'email' }}:</strong> {{ $employee->email ?? 'N/A' }}
					</p>
				</div>
				<div class="card-body px-0">
					{{-- Date Range Filter --}}
					<div class="row mb-5">
						<div class="col-md-12 mb-5">
							<form method="GET" action="{{ route('admin.attendance.employeeDetails', $employeeId) }}">
								<div class="row align-items-center">
									<div class="col-md-6">
										<div class="input-item-wrap">
											{{-- <label class="form-label">{{ $getCurrentTranslation['date_range'] ?? 'date_range' }}:</label> --}}
											<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
												@php
													$defaultStart = \Carbon\Carbon::now()->firstOfMonth()->format('Y/m/d');
													$defaultEnd = \Carbon\Carbon::today()->format('Y/m/d');
													$selectedDateRange = request()->date_range ?? "$defaultStart-$defaultEnd";
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
									<div class="col-md-6 d-flex align-items-end">
										<button type="submit" class="btn btn-primary btn-sm me-3">
											{{ $getCurrentTranslation['filter'] ?? 'filter' }}
										</button>
										<a href="{{ route('admin.attendance.employeeDetails', $employeeId) }}" class="btn btn-secondary btn-sm">
											{{ $getCurrentTranslation['reset'] ?? 'reset' }}
										</a>
									</div>
								</div>
							</form>
						</div>
					</div>

					{{-- Employee Summary --}}
					@if(isset($employeeSummary))
					<div class="row mb-5">
						<div class="col-md-12">
							<div class="card shadow-sm mb-4">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['employee_attendance_summary'] ?? 'Employee Attendance Summary' }}
									</h5>
								</div>
								<div class="card-body">
									<div class="row">
										<div class="col-md-3 mb-3">
											<div class="card bg-light h-100">
												<div class="card-body text-center">
													<h6 class="text-muted mb-2">{{ $getCurrentTranslation['total_present_days'] ?? 'Total Present Days' }}</h6>
													<h3 class="mb-0 text-primary">{{ $employeeSummary['total_present_days'] }}</h3>
												</div>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="card bg-light h-100">
												<div class="card-body text-center">
													<h6 class="text-muted mb-2">{{ $getCurrentTranslation['total_absent_days'] ?? 'Total Absent Days' }}</h6>
													<h3 class="mb-0 text-danger">{{ $employeeSummary['total_absent_days'] }}</h3>
												</div>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="card bg-light h-100">
												<div class="card-body text-center">
													<h6 class="text-muted mb-2">{{ $getCurrentTranslation['total_work_hours'] ?? 'Total Work Hours' }}</h6>
													<h3 class="mb-0 text-success">{{ formatHoursMinutes($employeeSummary['total_work_hours']) }}</h3>
												</div>
											</div>
										</div>
										<div class="col-md-3 mb-3">
											<div class="card bg-light h-100">
												<div class="card-body text-center">
													<h6 class="text-muted mb-2">{{ $getCurrentTranslation['total_overtime_hours'] ?? 'total_overtime_hours' }}</h6>
													<h3 class="mb-0 text-warning">{{ formatHoursMinutes($employeeSummary['total_overtime_hours']) }}</h3>
												</div>
											</div>
										</div>
									</div>
									<div class="row mt-3">
										<div class="col-md-6">
											<div class="card bg-light">
												<div class="card-body text-center">
													<h6 class="text-muted mb-2">{{ $getCurrentTranslation['work_hours_gap_present'] ?? 'Work Hours Gap (Present Days)' }}</h6>
													@php
														$gapPresent = $employeeSummary['work_hours_gap_present'] ?? 0;
														$gapPresentClass = $gapPresent > 0 ? 'text-warning' : 'text-success';
													@endphp
													<h3 class="mb-0 {{ $gapPresentClass }}">
														{{ formatHoursMinutes($gapPresent) }}
													</h3>
													<small class="text-muted">
														@if($gapPresent > 0)
															{{ $getCurrentTranslation['deficit'] ?? 'Deficit' }}
														@else
															{{ $getCurrentTranslation['on_target'] ?? 'On Target' }}
														@endif
													</small>
												</div>
											</div>
										</div>
										<div class="col-md-6">
											<div class="card bg-light">
												<div class="card-body text-center">
													<h6 class="text-muted mb-2">{{ $getCurrentTranslation['work_hours_gap_total'] ?? 'Work Hours Gap (Present + Absent Days)' }}</h6>
													@php
														$gapTotal = $employeeSummary['work_hours_gap_total'] ?? 0;
														$gapTotalClass = $gapTotal > 0 ? 'text-warning' : 'text-success';
													@endphp
													<h3 class="mb-0 {{ $gapTotalClass }}">
														{{ formatHoursMinutes($gapTotal) }}
													</h3>
													<small class="text-muted">
														@if($gapTotal > 0)
															{{ $getCurrentTranslation['deficit'] ?? 'Deficit' }}
														@else
															{{ $getCurrentTranslation['on_target'] ?? 'On Target' }}
														@endif
													</small>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					@endif

					{{-- Attendance List --}}
					<div class="table-responsive">
						<table class="table table-bordered table-striped table-hover align-middle mb-0">
							<thead class="table-secondary">
								<tr>
									<th class="fw-semibold ps-3">#</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['date'] ?? 'date' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['check_in'] ?? 'check_in' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['check_out'] ?? 'check_out' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['total_hours'] ?? 'total_hours' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['status'] ?? 'status' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($attendances as $attendance)
									<tr>
										<td class="ps-3">{{ $loop->iteration }}</td>
										<td>{{ $attendance->date ? $attendance->date->format('Y-m-d') : 'N/A' }}</td>
										<td>
											@if($attendance->check_in)
												{{ $attendance->check_in->format('H:i:s') }}<br>
												<small class="text-muted">{{ $attendance->check_in->format('Y-m-d') }}</small>
											@else
												<span class="text-muted">-</span>
											@endif
										</td>
										<td>
											@if($attendance->check_out)
												{{ $attendance->check_out->format('H:i:s') }}<br>
												<small class="text-muted">{{ $attendance->check_out->format('Y-m-d') }}</small>
											@else
												<span class="text-muted">-</span>
											@endif
										</td>
										<td>{{ formatHoursMinutes($attendance->total_hours ?? 0) }}</td>
										<td>
											<span class="badge 
												@if($attendance->status == 'Present') badge-success
												@elseif($attendance->status == 'Late') badge-warning
												@elseif($attendance->status == 'Absent') badge-danger
												@else badge-info
												@endif">
												{{ $attendance->status }}
											</span>
										</td>
										<td>
											<button type="button" class="btn btn-sm btn-info view-attendance-details" 
												data-attendance-id="{{ $attendance->id }}"
												data-date="{{ $attendance->date ? $attendance->date->format('Y-m-d') : 'N/A' }}">
												<i class="fas fa-list"></i> {{ $getCurrentTranslation['activity'] ?? 'activity' }}
											</button>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="7" class="p-10 text-center">
											{{ $getCurrentTranslation['no_data_found_for_selected_filter'] ?? 'no_data_found_for_selected_filter' }}
										</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{{-- Attendance Details Modal --}}
<div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">{{ $getCurrentTranslation['attendance_details'] ?? 'Attendance Details' }}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="attendanceDetailsContent">
				@include('admin.report._partials.attendanceDetailsModal', ['hasAttendanceData' => isset($attendances) && $attendances->count() > 0])
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
	$('.view-attendance-details').on('click', function() {
		const attendanceId = $(this).data('attendance-id');
		const date = $(this).data('date');
		
		$('#attendanceDetailsModal').modal('show');
		$('#attendanceDetailsContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
		
		$.ajax({
			url: '{{ route("admin.attendance.getDetails") }}',
			method: 'GET',
			data: {
				attendance_id: attendanceId
			},
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			success: function(response) {
				if (response.success) {
					$('#attendanceDetailsContent').html(response.html);
				} else {
					$('#attendanceDetailsContent').html('<div class="alert alert-danger">' + (response.message || 'Error loading details') + '</div>');
				}
			},
			error: function() {
				$('#attendanceDetailsContent').html('<div class="alert alert-danger">Error loading attendance details</div>');
			}
		});
	});
});
</script>
@endpush
