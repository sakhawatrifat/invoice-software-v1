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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['expense_list'] ?? 'expense_list' }}</a> &nbsp; - 
						</li>
					@endif
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_expense'] ?? 'edit_expense' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_expense'] ?? 'create_expense' }}</li>
					@endif
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($editData) && !empty($editData))
					<a href="{{ route('admin.expense.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
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
			@php
				$formAction = isset($saveRoute) && !empty($saveRoute) ? $saveRoute : route('admin.expense.store');
			@endphp
			<form class="" method="post" action="{{ $formAction }}" enctype="multipart/form-data" id="expense-form">
				@csrf
				@if(isset($editData) && !empty($editData))
					@method('put')
				@endif

				<div class="col-md-10 col-lg-8 m-auto">
					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							@if(isset($editData))
								<h3 class="card-title">{{ $getCurrentTranslation['edit_expense'] ?? 'edit_expense' }}</h3>
							@else
								<h3 class="card-title">{{ $getCurrentTranslation['create_expense'] ?? 'create_expense' }}</h3>
							@endif
							<div class="card-toolbar"></div>
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['expense_category'] ?? 'expense_category' }}: <span class="text-danger">*</span></label>
										@php
											$selectedCategory = old('expense_category_id') ?? (isset($editData) ? $editData->expense_category_id : '');
										@endphp
										<select name="expense_category_id" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_expense_category'] ?? 'select_expense_category' }}" ip-required>
											<option value="">----</option>
											@foreach($categories as $category)
												<option value="{{ $category->id }}" {{ (string)$category->id === (string)$selectedCategory ? 'selected' : '' }}>
													{{ $category->name }}
												</option>
											@endforeach
										</select>
										@error('expense_category_id')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['for'] ?? 'for' }}:</label>
										@php
											$selectedUser = old('for_user_id') ?? (isset($editData) ? $editData->for_user_id : '');
											// Convert empty/null to '0' for Select2 compatibility
											if($selectedUser === '' || $selectedUser === null){
												$selectedUser = '0';
											}
										@endphp
										<select name="for_user_id" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_user'] ?? 'select_user' }}">
											<option value="0" {{ (string)$selectedUser === '0' ? 'selected' : '' }}>{{ $getCurrentTranslation['none'] ?? 'None' }}</option>
											@foreach($users as $user)
												<option value="{{ $user->id }}" {{ (string)$user->id === (string)$selectedUser ? 'selected' : '' }}>
													{{ $user->name }}
													({{ $user->designation?->name ?? 'N/A' }})
													@if($user->is_staff == 0)
														- {{ $getCurrentTranslation['non_staff'] ?? 'Non Staff' }}
													@endif
												</option>
											@endforeach
										</select>
										@error('for_user_id')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['title'] ?? 'title' }}: <span class="text-danger">*</span></label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['enter_title'] ?? 'enter_title' }}" name="title" ip-required value="{{ old('title') ?? (isset($editData) ? $editData->title : '') }}"/>
										@error('title')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['description'] ?? 'description' }}:</label>
										<textarea class="form-control" placeholder="{{ $getCurrentTranslation['description_placeholder'] ?? 'description_placeholder' }}" name="description" rows="3">{{ old('description') ?? (isset($editData) ? $editData->description : '') }}</textarea>
										@error('description')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['amount'] ?? 'amount' }} ({{Auth::user()->company_data->currency->short_name ?? ''}}): <span class="text-danger">*</span></label>
										<input type="text" class="form-control number-validate" placeholder="{{ $getCurrentTranslation['enter_amount'] ?? 'enter_amount' }}" name="amount" ip-required value="{{ old('amount') ?? (isset($editData) ? $editData->amount : '') }}"/>
										@error('amount')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['expense_date'] ?? 'expense_date' }}: <span class="text-danger">*</span></label>
										<input type="text" placeholder="{{ $getCurrentTranslation['date_placeholder'] ?? 'date_placeholder' }}" class="form-control flatpickr-input date" name="expense_date" ip-required value="{{ old('expense_date') ?? (isset($editData) && $editData->expense_date ? \Carbon\Carbon::parse($editData->expense_date)->format('Y-m-d') : '') }}"/>
										@error('expense_date')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['payment_method'] ?? 'payment_method' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['enter_payment_method'] ?? 'enter_payment_method' }}" name="payment_method" value="{{ old('payment_method') ?? (isset($editData) ? $editData->payment_method : '') }}"/>
										@error('payment_method')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['reference_number'] ?? 'reference_number' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['enter_reference_number'] ?? 'enter_reference_number' }}" name="reference_number" value="{{ old('reference_number') ?? (isset($editData) ? $editData->reference_number : '') }}"/>
										@error('reference_number')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['notes'] ?? 'notes' }}:</label>
										<textarea class="form-control" placeholder="{{ $getCurrentTranslation['enter_notes'] ?? 'enter_notes' }}" name="notes" rows="3">{{ old('notes') ?? (isset($editData) ? $editData->notes : '') }}</textarea>
										@error('notes')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-12">
									<div class="mb-5">
										@php
											$options = [
												'Unpaid' => 'Unpaid',
												'Paid' => 'Paid',
											];
											$selected = old('payment_status') ?? (isset($editData) ? $editData->payment_status : 'Unpaid');
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['payment_status'] ?? 'payment_status' }}: <span class="text-danger">*</span></label>
										<select name="payment_status" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" ip-required>
											<option value="">----</option>
											@foreach($options as $value => $label)
												<option value="{{ $value }}" {{ $value === $selected ? 'selected' : '' }}>
													{{ $label }}
												</option>
											@endforeach
										</select>
										@error('payment_status')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="card rounded border mt-5 bg-white append-item-container">
						<div class="card-header">
							<h3 class="card-title">{{ $getCurrentTranslation['expense_documents'] ?? 'expense_documents' }}</h3>
							<div class="card-toolbar">
								<button type="button" class="btn btn-sm btn-success append-item-add-btn">
									<i class="fa-solid fa-plus"></i>
								</button>
							</div>
						</div>
						<div class="card-body append-item-wrapper">
							@php
								$documents = $expenseDocuments ?? collect();
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
	$(document).ready(function() {
		// Ensure form action is set correctly
		var $form = $('#expense-form');
		var currentAction = $form.attr('action');
		var expectedAction = '{{ route('admin.expense.store') }}';
		
		// If action is empty or points to create route, set it to store route
		if (!currentAction || currentAction.includes('/expense/create')) {
			$form.attr('action', expectedAction);
		}
	});
</script>
@endpush
