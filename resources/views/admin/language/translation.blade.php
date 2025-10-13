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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['language_list'] ?? 'language_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['translation'] ?? 'translation' }}</li>
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
				<div class="col-md-12 m-auto">
					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['update_translation'] ?? 'update_translation' }}</h3>
							<div class="card-toolbar" style="min-width: 300px">
								<form class="w-100" action="{{ route('admin.language.translate.key', $language->id) }}" method="post">
									@csrf()
									<input class="form-control" type="text" name="lang_key" placeholder="{{ $getCurrentTranslation['type_and_enter_for_new_language_key'] ?? 'type_and_enter_for_new_language_key' }}" required>
								</form>
							</div>
						</div>
						<form class="" method="post" action="{{ route('admin.language.translate.update', $language->id) }}" enctype="multipart/form-data">
							@csrf
							@method('put')
							<div class="card-body">
								<table class="table table-rounded table-striped border gs-7 w-100" style="vertical-align: middle">
									<thead>
										<tr>
											<th><b>{{ $getCurrentTranslation['sl'] ?? 'sl.' }}</b></th>
											<th><b>{{ $getCurrentTranslation['language_key'] ?? 'language_key' }}</b></th>
											<th><b>{{ $getCurrentTranslation['language_value'] ?? 'language_value' }}</b></th>
											<th><b>{{ $getCurrentTranslation['action'] ?? 'action' }}</b></th>
										</tr>
									</thead>
									<tbody>
										@foreach($baseTranslations as $key => $item)
											<tr>
												<th>{{ $baseTranslations->firstItem() + $key }}</th>
												{{-- <th>{{ $item->lang_value ?? $item->lang_key }}</th> --}}
												<th>{{ $item->lang_key }}</th>
												<th><textarea class="form-control" name="translationData[{{ $item->lang_key }}]" rows="1">{{ $langTranslations[$item->lang_key]->lang_value ?? ''}}</textarea></th>
												<th><a class="btn btn-danger btn-sm data-confirm-button" href="{{ route('admin.language.translate.delete', $item->lang_key) }}" title="{{ $getCurrentTranslation['delete'] ?? 'delete' }}"><i class="fa-solid fa-trash"></i></a></th>
											</tr>
										@endforeach
									</tbody>

									<tfoot>
										<tr>
											<td colspan="2"></td>
											<td>
												<div class="d-flex justify-content-start my-10">
													<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
														<span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
													</button>
												</div>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</form>

						<div class="custom-pagination d-flex justify-content-center mb-10">
							{{ $baseTranslations->links() }}
						</div>
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