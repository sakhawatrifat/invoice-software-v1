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
					@if(isset($listRoute) && !empty($listRoute))
						<li class="breadcrumb-item text-muted">
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['payment_method_list'] ?? 'payment_method_list' }}</a> &nbsp; - 
						</li>
					@endif
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_payment_method'] ?? 'edit_payment_method' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_payment_method'] ?? 'create_payment_method' }}</li>
					@endif
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
				@if(isset($editData) && !empty($editData))
					@method('put')
				@endif

				<div class="col-md-12 m-auto">
					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							@if(isset($editData))
								<h3 class="card-title">{{ $getCurrentTranslation['edit_payment_method'] ?? 'edit_payment_method' }}</h3>
							@else
								<h3 class="card-title">{{ $getCurrentTranslation['create_payment_method'] ?? 'create_payment_method' }}</h3>
							@endif
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['name'] ?? 'name' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['enter_name'] ?? 'enter_name' }}" name="name" ip-required value="{{ old('name') ?? $editData->name ?? '' }}"/>
										@error('name')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="mb-5">
										@php
											$options = [
												0 => 'Inactive',
												1 => 'Active',
											];
											$selected = $editData->status ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}:</label>
										<select name="status" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" ip-required>
											<option value="">----</option>
											@foreach(array_reverse($options, true) as $value => $label)
												<option value="{{ $value }}" {{ (string)$value === (string)$selected ? 'selected' : '' }}>
													{{ $label }}
												</option>
											@endforeach
										</select>
									</div>
								</div>


							</div>
						</div>

					</div>
					<div class="d-flex justify-content-end my-10">
						<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
							@if(isset($editData))
								<span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
							@else
								<span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
							@endif
						</button>
					</div>

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