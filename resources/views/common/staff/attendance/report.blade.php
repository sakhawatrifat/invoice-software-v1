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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['attendance_report'] ?? 'attendance_report' }}</li>
				</ul>
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
									<div class="row align-items-center">
										<div class="col-md-6">
											<div class="input-item-wrap mb-0">
												{{-- <label class="form-label">{{ $getCurrentTranslation['date_range'] ?? 'date_range' }}:</label> --}}
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														if (empty(request()->date_range)) {
															$defaultStart = \Carbon\Carbon::now()->firstOfMonth()->format('Y/m/d');
															$defaultEnd = \Carbon\Carbon::today()->format('Y/m/d');
															$selectedDateRange = "$defaultStart-$defaultEnd";
														} else {
															$selectedDateRange = request()->date_range;
														}
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

										{{-- <div class="col-md-6">
											<div class="form-item mb-5">
												@php
													$options = ['Present', 'Late', 'Absent', 'Half-day', 'On Leave', 'Work From Home'];
													$selected = request()->status ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}:</label>
												<select class="form-select dynamic-option" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_status'] ?? 'select_status' }}" name="status">
													<option value="0">All</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
											</div>
										</div> --}}

										<div class="col-md-6">
											<div class="d-flex justify-content-start mt-0">
												@php
													$defaultStart = \Carbon\Carbon::now()->firstOfMonth()->format('Y/m/d');
													$defaultEnd = \Carbon\Carbon::today()->format('Y/m/d');
													$defaultDateRange = "$defaultStart-$defaultEnd";
												@endphp
												<a class="btn btn-secondary btn-sm me-3" href="{{ route('staff.attendance.report') }}?date_range={{ $defaultDateRange }}">
													{{ $getCurrentTranslation['reset'] ?? 'reset' }}
												</a>
												<button type="type" class="btn btn-primary btn-sm filter-data-btn">
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
						{{ $getCurrentTranslation['attendance_report'] ?? 'attendance_report' }}
					</h3>
				</div>
				<div class="card-body px-0">
					@php
						$dailyWorkTime = env('DAILY_WORK_TIME', 8);
					@endphp

					{{-- ================= SUMMARY CARDS ================= --}}
					<div class="row">
						<div class="col-md-4 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['total_present_days'] ?? 'Total Present Days' }}</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ $employeeSummary['total_present_days'] ?? 0 }}</h2>
								</div>
							</div>
						</div>

						<div class="col-md-4 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-success text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['total_work_hours'] ?? 'total_work_hours' }}</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ formatHoursMinutes($employeeSummary['total_work_hours'] ?? 0) }}</h2>
								</div>
							</div>
						</div>

						<div class="col-md-4 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-warning text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['total_overtime_hours'] ?? 'total_overtime_hours' }}</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ formatHoursMinutes($employeeSummary['total_overtime_hours'] ?? 0) }}</h2>
								</div>
							</div>
						</div>

						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-info text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['work_hours_gap_present'] ?? 'Work Hours Gap (Present Days)' }}</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ formatHoursMinutes($employeeSummary['work_hours_gap_present'] ?? 0) }}</h2>
								</div>
							</div>
						</div>

						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['work_hours_gap_total'] ?? 'Work Hours Gap (Present + Absent Days)' }}</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ formatHoursMinutes($employeeSummary['work_hours_gap_total'] ?? 0) }}</h2>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= DETAILED ATTENDANCE TABLE ================= --}}
					<div class="row">
						<div class="col-md-12">
							<div class="card shadow-sm mb-4">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['attendance_details'] ?? 'attendance_details' }}</h5>
								</div>
								<div class="card-body">
									<div class="table-responsive">
										<table class="report-table table table-bordered table-striped table-hover align-middle mb-0">
											<thead class="table-secondary">
												<tr>
													<th class="fw-semibold">#</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['date'] ?? 'date' }}</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['clock_status'] ?? 'Clock Status' }}</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['check_in'] ?? 'check_in' }}</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['check_out'] ?? 'check_out' }}</th>
													<th class="fw-semibold bg-info text-white">{{ $getCurrentTranslation['running_total_hours'] ?? 'Running Total Hours' }}</th>
													<th class="fw-semibold bg-success text-white">{{ $getCurrentTranslation['total_hours'] ?? 'Total Hours (Completed)' }}</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['status'] ?? 'status' }}</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['overtime'] ?? 'overtime' }}</th>
													<th class="fw-semibold">{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
												</tr>
											</thead>
											<tbody>
												@forelse($attendances as $attendance)
													@php
														$isOvertime = $attendance->total_hours > $dailyWorkTime;
														$overtimeHours = $isOvertime ? ($attendance->total_hours - $dailyWorkTime) : 0;
														$isRunning = empty($attendance->check_out) && !empty($attendance->check_in);
														$runningTotalHours = $attendance->running_total_hour ?? 0;
													@endphp
													<tr class="{{ $isRunning ? 'table-warning' : '' }}">
														<td>{{ $loop->iteration }}</td>
														<td>{{ $attendance->date ? $attendance->date->format('Y-m-d') : 'N/A' }}</td>
														<td>
															@if($isRunning)
																<span class="badge badge-success">
																	<i class="fas fa-clock"></i> {{ $getCurrentTranslation['running'] ?? 'Running' }}
																</span>
															@else
																<span class="badge badge-secondary">
																	{{ $getCurrentTranslation['completed'] ?? 'Completed' }}
																</span>
															@endif
														</td>
														<td>
															@if($attendance->check_in)
																<small>{{ $attendance->check_in->format('H:i:s') }}</small><br>
																<small class="text-muted">{{ $attendance->check_in->format('Y-m-d') }}</small>
															@else
																<span class="text-muted">-</span>
															@endif
														</td>
														<td>
															@if($attendance->check_out)
																<small>{{ $attendance->check_out->format('H:i:s') }}</small><br>
																<small class="text-muted">{{ $attendance->check_out->format('Y-m-d') }}</small>
															@else
																<span class="text-muted">-</span>
															@endif
														</td>
														<td class="bg-light fw-bold text-primary">
															@if($isRunning)
																<span class="text-success">
																	<i class="fas fa-hourglass-half"></i> {{ formatHoursMinutes($runningTotalHours) }}
																</span>
																<br>
																<small class="text-muted">(Live)</small>
															@else
																<span class="text-muted">-</span>
															@endif
														</td>
														<td class="fw-semibold">
															@if($attendance->check_out)
																{{ formatHoursMinutes($attendance->total_hours ?? 0) }}
															@else
																<span class="text-muted">-</span>
															@endif
														</td>
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
															@if($isOvertime)
																<span class="badge badge-warning">{{ formatHoursMinutes($overtimeHours) }}</span>
															@else
																<span class="text-muted">-</span>
															@endif
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
														<td colspan="10" class="p-10 text-center">
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
			</div>
		</div>
	</div>
</div>

@include('admin.report._partials.attendanceDetailsModal', ['hasAttendanceData' => isset($attendances) && $attendances->count() > 0])

@endsection


@push('script')
<script>
	$(document).ready(function() {
		// View attendance details
		$('.view-attendance-details').on('click', function() {
			var attendanceId = $(this).data('attendance-id');
			$('.r-preloader').show();
			
			$.ajax({
				url: '{{ route("admin.attendance.getDetails") }}',
				method: 'GET',
				data: { attendance_id: attendanceId },
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				success: function(response) {
					$('.r-preloader').hide();
					if (response.success) {
						$('#attendanceDetailsModalContent').html(response.html);
						$('#attendanceDetailsModal').modal('show');
					}
				},
				error: function() {
					$('.r-preloader').hide();
					alert('Failed to load attendance details.');
				}
			});
		});
	});
</script>
@endpush