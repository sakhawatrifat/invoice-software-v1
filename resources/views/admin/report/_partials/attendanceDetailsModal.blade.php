@php
	$getCurrentTranslation = getCurrentTranslation();
	$timeline = $timeline ?? [];
	$pauses = $pauses ?? collect();
	$hasAttendanceData = $hasAttendanceData ?? null;
@endphp

@if(!isset($attendance))
	<div class="row m-0">
		<div class="col-md-12 mb-4 px-10">
			<div class="card">
				<div class="alert alert-info mb-0 text-center">
					@if($hasAttendanceData === false)
						{{ $getCurrentTranslation['no_attendance_data_found'] ?? 'No attendance data found.' }}
					@else
						{{ $getCurrentTranslation['please_select_an_attendance_record_to_view_details'] ?? 'Please select an attendance record to view details.' }}
					@endif
				</div>
			</div>
		</div>
	</div>
@else
<div class="row">
	{{-- Basic Information --}}
	<div class="col-md-12 mb-4">
		<div class="card">
			<div class="card-header bg-primary text-white align-items-center">
				<h6 class="mb-0 text-white">{{ $getCurrentTranslation['basic_information'] ?? 'Basic Information' }}</h6>
			</div>
			<div class="card-body">
				<div class="row">
					<div class="col-md-2 col-6 mb-2">
						<strong>{{ $getCurrentTranslation['employee'] ?? 'employee' }}:</strong><br>
						{{ $attendance->employee->name ?? 'N/A' }}
					</div>
					<div class="col-md-2 col-6 mb-2">
						<strong>{{ $getCurrentTranslation['designation'] ?? 'designation' }}:</strong><br>
						{{ $attendance->employee->designation?->name ?? 'N/A' }}
					</div>
					<div class="col-md-2 col-6 mb-2">
						<strong>{{ $getCurrentTranslation['department'] ?? 'department' }}:</strong><br>
						{{ $attendance->employee->department?->name ?? 'N/A' }}
					</div>
					<div class="col-md-2 col-6 mb-2">
						<strong>{{ $getCurrentTranslation['date'] ?? 'date' }}:</strong><br>
						{{ $attendance->date ? $attendance->date->format('Y-m-d') : 'N/A' }}
					</div>
					<div class="col-md-2 col-6 mb-2">
						<strong>{{ $getCurrentTranslation['status'] ?? 'status' }}:</strong><br>
						<span class="badge 
							@if($attendance->status == 'Present') badge-success
							@elseif($attendance->status == 'Late') badge-warning
							@elseif($attendance->status == 'Absent') badge-danger
							@else badge-info
							@endif">
							{{ $attendance->status }}
						</span>
					</div>
				</div>
				<div class="row mt-3">
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['check_in'] ?? 'check_in' }}:</strong><br>
						{{ $attendance->check_in ? $attendance->check_in->format('Y-m-d H:i:s') : 'N/A' }}
					</div>
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['check_out'] ?? 'check_out' }}:</strong><br>
						{{ $attendance->check_out ? $attendance->check_out->format('Y-m-d H:i:s') : 'N/A' }}
					</div>
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['total_hours'] ?? 'total_hours' }}:</strong><br>
						{{ formatHoursMinutes($attendance->total_hours ?? 0) }}
					</div>
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['ip_address'] ?? 'ip_address' }}:</strong><br>
						{{ $attendance->ip_address ?? 'N/A' }}
					</div>
				</div>
				<div class="row mt-3">
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['device_browser'] ?? 'device_browser' }}:</strong><br>
						{{ $attendance->device_browser ?? 'N/A' }}
					</div>
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['location'] ?? 'Location' }}:</strong><br>
						@if($attendance->user_location)
							{{ $attendance->user_location }}
							@if(!empty($attendance->location) && isset($attendance->location['lat'], $attendance->location['lng']))
								<br><a href="https://www.google.com/maps?q={{ $attendance->location['lat'] }},{{ $attendance->location['lng'] }}" target="_blank" rel="noopener" class="small">{{ $getCurrentTranslation['view_on_map'] ?? 'View on map' }}</a>
							@endif
						@else
							<span class="text-muted">{{ $getCurrentTranslation['no_location_recorded'] ?? '—' }}</span>
						@endif
					</div>
				</div>
				<div class="row mt-3">
					<div class="col-md-3">
						<strong>{{ $getCurrentTranslation['forgot_clock_out'] ?? 'Forgot Clock Out' }}:</strong><br>
						@if($attendance->forgot_clock_out)
							<span class="badge badge-danger">{{ $getCurrentTranslation['yes'] ?? 'Yes' }}</span>
						@else
							<span class="badge badge-success">{{ $getCurrentTranslation['no'] ?? 'No' }}</span>
						@endif
					</div>
				</div>
				@if($attendance->overtime_task_description)
				<div class="row mt-3">
					<div class="col-md-12">
						<strong>{{ $getCurrentTranslation['overtime_task_description'] ?? 'overtime_task_description' }}:</strong><br>
						<div class="p-3 bg-light rounded">
							{{ $attendance->overtime_task_description }}
						</div>
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>

	{{-- Attendance Timeline --}}
	@if(!empty($timeline) && count($timeline) > 0)
	<div class="col-md-12 mb-4">
		<div class="card">
			<div class="card-header bg-info align-items-center">
				<h6 class="mb-0 text-white">{{ $getCurrentTranslation['attendance_timeline'] ?? 'Attendance Timeline' }}</h6>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered table-sm">
						<thead class="table-secondary">
							<tr>
								<th>#</th>
								<th>{{ $getCurrentTranslation['check_in'] ?? 'check_in' }}</th>
								<th>{{ $getCurrentTranslation['check_out'] ?? 'check_out' }}</th>
								<th>{{ $getCurrentTranslation['total_time'] ?? 'Total Time' }}</th>
								<th>{{ $getCurrentTranslation['total_pause_minutes'] ?? 'Total Pause Minutes' }}</th>
								<th>{{ $getCurrentTranslation['total_pause_hours'] ?? 'Total Pause Hours' }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($timeline as $index => $session)
								<tr>
									<td>{{ $index + 1 }}</td>
									<td>{{ $session['clock_in'] ?? 'N/A' }}</td>
									<td>{{ $session['clock_out'] ?? ($attendance->check_out ? 'N/A (Running)' : '-') }}</td>
									<td>{{ isset($session['total_time']) ? formatHoursMinutes($session['total_time']) : '-' }}</td>
									<td>{{ $session['total_pause_minutes'] ?? 0 }}m</td>
									<td>{{ isset($session['total_pause_hours']) ? formatHoursMinutes($session['total_pause_hours']) : formatHoursMinutes(($session['total_pause_minutes'] ?? 0) / 60) }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	@endif

	{{-- Pause Details --}}
	@if($pauses->count() > 0)
	<div class="col-md-12 mb-4">
		<div class="card">
			<div class="card-header bg-warning align-items-center">
				<h6 class="mb-0 text-white">{{ $getCurrentTranslation['pause_details'] ?? 'Pause Details' }}</h6>
			</div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table table-bordered table-sm">
						<thead class="table-secondary">
							<tr>
								<th>#</th>
								<th>{{ $getCurrentTranslation['pause_start'] ?? 'Pause Start' }}</th>
								<th>{{ $getCurrentTranslation['pause_end'] ?? 'Pause End' }}</th>
								<th>{{ $getCurrentTranslation['pause_duration'] ?? 'Pause Duration' }}</th>
								<th>{{ $getCurrentTranslation['status'] ?? 'status' }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($pauses as $pause)
								<tr>
									<td>{{ $loop->iteration }}</td>
									<td>{{ $pause->pause_start ? $pause->pause_start->format('Y-m-d H:i:s') : 'N/A' }}</td>
									<td>
										@if($pause->pause_end)
											{{ $pause->pause_end->format('Y-m-d H:i:s') }}
										@else
											<span class="badge badge-warning">{{ $getCurrentTranslation['active'] ?? 'Active' }}</span>
										@endif
									</td>
									<td>
										@if($pause->pause_duration_minutes)
											{{ formatHoursMinutes($pause->pause_duration_minutes / 60) }}
										@elseif($pause->pause_end)
											{{ formatHoursMinutes($pause->pause_start->diffInMinutes($pause->pause_end) / 60) }}
										@else
											<span class="text-muted">-</span>
										@endif
									</td>
									<td>
										@if($pause->pause_end)
											<span class="badge badge-success">{{ $getCurrentTranslation['completed'] ?? 'Completed' }}</span>
										@else
											<span class="badge badge-warning">{{ $getCurrentTranslation['running'] ?? 'Running' }}</span>
										@endif
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
	@endif
</div>
@endif