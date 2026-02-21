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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['sticky_note_details'] ?? 'sticky_note_details' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($editRoute) && !empty($editRoute))
					<a href="{{ $editRoute }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-pen"></i>
						{{ $getCurrentTranslation['edit'] ?? 'edit' }}
					</a>
				@endif
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
			<div class="col-md-10 m-auto">
				<div class="card rounded border mt-5 bg-white">
					<div class="card-header">
						<h3 class="card-title">{{ $note->note_title }}</h3>
						<div class="card-toolbar">
							<span class="badge badge-light-{{ $note->status == 'Completed' ? 'success' : ($note->status == 'In Progress' ? 'primary' : ($note->status == 'Cancelled' ? 'secondary' : 'warning')) }}">{{ $note->status ?? 'Pending' }}</span>
						</div>
					</div>
					<div class="card-body">
						@if($note->note_description)
							<div class="mb-5">
								<label class="form-label fw-bold">{{ $getCurrentTranslation['note_description'] ?? 'note_description' }}</label>
								<div class="text-gray-700">{!! nl2br(e($note->note_description)) !!}</div>
							</div>
						@endif
						<div class="row">
							<div class="col-md-6">
								<label class="form-label fw-bold">{{ $getCurrentTranslation['deadline'] ?? 'deadline' }}</label>
								<div>{{ $note->deadline ? $note->deadline->format('Y-m-d H:i') : '—' }}</div>
							</div>
							<div class="col-md-6">
								<label class="form-label fw-bold">{{ $getCurrentTranslation['reminder_datetime'] ?? 'reminder_datetime' }}</label>
								<div>{{ $note->reminder_datetime ? $note->reminder_datetime->format('Y-m-d H:i') : '—' }}</div>
							</div>
							<div class="col-md-6 mt-4">
								<label class="form-label fw-bold">{{ $getCurrentTranslation['created_by'] ?? 'created_by' }}</label>
								<div>{{ $note->creator->name ?? '—' }}</div>
							</div>
							<div class="col-md-6 mt-4">
								<label class="form-label fw-bold">{{ $getCurrentTranslation['owner'] ?? 'owner' }}</label>
								<div>{{ $note->user->name ?? '—' }}</div>
							</div>
							@if($note->assignedUsers->isNotEmpty())
								<div class="col-12 mt-4">
									<label class="form-label fw-bold">{{ $getCurrentTranslation['assigned_users'] ?? 'assigned_users' }}</label>
									<div>{{ $note->assignedUsers->pluck('name')->join(', ') }}</div>
								</div>
							@endif
						</div>
					</div>
				</div>

				@if($note->activities->isNotEmpty())
				<div class="card rounded border mt-4 bg-white">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['activity'] ?? 'Activity' }}</h3>
					</div>
					<div class="card-body p-0">
						<div class="accordion accordion-icon-collapse" id="kt_sticky_note_activity_accordion">
							@foreach($note->activities as $index => $activity)
							<div class="accordion-item border-bottom border-gray-200">
								<h2 class="accordion-header" id="activity-heading-{{ $activity->id }}">
									<button class="accordion-button fs-6 {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#activity-collapse-{{ $activity->id }}" aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="activity-collapse-{{ $activity->id }}">
										<span class="bullet bullet-vertical h-25px bg-{{ $activity->action === 'create' ? 'success' : ($activity->action === 'delete' ? 'danger' : ($activity->action === 'status' ? 'primary' : 'warning')) }} me-3"></span>
										<span class="text-capitalize fw-semibold">{{ $activity->action }}</span>
										<span class="text-muted ms-2 fs-7">— {{ $activity->user->name ?? '—' }} · {{ $activity->created_at->format('d M Y, H:i') }}</span>
									</button>
								</h2>
								<div id="activity-collapse-{{ $activity->id }}" class="accordion-collapse collapse pt-3 {{ $index === 0 ? 'show' : '' }}" aria-labelledby="activity-heading-{{ $activity->id }}" data-bs-parent="#kt_sticky_note_activity_accordion">
									<div class="accordion-body pt-0">
										<div class="text-gray-700 mb-0" style="white-space: pre-line;">{{ $activity->changes }}</div>
									</div>
								</div>
							</div>
							@endforeach
						</div>
					</div>
				</div>
				@endif
			</div>
		</div>
	</div>
</div>
@endsection
