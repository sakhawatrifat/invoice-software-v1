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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['user_list'] ?? 'user_list' }}</a> &nbsp; - 
						</li>
					@endif
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_user'] ?? 'edit_user' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_user'] ?? 'create_user' }}</li>
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
							<h3 class="card-title">{{ $getCurrentTranslation['user'] ?? 'user' }}</h3>
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">

								<div class="col-md-6">
									<div class="input-item-wrap mb-5">
										<label>{{ $getCurrentTranslation['image'] ?? 'image' }}:</label>
										@php
											$selected = old('image') ?? ($editData->image_url ?? '');

											$isFileExist = false;
											if (isset($selected) && !empty($selected)) {
												if (!empty($selected)) {
													$isFileExist = true;
												}
											}

										@endphp
										<div class="file-input-box">
											<input name="image" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.png,.jpg" {{ empty($selected) ? '' : '' }}>
										</div>
										<div class="preview-image">
											<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img mt-2 ml-2" width="30%" style="{{ $selected ? '' : 'display: none;' }}">
										</div>
										@error('image')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['user_full_name_label'] ?? 'user_full_name_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['user_full_name_placeholder'] ?? 'user_full_name_placeholder' }}" name="name" ip-required value="{{ old('name') ?? $editData->name ?? '' }}"/>
										@error('name')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['designation_label'] ?? 'designation_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['designation_placeholder'] ?? 'designation_placeholder' }}" name="designation" ip-required value="{{ old('designation') ?? $editData->designation ?? '' }}"/>
										@error('designation')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Address:</label>
										<textarea class="form-control ck-editor" placeholder="Enter full address" name="address" rows="1">{{ old('address') ?? $editData->address ?? '' }}</textarea>
										@error('address')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Phone:</label>
										<input type="text" class="form-control" placeholder="Enter phone number" name="phone" value="{{ old('phone') ?? $editData->phone ?? '' }}"/>
										@error('phone')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['email_placeholder'] ?? 'email_placeholder' }}" name="email" ip-required value="{{ old('email') ?? $editData->email ?? '' }}"/>
										@error('email')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label"> 
											{{ $getCurrentTranslation['email_verified_at_label'] ?? 'email_verified_at_label' }}: <br>
											<small class="text-info">{{ $getCurrentTranslation['email_verified_at_note'] ?? 'email_verified_at_note' }}</small>
										</label>
										<input type="text" class="form-control flatpickr-input" placeholder="{{ $getCurrentTranslation['email_verified_at_placeholder'] ?? 'email_verified_at_placeholder' }}" name="email_verified_at" ip-required value="{{ old('email_verified_at') ?? $editData->email_verified_at ?? '' }}"/>
										@error('email_verified_at')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<label class="form-label">{{ $getCurrentTranslation['password_label'] ?? 'password_label' }}:</label>
									<div class="form-item mb-5 position-relative">
										<input type="password" name="password" placeholder="{{ $getCurrentTranslation['password_placeholder'] ?? 'password_placeholder' }}" class="form-control bg-transparent password" required autocomplete="new-password" value="">
										<span class="toggle-password" minlength="8" data-show="f06e" data-hide="f070"></span>
									</div>
									@error('password')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>

								<div class="col-md-6">
									<label class="form-label">{{ $getCurrentTranslation['confirm_password_label'] ?? 'confirm_password_label' }}:</label>
									<div class="form-item mb-5 position-relative">
										<input type="password" name="password_confirmation" placeholder="{{ $getCurrentTranslation['confirm_password_placeholder'] ?? 'confirm_password_placeholder' }}" class="form-control bg-transparent password" minlength="8" required>
										<span class="toggle-password" data-show="f06e" data-hide="f070"></span>
									</div>
									@error('password_confirmation')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>

								@if(Auth::user()->user_type == 'admin' && Auth::user()->id == 1)
									<div class="col-md-6">
										<div class="mb-5">
											@php
												$options = [
													'Inactive' => 'Inactive',
													'Active' => 'Active',
												];
												$selected = $editData->status ?? '';
											@endphp
											<label class="form-label">{{ $getCurrentTranslation['active_status_label'] ?? 'active_status_label' }}:</label>
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
								@endif
							</div>
						</div>

					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['company'] ?? 'company' }}</h3>
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['company_name_label'] ?? 'company_name_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['company_name_placeholder'] ?? 'company_name_placeholder' }}" name="company_name" ip-required value="{{ old('company_name') ?? $editData->company->company_name ?? '' }}"/>
										@error('company_name')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['tagline_label'] ?? 'tagline_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['tagline_placeholder'] ?? 'tagline_placeholder' }}" name="tagline" value="{{ old('tagline') ?? $editData->company->tagline ?? '' }}"/>
										@error('tagline')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['website_url_label'] ?? 'website_url_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['website_url_placeholder'] ?? 'website_url_placeholder' }}" name="website_url" value="{{ old('website_url') ?? $editData->company->website_url ?? '' }}"/>
										@error('website_url')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['invoice_prefix_label'] ?? 'invoice_prefix_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['invoice_prefix_placeholder'] ?? 'invoice_prefix_placeholder' }}" name="invoice_prefix" value="{{ old('invoice_prefix') ?? $editData->company->invoice_prefix ?? '' }}"/>
										@error('invoice_prefix')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['company_invoice_id_label'] ?? 'company_invoice_id_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['company_invoice_id_placeholder'] ?? 'company_invoice_id_placeholder' }}" name="company_invoice_id" value="{{ old('company_invoice_id') ?? $editData->company->company_invoice_id ?? '' }}"/>
										@error('company_invoice_id')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Light Logo:</label>
										@php
											$isFileExist = false;
											if (isset($editData) && !empty($editData->company->light_logo)) {
												if (!empty($editData->company->light_logo_url)) {
													$isFileExist = true;
												}
											}
										@endphp

										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="light_logo" @if(empty(old('light_logo')) && empty($editData->company->light_logo)) @endif />

										@if($isFileExist)
											<div class="mt-3"><img src="{{ $editData->company->light_logo_url }}" alt="Image" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif

										@error('light_logo')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-6">
									<div class="input-item-wrap mb-5">
										<label>{{ $getCurrentTranslation['logo_label'] ?? 'logo_label' }}:</label>
										@php
											$selected = old('dark_logo') ?? ($editData->company->dark_logo_url ?? '');

											$isFileExist = false;
											if (isset($selected) && !empty($selected)) {
												if (!empty($selected)) {
													$isFileExist = true;
												}
											}

										@endphp
										<div class="file-input-box">
											<input name="dark_logo" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.png,.jpg" {{ empty($selected) ? 'ip-required' : '' }}>
										</div>
										<div class="preview-image">
											<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img mt-2 ml-2" width="30%" style="{{ $selected ? '' : 'display: none;' }}">
										</div>
										@error('dark_logo')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Light Icon:</label>
										@php
											$isFileExist = false;
											if (isset($editData) && !empty($editData->company->light_icon)) {
												if (!empty($editData->company->light_icon_url)) {
													$isFileExist = true;
												}
											}
										@endphp

										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="light_icon" @if(empty(old('light_icon')) && empty($editData->company->light_icon)) @endif/>

										@if($isFileExist)
											<div class="mt-3"><img src="{{ $editData->company->light_icon_url }}" alt="Image" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif

										@error('light_icon')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-6">
									<div class="input-item-wrap mb-5">
										<label>{{ $getCurrentTranslation['icon_label'] ?? 'icon_label' }}:</label>
										@php
											$selected = old('dark_icon') ?? ($editData->company->dark_icon_url ?? '');

											$isFileExist = false;
											if (isset($selected) && !empty($selected)) {
												if (!empty($selected)) {
													$isFileExist = true;
												}
											}

										@endphp
										<div class="file-input-box">
											<input name="dark_icon" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.png,.jpg" {{ empty($selected) ? 'ip-required' : '' }}>
										</div>
										<div class="preview-image">
											<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img mt-2 ml-2" width="30%" style="{{ $selected ? '' : 'display: none;' }}">
										</div>
										@error('dark_icon')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Light Seal:</label>
										@php
											$isFileExist = false;
											if (isset($editData) && !empty($editData->company->light_seal)) {
												if (!empty($editData->company->light_seal_url)) {
													$isFileExist = true;
												}
											}
										@endphp

										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="light_seal" @if(empty(old('light_seal')) && empty($editData->company->light_seal)) @endif/>

										@if($isFileExist)
											<div class="mt-3"><img src="{{ $editData->company->light_seal_url }}" alt="Image" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif

										@error('light_seal')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-6">
									<div class="input-item-wrap mb-5">
										<label>{{ $getCurrentTranslation['seal_label'] ?? 'seal_label' }}:</label>
										@php
											$selected = old('dark_seal') ?? ($editData->company->dark_seal_url ?? '');

											$isFileExist = false;
											if (isset($selected) && !empty($selected)) {
												if (!empty($selected)) {
													$isFileExist = true;
												}
											}

										@endphp
										<div class="file-input-box">
											<input name="dark_seal" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.png,.jpg" {{ empty($selected) ? 'ip-required' : '' }}>
										</div>
										<div class="preview-image">
											<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img mt-2 ml-2" width="30%" style="{{ $selected ? '' : 'display: none;' }}">
										</div>
										@error('dark_seal')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								

								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['address_label'] ?? 'address_label' }}:</label>
										<textarea class="form-control ck-editor" placeholder="{{ $getCurrentTranslation['address_placeholder'] ?? 'address_placeholder' }}" name="address" rows="1" ip-required>{{ old('address') ?? $editData->company->address ?? '' }}</textarea>
										@error('address')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['phone_label'] ?? 'phone_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['phone_placeholder'] ?? 'phone_placeholder' }}" name="phone_1" ip-required value="{{ old('phone_1') ?? $editData->company->phone_1 ?? '' }}"/>
										@error('phone_1')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Alt Phone:</label>
										<input type="text" class="form-control" placeholder="Enter alt phone number" name="phone_2" value="{{ old('phone_2') ?? $editData->company->phone_2 ?? '' }}"/>
										@error('phone_2')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</label>
										<input type="email" class="form-control" placeholder="{{ $getCurrentTranslation['email_placeholder'] ?? 'email_placeholder' }}" name="email_1" ip-required value="{{ old('email_1') ?? $editData->company->email_1 ?? '' }}"/>
										@error('email_1')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Alt Email:</label>
										<input type="email" class="form-control" placeholder="Enter alt email number" name="email_2" value="{{ old('email_2') ?? $editData->company->email_2 ?? '' }}"/>
										@error('email_2')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								<div class="col-md-6">
									@php
										$options = $currencies;
										$selected = $editData->company->currency_id ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['currency_label'] ?? 'currency_label' }}:</label>
									<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['currency_placeholder'] ?? 'currency_placeholder' }}" name="currency_id" ip-required>
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>
												{{ $option->currency_name }} ({{ $option->symbol }})
											</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['system_settings'] ?? 'system_settings' }}</h3>
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									@php
										$options = $languages;
										$selected = $editData->default_language ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['default_language_label'] ?? 'default_language_label' }}:</label>
									<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['default_language_placeholder'] ?? 'default_language_placeholder' }}" name="default_language" ip-required>
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option->code }}" {{ $option->code == $selected ? 'selected' : '' }}>
												{{ $option->name }} ({{ $option->code }})
											</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['permissions'] ?? 'permissions' }}</h3>
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<table class="table table-rounded table-striped border dataTable no-footer gs-7 align-middle permission-table">
								<thead class="table-light">
									<tr>
										<th>{{ $getCurrentTranslation['module'] ?? 'module' }}</th>
										<th>{{ $getCurrentTranslation['permissions'] ?? 'permissions' }}</th>
									</tr>
								</thead>
								<tbody>
									@php
										$userType = 'user';

										$allPermissions = getPermissionList();

										$permissions = collect($allPermissions)->filter(function ($item) use ($userType) {
											return $userType === 'admin' || $item['for'] === 'all_user';
										});

										$selectedPermissions = $editData->permissions ?? [];
									@endphp

									@foreach($permissions as $groupIndex => $item)
										@php
											// Get only the permission keys for this group
											$groupKeys = array_column($item['permissions'], 'key');

											// Check if all group permissions are selected
											$isGroupChecked = count(array_intersect($groupKeys, $selectedPermissions)) === count($groupKeys);
										@endphp
										<tr class="{{ $groupIndex % 2 == 0 ? 'even' : 'odd' }} {{ $item['for'] ?? '' }}">
											<td>
												<div class="form-check">
													<input type="checkbox"
														class="form-check-input group-checkbox"
														id="groupCheck{{ $groupIndex }}"
														data-group="{{ $groupIndex }}"
														{{ $isGroupChecked ? 'checked' : '' }}>
													<label class="form-check-label user-select-none" for="groupCheck{{ $groupIndex }}">
														{{ $getCurrentTranslation[$item['title']] ?? $item['title'] }}
													</label>
												</div>
											</td>
											<td>
												<ul class="list-unstyled mb-0">
													@foreach($item['permissions'] as $permIndex => $permission)
														<li>
															<div class="form-check my-3">
																<input type="checkbox"
																	class="form-check-input permission-checkbox group-{{ $groupIndex }}"
																	name="permissions[]"
																	value="{{ $permission['key'] }}"
																	id="perm{{ $groupIndex }}_{{ $permIndex }}"
																	{{ in_array($permission['key'], $selectedPermissions) ? 'checked' : '' }}>
																<label class="form-check-label user-select-none" for="perm{{ $groupIndex }}_{{ $permIndex }}">
																	{{ $getCurrentTranslation[$permission['title']] ?? $permission['title'] }}
																</label>
															</div>
														</li>
													@endforeach
												</ul>
											</td>
										</tr>
									@endforeach

								</tbody>
							</table>
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
	checkUserType();
	$(document).on('change', '[name="user_type"]', function() {
		checkUserType();
	});

	function checkUserType(){
		var selectedType = $('[name="user_type"]').val();

		if(selectedType == 'user'){
			$('.user-dropdown').slideDown();

			$('.permission-table tr.admin input[type="checkbox"]').prop('checked', false);
			$('.permission-table').find('tr.admin').slideUp();
		}else if(selectedType == 'admin'){
			$('.user-dropdown').slideUp();
			
			$('.permission-table').find('tr.all_user').slideDown();
			$('.permission-table').find('tr.admin').slideDown();
		}else{
			$('.permission-table tr.admin input[type="checkbox"]').prop('checked', false);
			$('.permission-table').find('tbody tr').slideUp();
		}

		if(selectedType == undefined){
			$('.permission-table').find('tr.all_user').slideDown();
		}
	}



    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.group-checkbox').forEach(function (groupCheckbox) {
            groupCheckbox.addEventListener('change', function () {
                const group = this.getAttribute('data-group');
                const checkboxes = document.querySelectorAll('.group-' + group);
                checkboxes.forEach(function (cb) {
                    cb.checked = groupCheckbox.checked;
                });
            });
        });
    });
</script>
@endpush