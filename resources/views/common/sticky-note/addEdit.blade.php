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
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</a> &nbsp; -
					</li>
					@if(isset($listRoute) && !empty($listRoute))
						<li class="breadcrumb-item text-muted">
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['sticky_note_list'] ?? 'sticky_note_list' }}</a> &nbsp; -
						</li>
					@endif
					<li class="breadcrumb-item">
						@if(isset($editData) && !empty($editData))
							{{ $getCurrentTranslation['edit_sticky_note'] ?? 'edit_sticky_note' }}
						@else
							{{ $getCurrentTranslation['create_sticky_note'] ?? 'create_sticky_note' }}
						@endif
					</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($listRoute) && !empty($listRoute))
					<a href="{{ $listRoute }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-arrow-left"></i>
						{{ $getCurrentTranslation['back_to_list'] ?? 'back_to_list' }}
					</a>
				@endif
			</div>
		</div>
	</div>

	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<form method="post" action="{{ $saveRoute }}" class="ajax-form-submit">
				@csrf
				@if(isset($editData) && !empty($editData))
					@method('put')
				@endif

				<div class="col-md-10 m-auto">
					<div class="card rounded border mt-5 bg-white">
						<div class="card-header">
							<h3 class="card-title">
								@if(isset($editData) && !empty($editData))
									{{ $getCurrentTranslation['edit_sticky_note'] ?? 'edit_sticky_note' }}
								@else
									{{ $getCurrentTranslation['create_sticky_note'] ?? 'create_sticky_note' }}
								@endif
							</h3>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['note_title'] ?? 'note_title' }} <span class="text-danger">*</span></label>
										<input type="text" class="form-control" name="note_title" ip-required
											placeholder="{{ $getCurrentTranslation['note_title_placeholder'] ?? 'note_title_placeholder' }}"
											value="{{ old('note_title', isset($editData) ? ($editData->note_title ?? '') : '') }}">
									</div>
								</div>
								<div class="col-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['note_description'] ?? 'note_description' }}</label>
										<textarea class="form-control" name="note_description" rows="4"
											placeholder="{{ $getCurrentTranslation['note_description_placeholder'] ?? 'note_description_placeholder' }}">{{ old('note_description', isset($editData) ? ($editData->note_description ?? '') : '') }}</textarea>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['reminder_datetime'] ?? 'reminder_datetime' }} <span class="text-danger">*</span></label>
										<input type="text" class="form-control datetimepicker" name="reminder_datetime" readonly required
											placeholder="{{ $getCurrentTranslation['reminder_datetime_placeholder'] ?? 'reminder_datetime_placeholder' }}"
											value="{{ old('reminder_datetime', isset($editData) && $editData->reminder_datetime ? $editData->reminder_datetime->format('Y-m-d H:i') : '') }}">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['deadline'] ?? 'deadline' }} <span class="text-danger">*</span></label>
										<input type="text" class="form-control datetimepicker" name="deadline" readonly required
											placeholder="{{ $getCurrentTranslation['deadline_placeholder'] ?? 'deadline_placeholder' }}"
											value="{{ old('deadline', isset($editData) && $editData->deadline ? $editData->deadline->format('Y-m-d H:i') : '') }}">
									</div>
								</div>
								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}</label>
										<select class="form-select" name="status">
											<option value="Pending" {{ old('status', isset($editData) ? ($editData->status ?? '') : '') == 'Pending' ? 'selected' : '' }}>{{ $getCurrentTranslation['pending'] ?? 'pending' }}</option>
											<option value="In Progress" {{ old('status', isset($editData) ? ($editData->status ?? '') : '') == 'In Progress' ? 'selected' : '' }}>{{ $getCurrentTranslation['in_progress'] ?? 'in_progress' }}</option>
											<option value="Completed" {{ old('status', isset($editData) ? ($editData->status ?? '') : '') == 'Completed' ? 'selected' : '' }}>{{ $getCurrentTranslation['completed'] ?? 'completed' }}</option>
											<option value="Cancelled" {{ old('status', isset($editData) ? ($editData->status ?? '') : '') == 'Cancelled' ? 'selected' : '' }}>{{ $getCurrentTranslation['cancelled'] ?? 'cancelled' }}</option>
										</select>
									</div>
								</div>
								<div class="col-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['assign_to_users'] ?? 'assign_to_users' }}</label>
										<select class="form-select" name="assigned_user_ids[]" multiple data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_users'] ?? 'select_users' }}">
											@foreach($assignableUsers ?? [] as $u)
												<option value="{{ $u->id }}" {{ in_array($u->id, old('assigned_user_ids', isset($editData) && $editData->assignedUsers ? $editData->assignedUsers->pluck('id')->toArray() : [])) ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
											@endforeach
										</select>
										<small class="text-muted">{{ $getCurrentTranslation['assign_to_users_help'] ?? 'Assign to one or multiple users. They can view and manage this note.' }}</small>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="d-flex justify-content-end my-10">
						<button type="submit" class="btn btn-primary form-submit-btn ajax-submit">
							@if(isset($editData) && !empty($editData))
								<span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
							@else
								<span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
							@endif
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.formScripts')
<script>
$(function() {
	$('.datetimepicker').flatpickr({ enableTime: true, dateFormat: 'Y-m-d H:i' });
});
</script>
@endpush
