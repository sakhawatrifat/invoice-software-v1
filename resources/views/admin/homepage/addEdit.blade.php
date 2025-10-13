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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['homepage_list'] ?? 'homepage_list' }}</a> &nbsp; - 
						</li>
					@endif
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_homepage'] ?? 'edit_homepage' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_homepage'] ?? 'create_homepage' }}</li>
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
								<h3 class="card-title">{{ $getCurrentTranslation['edit_homepage'] ?? 'edit_homepage' }}</h3>
							@else
								<h3 class="card-title">{{ $getCurrentTranslation['create_homepage'] ?? 'create_homepage' }}</h3>
							@endif
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-12 d-none">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['language'] ?? 'language' }}:</label>
										<input type="text" class="form-control" value="{{ $language->name ?? '' }}" readonly/>
										<input type="hidden" name="lang" value="{{ $editData->lang ?? request()->lang }}"/>
									</div>
								</div>
								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['banner'] ?? 'banner' }}:</label>
										@php
											//dd($editData->banner);
											$isFileExist = false;
											if (isset($editData) && !empty($editData->banner)) {
												if (!empty($editData->banner_url)) {
													$isFileExist = true;
												}
											}
										@endphp
	
										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="banner" @if(empty(old('banner')) && empty($editData->banner)) ip-required @endif {{ $isFileExist ? '' : 'ip-required' }}/>
	
										@if($isFileExist)
											<div class="img-wrapper mt-3"><img src="{{ $editData->banner_url }}" alt="Logo" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif
	
										@error('banner')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
							</div>
						</div>
					</div>


					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['feature_content'] ?? 'feature_content' }}</h3>
							<div class="card-toolbar">
								<button type="button" class="btn btn-sm btn-success append-item-add-btn">
									<i class="fa-solid fa-plus"></i>
								</button>
							</div>
						</div>
						<div class="card-body append-item-wrapper">
							@if(isset($editData) && $editData->featureContent && is_array($editData->featureContent) && count($editData->featureContent))
								@foreach($editData->featureContent as $item)
									<div class="append-item rounded border p-5 mb-5">
										<div class="append-item-header d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['feature'] ?? 'feature' }} <span class="append-item-count"></span></h3>
											<div class="append-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>
										<div class="row p-5">
											<div class="col-md-6">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['image'] ?? 'image' }} (300x300):</label>
													@php
														$isFileExist = false;
														$imgUrl = null;
														if (isset($item['image']) && !empty($item['image'])) {
															$imgUrl = getUploadedUrl($item['image']);
															if (!empty($imgUrl)) {
																$isFileExist = true;
															}
														}
													@endphp
													<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="featureContent[0][image]"/>
													<input type="hidden" name="featureContent[0][old_image_url]" value="{{ $item['image'] }}"/>

													@if($isFileExist)
														<div class="img-wrapper mt-3"><img src="{{ $imgUrl }}" alt="Logo" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
													@endif
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['title'] ?? 'title' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['title_placeholder'] ?? 'title_placeholder' }}" name="featureContent[0][title]" value="{{ $item['title'] ?? '' }}"/>
												</div>
											</div>
											

											<div class="col-md-12">
												<div class="mb-5">
													<label class="form-label">{{ $getCurrentTranslation['details'] ?? 'details' }}:</label>
													<textarea class="form-control" name="featureContent[0][details]" rows="2" placeholder="{{ $getCurrentTranslation['details_placeholder'] ?? 'details_placeholder' }}">{{ $item['details'] ?? '' }}</textarea>
												</div>
											</div>
										</div>
									</div>
								@endforeach
							@else
								<div class="append-item rounded border p-5 mb-5">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['feature'] ?? 'feature' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
									<div class="row p-5">
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['image'] ?? 'image' }}(300x300):</label>
												<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="featureContent[0][image]"/>
												<input type="hidden" name="featureContent[0][old_image_url]" value=""/>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['title'] ?? 'title' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['title_placeholder'] ?? 'title_placeholder' }}" name="featureContent[0][title]"/>
											</div>
										</div>
										

										<div class="col-md-12">
											<div class="mb-5">
												<label class="form-label">{{ $getCurrentTranslation['details'] ?? 'details' }}:</label>
												<textarea class="form-control" name="featureContent[0][details]" rows="2" placeholder="{{ $getCurrentTranslation['details_placeholder'] ?? 'details_placeholder' }}"></textarea>
											</div>
										</div>
									</div>
								</div>
							@endif
						</div>
					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['info'] ?? 'info' }}</h3>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['title'] ?? 'title' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['title_placeholder'] ?? 'title_placeholder' }}" name="title" ip-required value="{{ old('title') ?? $editData->title ?? '' }}"/>
										@error('title')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['subtitle'] ?? 'subtitle' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['subtitle_placeholder'] ?? 'subtitle_placeholder' }}" name="subtitle" value="{{ old('subtitle') ?? $editData->subtitle ?? '' }}"/>
										@error('subtitle')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['description'] ?? 'description' }}</label>
										<textarea class="form-control ck-editor" name="description" rows="3" placeholder="{{ $getCurrentTranslation['description_placeholder'] ?? 'description_placeholder' }}">{{ old('description') ?? $editData->description ?? '' }}</textarea>
										@error('description')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['content_data'] ?? 'content_data' }}</h3>
							<div class="card-toolbar">
								<button type="button" class="btn btn-sm btn-success append-item-add-btn">
									<i class="fa-solid fa-plus"></i>
								</button>
							</div>
						</div>
						<div class="card-body append-item-wrapper">
							@if(isset($editData) && $editData->content && is_array($editData->content) && count($editData->content))
								@foreach($editData->content as $item)
									<div class="append-item rounded border p-5 mb-5">
										<div class="append-item-header d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['content'] ?? 'content' }} <span class="append-item-count"></span></h3>
											<div class="append-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>
										<div class="row p-5">
											<div class="col-md-6">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['image'] ?? 'image' }}:</label>
													@php
														$isFileExist = false;
														$imgUrl = null;
														if (isset($item['image']) && !empty($item['image'])) {
															$imgUrl = getUploadedUrl($item['image']);
															if (!empty($imgUrl)) {
																$isFileExist = true;
															}
														}
													@endphp

													{{-- <input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="image" @if(empty(old('image')) && empty($imgUrl)) ip-required @endif {{ $isFileExist ? '' : 'ip-required' }}/> --}}

													<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="content[0][image]"/>
													<input type="hidden" name="content[0][old_image_url]" value="{{ $item['image'] }}"/>

													@if($isFileExist)
														<div class="img-wrapper mt-3"><img src="{{ $imgUrl }}" alt="Logo" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
													@endif
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['title'] ?? 'title' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['title_placeholder'] ?? 'title_placeholder' }}" name="content[0][title]" value="{{ $item['title'] ?? '' }}"/>
												</div>
											</div>
											

											<div class="col-md-12">
												<div class="mb-5">
													<label class="form-label">{{ $getCurrentTranslation['details'] ?? 'details' }}:</label>
													<textarea class="form-control ck-editor" name="content[0][details]" rows="2" placeholder="{{ $getCurrentTranslation['details_placeholder'] ?? 'details_placeholder' }}">{{ $item['details'] ?? '' }}</textarea>
												</div>
											</div>
										</div>
									</div>
								@endforeach
							@else
								<div class="append-item rounded border p-5 mb-5">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['content'] ?? 'content' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
									<div class="row p-5">
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['image'] ?? 'image' }}:</label>
												{{-- <input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="image" @if(empty(old('image')) && empty($imgUrl)) ip-required @endif {{ $isFileExist ? '' : 'ip-required' }}/> --}}

												<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="content[0][image]"/>
												<input type="hidden" name="content[0][old_image_url]" value=""/>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['title'] ?? 'title' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['title_placeholder'] ?? 'title_placeholder' }}" name="content[0][title]"/>
											</div>
										</div>
										

										<div class="col-md-12">
											<div class="mb-5">
												<label class="form-label">{{ $getCurrentTranslation['details'] ?? 'details' }}:</label>
												<textarea class="form-control ck-editor" name="content[0][details]" rows="2" placeholder="{{ $getCurrentTranslation['details_placeholder'] ?? 'details_placeholder' }}"></textarea>
											</div>
										</div>
									</div>
								</div>
							@endif
						</div>
					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['Other Settings'] ?? 'Other Settings' }}</h3>
							<div class="card-toolbar">
								
							</div>
						</div>
						<div class="card-body append-item-wrapper">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-5">
										@php
											$options = [
												0 => 'Inactive',
												1 => 'Active',
											];
											$selected = $editData->is_registration_enabled ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['registration_status'] ?? 'registration_status' }}:</label>
										<select name="is_registration_enabled" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" ip-required>
											<option value="">----</option>
											@foreach(array_reverse($options, true) as $value => $label)
												<option value="{{ $value }}" {{ (string)$value === (string)$selected ? 'selected' : '' }}>
													{{ $label }}
												</option>
											@endforeach
										</select>
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['auth_bg'] ?? 'auth_bg' }}:</label>
										@php
											$isFileExist = false;
											if (isset($editData) && !empty($editData->auth_bg_image)) {
												if (!empty($editData->auth_bg_image_url)) {
													$isFileExist = true;
												}
											}
										@endphp
	
										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="auth_bg_image"/>
	
										@if($isFileExist)
											<div class="img-wrapper mt-3"><img src="{{ $editData->auth_bg_image_url }}" alt="Logo" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif
	
										@error('auth_bg_image')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
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