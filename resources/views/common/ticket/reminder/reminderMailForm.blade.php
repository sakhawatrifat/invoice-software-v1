@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
	$getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
	<style>
		.customer-name-checkbox-wrap {
			white-space: nowrap; /* keep text on a single line */
		}
	</style>
	<!--Toolbar-->
	<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
		<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
			<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</a> &nbsp; - 
					</li>
					<li class="breadcrumb-item">{{ $getCurrentTranslation['reminder_mail_informations'] ?? 'reminder_mail_informations' }}</li>
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

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<form class="" method="post" action="{{ $saveRoute }}" enctype="multipart/form-data">
				@csrf
				
                <div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['reminder_mail_informations'] ?? 'reminder_mail_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-item mb-5 mail-content-wrapper">
									<label class="form-label mb-0">{{ $getCurrentTranslation['mail_content_label'] ?? 'mail_content_label' }}:</label>
									<br><small class="d-block mb-2">{{ $getCurrentTranslation['mail_content_note'] ?? 'mail_content_note' }}</small>
									<textarea class="form-control summernote" name="reminder_mail_content" rows="10">{{ old('mail_content') ?? Auth::user()->company_data->reminder_mail_content ?? getTravelReminderEmailContent() }}</textarea>
									@error('mail_content')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="d-flex justify-content-end my-10">
					<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
						<span class="indicator-label">{{ $getCurrentTranslation['save_reminder_information'] ?? 'save_reminder_information' }}</span>
					</button>
				</div>
			</form>
		</div>
		<!--end::Content container-->
	</div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
<script>
	//
</script>
@endpush