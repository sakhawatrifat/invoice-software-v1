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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['staff_list'] ?? 'staff_list' }}</a> &nbsp; - 
						</li>
					@endif
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_staff'] ?? 'edit_staff' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_staff'] ?? 'create_staff' }}</li>
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
							<h3 class="card-title">{{ $getCurrentTranslation['staff'] ?? 'staff' }}</h3>
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">
								{{-- <div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">Image:</label>
										@php
											$isFileExist = false;
											if (isset($editData) && !empty($editData->image)) {
												if (!empty($editData->image_url)) {
													$isFileExist = true;
												}
											}
										@endphp

										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="image" @if(empty(old('image')) && empty($editData->image)) @endif {{ $isFileExist ? '' : 'ip-required' }}/>

										@if($isFileExist)
											<div class="mt-3"><img src="{{ $editData->image_url }}" alt="Image" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif

										@error('image')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div> --}}

								@if(Auth::user()->user_type == 'admin' && !isset($editData))
									<div class="col-md-4">
										@php
											$options = [['name' => 'admin'], ['name' => 'user']];
											$options = json_decode(json_encode($options));

											$selected = $editData->user_type ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['user_type'] ?? 'user_type' }}:</label>
										<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['select_user_type'] ?? 'select_user_type' }}" name="user_type" ip-required>
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->name }}" {{ $option->name == $selected ? 'selected' : '' }}>
													{{ ucfirst($option->name) }}
												</option>
											@endforeach
										</select>
									</div>
								@endif

								@if(Auth::user()->user_type == 'admin')
									<div class="col-md-4 user-dropdown">
										@php
											$options = $users->where('user_type', 'user');
											$selected = $editData->parent_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['user'] ?? 'user' }}:</label>
										<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['select_user'] ?? 'select_user' }}" name="user_id">
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>
													{{ $option->name }} {{ $option->company && $option->company->name ? '(' . $option->company->name . ')' : '' }}
												</option>
											@endforeach
										</select>
									</div>
								@endif

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['staff_full_name_label'] ?? 'staff_full_name_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['staff_full_name_placeholder'] ?? 'staff_full_name_placeholder' }}" name="name" ip-required value="{{ old('name') ?? $editData->name ?? '' }}"/>
										@error('name')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-4">
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

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['email_placeholder'] ?? 'email_placeholder' }}" name="email" ip-required value="{{ old('email') ?? $editData->email ?? '' }}"/>
										@error('email')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								{{-- <div class="col-md-4">
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
								</div> --}}

								<div class="col-md-4">
									<label class="form-label">{{ $getCurrentTranslation['password_label'] ?? 'password_label' }}:</label>
									<div class="form-item mb-5 position-relative">
										<input type="password" name="password" placeholder="{{ $getCurrentTranslation['password_placeholder'] ?? 'password_placeholder' }}" class="form-control bg-transparent password" required autocomplete="new-password" value="">
										<span class="toggle-password" minlength="8" data-show="f06e" data-hide="f070"></span>
									</div>
									@error('password')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>

								<div class="col-md-4">
									<label class="form-label">{{ $getCurrentTranslation['confirm_password_label'] ?? 'confirm_password_label' }}:</label>
									<div class="form-item mb-5 position-relative">
										<input type="password" name="password_confirmation" placeholder="{{ $getCurrentTranslation['confirm_password_placeholder'] ?? 'confirm_password_placeholder' }}" class="form-control bg-transparent password" minlength="8" required>
										<span class="toggle-password" data-show="f06e" data-hide="f070"></span>
									</div>
									@error('password_confirmation')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>

								{{-- @if(Auth::user()->user_type == 'admin' && Auth::user()->id == 1) --}}
									<div class="col-md-4">
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
								{{-- @endif --}}
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
								<div class="col-md-4">
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
						<div class="card-body" id="permissionList">
							<table class="table table-rounded table-striped border dataTable no-footer gs-7 align-middle permission-table">
								<thead class="table-light">
									<tr>
										<th>{{ $getCurrentTranslation['module'] ?? 'module' }}</th>
										<th>{{ $getCurrentTranslation['permissions'] ?? 'permissions' }}</th>
									</tr>
								</thead>
								<tbody>
									@php
										$userType = Auth::user()->user_type;

										// full permission list
										$allPermissions = getPermissionList();

										if (isset($editData) && !empty($editData->parent_id)) {
											// decode parent permissions (array of keys)
											$parentPermissions = is_string($editData->parent->permissions)
												? json_decode($editData->parent->permissions, true)
												: ($editData->parent->permissions ?? []);

											// filter allPermissions to only include parent permissions
											$permissions = collect($allPermissions)->map(function ($group) use ($parentPermissions) {
												$group['permissions'] = collect($group['permissions'])
													->filter(fn($perm) => in_array($perm['key'], $parentPermissions))
													->values()
													->toArray();
												return $group;
											})
											->filter(fn($group) => !empty($group['permissions'])) // remove groups with no permissions
											->values()
											->toArray();
										} else {
											// decode parent permissions (array of keys)
											$parentPermissions = Auth::user()->permissions;

											// filter allPermissions to only include parent permissions
											$permissions = collect($allPermissions)->map(function ($group) use ($parentPermissions) {
												$group['permissions'] = collect($group['permissions'])
													->filter(fn($perm) => in_array($perm['key'], $parentPermissions))
													->values()
													->toArray();
												return $group;
											})
											->filter(fn($group) => !empty($group['permissions'])) // remove groups with no permissions
											->values()
											->toArray();
										}
										

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



	$(document).on('change', '[name="user_id"]', function () {
		let parentId = $(this).val();
		let userId = "{{ $editData->id ?? '' }}"; 
		let url = "{{ route('staff.loadPermissions') }}" + "?parentId=" + parentId + "&userId=" + userId;

		$.ajax({
			url: url,
			type: 'GET', // Or 'POST' if you need CSRF
			data: {
				_token: '{{ csrf_token() }}'
			},
			beforeSend: function () {
				$('.r-preloader').show();
			},
			success: function (response) {
				$('.r-preloader').hide();

				if (response.is_success) {
					toastr.success(response.message);
					if(response.view_page){
						$('#permissionList').empty();
						$('#permissionList').append(response.view_page);
					}else{
						toastr.error('View render failled');
					}
				} else {
					toastr.error(response.message);
				}
			},
			error: function (xhr) {
				$('.r-preloader').hide();

				if (xhr.status === 419) {
					Swal.fire({
						icon: 'error',
						title: getCurrentTranslation.csrf_token_mismatch ?? 'csrf_token_mismatch',
						text: getCurrentTranslation.csrf_token_mismatch_msg ?? 'csrf_token_mismatch_msg',
						confirmButtonText: getCurrentTranslation.yes_reload_page || 'yes_reload_page'
					}).then(() => location.reload());
					return;
				}

				toastr.error(getCurrentTranslation.something_went_wrong ?? 'something_went_wrong');
			}
		});
	});

	
</script>
@endpush