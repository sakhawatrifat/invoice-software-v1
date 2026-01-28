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
				@if(hasPermission('staff.index') && isset($editData) && !empty($editData))
					<a href="{{ route('staff.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-pager"></i>
						{{ $getCurrentTranslation['details'] ?? 'details' }}
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
							<div class="card-toolbar">
								<div class="d-flex justify-content-end">
									<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
										@if(isset($editData))
											<span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
										@else
											<span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
										@endif
									</button>
								</div>
							</div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-4">
									<div class="input-item-wrap mb-5">
										<label class="form-label">{{ $getCurrentTranslation['image'] ?? 'image' }} (100x100):</label>
										@php
											$selected = isset($editData) && !empty($editData->image_url) ? $editData->image_url : '';
											$isFileExist = !empty($selected);
										@endphp
										<div class="file-input-box">
											<input name="image" class="form-control image-input" type="file" max-size="0" accept=".heic,.png,.jpg,.jpeg" data-old="{{ $selected ?? '' }}">
										</div>
										<div class="preview-image mt-2" style="{{ $isFileExist ? '' : 'display: none;' }}" data-old="{{ $selected ?? '' }}">
											@if($isFileExist)
												<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="{{ $selected }}">
													<img src="{{ $selected }}" alt="Image" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;" old-selected="{{ $selected }}">
												</div>
											@else
												<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="" style="display: none;">
													<img src="" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;">
												</div>
											@endif
										</div>
										@error('image')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								@if(Auth::user()->user_type == 'admin')
									<div class="col-md-4 d-none">
										@php
											$options = [['name' => 'admin'], ['name' => 'user']];
											$options = json_decode(json_encode($options));

											$selected = isset($editData) ? ($editData->user_type ?? 'admin') : 'admin';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['user_type'] ?? 'user_type' }}:</label>
										<select class="form-select select2-with-images" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" name="user_type" ip-required>
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
									<div class="col-md-4 user-dropdown d-none">
										@php
											$options = $users->where('user_type', 'user');
											$selected = isset($editData) ? ($editData->parent_id ?? '') : '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['user'] ?? 'user' }}:</label>
										<select class="form-select select2-with-images" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" name="user_id">
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>
													{{ $option->name }}
													({{ $option->designation?->name ?? 'N/A' }})
													{{ $option->company && $option->company->name ? ' - ' . $option->company->name : '' }}
												</option>
											@endforeach
										</select>
									</div>
								@endif

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['staff_full_name_label'] ?? 'staff_full_name_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['staff_full_name_placeholder'] ?? 'staff_full_name_placeholder' }}" name="name" ip-required value="{{ old('name') ?? (isset($editData) ? ($editData->name ?? '') : '') }}"/>
										@error('name')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['department_name'] ?? 'department_name' }}:</label>
										@php
											$options = $departments ?? collect();
											$selected = old('department_id') ?? (isset($editData) ? ($editData->department_id ?? '') : '');
										@endphp
										<select name="department_id" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ (string)$option->id === (string)$selected ? 'selected' : '' }}>
													{{ $option->name }}
												</option>
											@endforeach
										</select>
										@error('department_id')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['designation_name'] ?? 'designation_name' }}:</label>
										@php
											$options = $designations ?? collect();
											$selected = old('designation_id') ?? (isset($editData) ? ($editData->designation_id ?? '') : '');
										@endphp
										<select name="designation_id" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ (string)$option->id === (string)$selected ? 'selected' : '' }}>
													{{ $option->name }}
												</option>
											@endforeach
										</select>
										@error('designation_id')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['joining_date'] ?? 'joining_date' }}:</label>
										<input type="text" placeholder="{{ $getCurrentTranslation['joining_date_placeholder'] ?? 'joining_date_placeholder' }}" class="form-control flatpickr-input" name="joining_date" value="{{ old('joining_date') ?? (isset($editData) && $editData->joining_date ? \Carbon\Carbon::parse($editData->joining_date)->format('Y-m-d') : '') }}"/>
										@error('joining_date')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-4">
									<div class="mb-5">
										@php
											$options = [
												'Full-time' => 'Full-time',
												'Part-time' => 'Part-time',
												'Contract' => 'Contract',
											];
											$selected = old('employment_type') ?? (isset($editData) ? ($editData->employment_type ?? '') : '');
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['employment_type'] ?? 'employment_type' }}:</label>
										<select name="employment_type" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
											<option value="">----</option>
											@foreach($options as $value => $label)
												<option value="{{ $value }}" {{ (string)$value === (string)$selected ? 'selected' : '' }}>
													{{ $label }}
												</option>
											@endforeach
										</select>
										@error('employment_type')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-4">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['salary_amount'] ?? 'salary_amount' }} ({{Auth::user()->company_data->currency->short_name ?? ''}}):</label>
										<input type="text" min="0" class="form-control number-validate" placeholder="{{ $getCurrentTranslation['salary_amount_placeholder'] ?? 'Enter salary amount' }}" name="salary_amount" value="{{ old('salary_amount') ?? (isset($editData) ? ($editData->salary_amount ?? '') : '') }}"/>
										@error('salary_amount')
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
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['email_placeholder'] ?? 'email_placeholder' }}" name="email" ip-required value="{{ old('email') ?? (isset($editData) ? ($editData->email ?? '') : '') }}"/>
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
												$selected = isset($editData) ? ($editData->status ?? '') : '';
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
							<h3 class="card-title">{{ $getCurrentTranslation['user_documents'] ?? 'user_documents' }}</h3>
							<div class="card-toolbar">
								<button type="button" class="btn btn-sm btn-success append-item-add-btn">
									<i class="fa-solid fa-plus"></i>
								</button>
							</div>
						</div>
						<div class="card-body append-item-wrapper">
							@php
								$documents = $userDocuments ?? collect();
							@endphp

							@if($documents->count())
								@foreach($documents as $index => $doc)
									@php
										$fileUrl = $doc->document_file_url ?? '';
										$extension = strtolower($doc->document_type ?? '');
										$imageExtensions = ['jpg', 'jpeg', 'png'];
										$isImage = in_array($extension, $imageExtensions);
										$isPdf = $extension === 'pdf';
									@endphp

									<div class="mb-3 append-item">
										<div class="append-item-header d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['document'] ?? 'document' }} <span class="append-item-count"></span></h3>
											<div class="append-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>
										<div class="row p-5 pt-0">
											<div class="col-md-6">
												<div class="form-item mb-5">
													<input type="hidden" name="documents[{{ $index }}][id]" value="{{ $doc->id }}">
													<label class="form-label">{{ $getCurrentTranslation['document_name'] ?? 'document_name' }}:</label>
													<input type="text" class="form-control" name="documents[{{ $index }}][document_name]" placeholder="{{ $getCurrentTranslation['enter_document_name'] ?? 'enter_document_name' }}" value="{{ old("documents.$index.document_name") ?? $doc->document_name ?? '' }}"/>
												</div>
											</div>
											<div class="col-md-6">
												<div class="input-item-wrap mb-5">
													<label>{{ $getCurrentTranslation['file_label'] ?? 'file_label' }}:</label>
													@php
														$selected = $fileUrl ?? '';
														$isFileExist = !empty($selected);
													@endphp
													<div class="file-input-box">
														<input name="documents[{{ $index }}][document_file]" class="form-control image-input" type="file" max-size="5120" accept=".pdf,.png,.jpg,.jpeg" {{ empty($selected) ? '' : '' }} data-old="{{ $fileUrl ?? '' }}">
													</div>
													<div class="preview-image mt-2" style="{{ $isFileExist ? '' : 'display: none;' }}" data-old="{{ $fileUrl ?? '' }}">
														@if($isFileExist)
															@if($isImage)
																<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="{{ $fileUrl }}">
																	<img src="{{ $fileUrl }}" alt="Document" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;" old-selected="{{ $fileUrl }}">
																</div>
																<div class="pdf-preview" data-src="" style="display: none;">
																	<a href="javascript:void(0);" class="file-prev-thumb">
																		<i class="fas fa-file-pdf fa-3x text-danger"></i>
																	</a>
																</div>
															@elseif($isPdf)
																<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="" style="display: none;">
																	<img src="" alt="Document" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;">
																</div>
																<div class="append-prev mf-prev hover-effect m-0 pdf-preview" data-src="{{ $fileUrl }}" old-selected="{{ $fileUrl }}">
																	<a href="javascript:void(0);" class="file-prev-thumb">
																		<i class="fas fa-file-pdf fa-3x text-danger"></i>
																	</a>
																</div>
															@else
																<a href="{{ $fileUrl }}" target="_blank" class="file-prev-thumb">
																	<i class="fas fa-file-alt fa-3x"></i>
																</a>
															@endif
														@else
															<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="" style="display: none;">
																<img src="" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;">
															</div>
															<div class="pdf-preview" data-src="" style="display: none;">
																<a href="javascript:void(0);" class="file-prev-thumb">
																	<i class="fas fa-file-pdf fa-3x text-danger"></i>
																</a>
															</div>
														@endif
													</div>
												</div>
											</div>
											<div class="col-md-12">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['description'] ?? 'description' }}:</label>
													<textarea class="form-control" name="documents[{{ $index }}][description]" rows="2" placeholder="{{ $getCurrentTranslation['description_placeholder'] ?? 'description_placeholder' }}">{{ old("documents.$index.description") ?? $doc->description ?? '' }}</textarea>
												</div>
											</div>
										</div>
									</div>
								@endforeach
							@else
								<div class="append-item">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['document'] ?? 'document' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
									<div class="row p-5 pt-0">
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['document_name'] ?? 'document_name' }}:</label>
												<input type="text" class="form-control" name="documents[0][document_name]" placeholder="{{ $getCurrentTranslation['enter_document_name'] ?? 'enter_document_name' }}" value="{{ old('documents.0.document_name') ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-6">
											<div class="input-item-wrap mb-5">
												<label>{{ $getCurrentTranslation['file_label'] ?? 'file_label' }}:</label>
												<div class="file-input-box">
													<input name="documents[0][document_file]" class="form-control image-input" type="file" max-size="5120" accept=".pdf,.png,.jpg,.jpeg" data-old="">
												</div>
												<div class="preview-image mt-2" style="display: none;" data-old="">
													<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="" style="display: none;">
														<img src="" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;">
													</div>
													<div class="pdf-preview" data-src="" style="display: none;">
														<a href="javascript:void(0);" class="file-prev-thumb">
															<i class="fas fa-file-pdf fa-3x text-danger"></i>
														</a>
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-12">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['description'] ?? 'description' }}:</label>
												<textarea class="form-control" name="documents[0][description]" rows="2" placeholder="{{ $getCurrentTranslation['description_placeholder'] ?? 'description_placeholder' }}">{{ old('documents.0.description') ?? '' }}</textarea>
											</div>
										</div>
									</div>
								</div>
							@endif
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
										$selected = isset($editData) ? ($editData->default_language ?? '') : '';
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
										

										$selectedPermissions = isset($editData) ? ($editData->permissions ?? []) : [];
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
	$(document).ready(function(){

		checkUserType();
		$(document).on('change', '[name="user_type"]', function() {
			checkUserType();
		});

		function checkUserType(){
			var selectedType = $('[name="user_type"]').find('option:selected').val();

		    // Get server-side value (safe for JS)
		    var editUserType = {!! json_encode(isset($editData) ? ($editData->user_type ?? '') : '') !!};

		    if (!selectedType && editUserType) {
		        selectedType = editUserType;
		    }

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
			let userId = "{{ isset($editData) ? ($editData->id ?? '') : '' }}"; 
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
	});
	
</script>
@endpush