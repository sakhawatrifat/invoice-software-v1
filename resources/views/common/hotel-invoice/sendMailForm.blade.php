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
					@if(isset($listRoute) && !empty($listRoute))
						<li class="breadcrumb-item text-muted">
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['invoice_list'] ?? 'invoice_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['send_mail'] ?? 'send_mail' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(hasPermission('hotel.invoice.show') && isset($editData) && !empty($editData))
                    <a href="{{ route('hotel.invoice.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pager"></i>
                        {{ $getCurrentTranslation['details'] ?? 'details' }}
                    </a>
                @endif

                @if (hasPermission('hotel.invoice.edit'))
                    <a href="{{ route('hotel.invoice.edit', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i>
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

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<form class="" method="post" action="{{ route('hotel.invoice.mailSend', $editData->id) }}" enctype="multipart/form-data">
				@csrf
                @method('put')
				
                <div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['mail_informations'] ?? 'mail_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<label class="form-label">{{ $getCurrentTranslation['select_guest'] ?? 'select_guest' }}:</label>
								@if(isset($editData) && !empty($editData->guestInfo) && is_array($editData->guestInfo))
									@foreach($editData->guestInfo as $key => $item)
										<div class="d-flex align-items-center form-item mb-4 customer-item">
											<input type="hidden" name="guest[{{$key}}][phone]" value="{{ $item['phone'] ?? null }}">
											<input type="hidden" name="guest[{{$key}}][passport_number]" value="{{ $item['passport_number'] ?? null }}">
											<div class="customer-name-checkbox-wrap mb-2 pe-2">
												<div class="form-check">
													<label class="form-check-label user-select-none">
														<input type="checkbox"
															class="form-check-input group-checkbox customer-name-checkbox"
															name="guest[{{$key}}][name]"
															value="{{ $item['name'] ?? null }}"
															@if(!$loop->last) checked @endif>
														{{ $item['name'] ?? '' }}
													</label>
												</div>
											</div>

											<div class="col-md-6 mb-2">
												<input class="form-control"
													name="guest[{{$key}}][email]"
													value="{{ $item['email'] ?? '' }}">
											</div>
										</div>
									@endforeach

								@endif
							</div>

							<hr class="border-top opacity-100">

							{{-- <div class="col-md-12">
								<label class="form-label mb-0">{{ $getCurrentTranslation['send_mail_individual_msg'] ?? 'send_mail_individual_msg' }}:</label><br>
								<small class="d-block mb-2 text-warning">{{ $getCurrentTranslation['send_mail_individual_note'] ?? 'send_mail_individual_note' }}</small>
								<div class="d-flex align-items-center form-item mb-4">
									<div class="mb-2 pe-2">
										<div class="form-check">
											<label class="form-check-label user-select-none">
												<input type="checkbox" class="form-check-input group-checkbox" name="send_individually" value="1">
												{{ $getCurrentTranslation['send_individually_label'] ?? 'send_individually_label' }}
											</label>
										</div>
									</div>
								</div>
							</div> --}}

							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<div class="form-item mb-5 mail-content-wrapper">
									<label class="form-label mb-0">{{ $getCurrentTranslation['mail_content_label'] ?? 'mail_content_label' }}:</label>
									<br><small class="d-block mb-2">{{ $getCurrentTranslation['mail_content_note'] ?? 'mail_content_note' }}</small>
									<textarea class="form-control summernote" name="mail_content" rows="10">{{ old('mail_content') ?? $editData->mail_content ?? '' }}</textarea>
									@error('mail_content')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<div class="form-item mb-5 mail-content-wrapper">
									<label class="form-label mb-0">{{ $getCurrentTranslation['cc_emails'] ?? 'cc_emails' }}:</label>
									<br>
									<small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_cc'] ?? 'select_or_type_cc' }}</small>

									<select class="form-control select-2-mail" name="cc_emails[]" multiple="multiple">
										@foreach(Auth::user()->company_data->cc_emails ?? [] as $ccItem)
											<option value="{{ $ccItem }}" selected>{{ $ccItem }}</option>
										@endforeach
									</select>

									@error('cc_emails')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>


							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<div class="form-item mb-5 mail-content-wrapper">
									<label class="form-label mb-0">{{ $getCurrentTranslation['bcc_emails'] ?? 'bcc_emails' }}:</label>
									<br>
									<small class="d-block mb-2">{{ $getCurrentTranslation['select_or_type_bcc'] ?? 'select_or_type_bcc' }}</small>

									<select class="form-control select-2-mail" name="bcc_emails[]" multiple="multiple">
										@foreach(Auth::user()->company_data->bcc_emails ?? [] as $bccItem)
											<option value="{{ $bccItem }}" selected>{{ $bccItem }}</option>
										@endforeach
									</select>

									@error('bcc_emails')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>


							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<label class="form-label">{{ $getCurrentTranslation['select_attached_document_type'] ?? 'select_attached_document_type' }}:</label>
								
								<div class="d-flex align-items-center form-item mb-4">
									<div class="mb-2 pe-6">
										<div class="form-check">
											<label class="form-check-label user-select-none">
												<input type="checkbox" class="form-check-input group-checkbox" name="document_type_invoice" value="1" checked>
												{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}
											</label>
										</div>
									</div>
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="d-flex justify-content-end my-10">
					<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
						<span class="indicator-label">{{ $getCurrentTranslation['send_mail'] ?? 'send_mail' }}</span>
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
	$(document).ready(function () {
		let maxWidth = 0;

		$(".customer-name-checkbox-wrap").each(function () {
			// Temporarily set to auto so we measure natural width
			$(this).css("width", "auto");
			
			let labelWidth = $(this).find(".form-check-label").outerWidth(true);
			if (labelWidth > maxWidth) {
				maxWidth = labelWidth;
			}
		});

		// Apply the maximum width to all
		$(".customer-name-checkbox-wrap").width(maxWidth+40);
	});



	// Load Mail Content
	$(document).on('click', '.customer-name-checkbox', function (e) {
		$('.r-preloader').show();
		
		let $form = $(this).closest('form');
		let formData = new FormData($form[0]);

		['_method'].forEach(key => formData.delete(key));

		$.ajax({
			url: "{{ route('hotel.invoice.mailContentLoad', $editData->id) }}",
			type: 'POST',
			data: formData,
			processData: false, // important
			contentType: false, // important
			beforeSend: function () {
                $('.r-preloader').show();
				//console.log('Sending...');
			},
			success: function (response) {
                $('.r-preloader').hide();

				// Clear previous errors
				$('.error-message').remove();
				$('.is-invalid').removeClass('is-invalid');

				if (response.is_success === 0 && response.errors) {
					let firstField = null;

					Object.entries(response.errors).forEach(([field, messages]) => {
						// Convert dot notation to bracket notation
						const name = field
							.split('.')
							.map(part => /^\d+$/.test(part) ? `[${part}]` : part)
							.reduce((acc, part, index) => {
								if (index === 0) return part;
								return acc + (part.startsWith('[') ? part : `[${part}]`);
							}, '');

						const $input = $(`[name="${name}"]`);

						if ($input.length > 0) {
							// Option 1: checkbox inside the same parent container
   	 						$input.closest('.customer-item').find('.customer-name-checkbox').prop('checked', false);

							//$input.addClass('is-invalid');

							// Get just the last field name (e.g., "email")
							const fieldLabel = field.split('.').pop().replace(/_/g, ' ');

							// Replace default Laravel error message with a shorter one
							let message = messages[0]
								.replace(field, fieldLabel) // Replace full path with short name
								.replace(/The\s+.+?\s+field/, `The ${fieldLabel} field`); // General fallback

							const $error = $(`
								<div class="error-message text-danger" style="font-size: 0.875rem; margin-top: 0.25rem;">
									${message}
								</div>
							`);
							$input.after($error);

							if (!firstField) {
								firstField = $input;
							}
						}
					});

					if (firstField) {
						// $('html, body').animate({
						// 	scrollTop: firstField.offset().top - 100
						// }, 600);
						firstField.focus();
					} else {
						Swal.fire({
							icon: 'warning',
							title: getCurrentTranslation.validation_warning ?? 'validation_warning',
							text: getCurrentTranslation.some_filed_missing_or_invalid ?? 'some_filed_missing_or_invalid',
                            confirmButtonText: getCurrentTranslation.ok || 'ok',
						});
					}
				} else {
					if (response.is_success === 1) {
						let content = response.mail_content ?? '';
						const $mailContentElem = $('[name="mail_content"]');
						const mailContentElem = $mailContentElem[0];

						/* ===== CKEditor Handling ===== */
						if (typeof ckeditors !== 'undefined' && mailContentElem) {
							const existingEditor = ckeditors.get(mailContentElem);

							if ($('.ck-editor').length && existingEditor) {
								existingEditor.destroy()
									.then(() => {
										ckeditors.delete(mailContentElem);

										if (mailContentElem.tagName.toLowerCase() === 'textarea') {
											mailContentElem.value = content;
										} else {
											mailContentElem.innerHTML = content;
										}

										initializeCKEditors();
									})
									.catch(error => {
										console.error('CKEditor destroy error:', error);
									});
							} else {
								if (mailContentElem.tagName.toLowerCase() === 'textarea') {
									mailContentElem.value = content;
								} else {
									mailContentElem.innerHTML = content;
								}
								initializeCKEditors();
							}
						}

						/* ===== Summernote Handling ===== */
						if ($mailContentElem.length && $mailContentElem.next('.note-editor').length) {
							$mailContentElem.summernote('destroy');
						}

						$mailContentElem.val(content);
						initializeSummernote();

					} else {
						var $icon = response.icon;
						Swal.fire({
							icon: $icon,
							title: (getCurrentTranslation[$icon] || $icon).toUpperCase(),
							text: response.message,
							confirmButtonText: getCurrentTranslation.ok || 'ok',
							allowOutsideClick: true,
						});
					}

				}
			},
			error: function (xhr) {
                $('.r-preloader').hide();

				// Laravel CSRF mismatch status
                if (xhr.status === 419) { 
                    Swal.fire({
                        icon: 'error',
                        title: getCurrentTranslation.csrf_token_mismatch ?? 'csrf_token_mismatch',
                        text: getCurrentTranslation.csrf_token_mismatch_msg ?? 'csrf_token_mismatch_msg',
                        confirmButtonText: getCurrentTranslation.yes_reload_page || 'yes_reload_page'
                    }).then(() => {
                        location.reload(); // Reload after user confirms
                    });

                    return;
                }
				
				// Generic error fallback for 500s or connectivity issues
				Swal.fire({
					icon: 'error',
					title: getCurrentTranslation.server_error ?? 'server_error',
					text: getCurrentTranslation.something_went_wrong ?? 'something_went_wrong',
                    confirmButtonText: getCurrentTranslation.ok || 'ok'
				});
			}
		});
	});


	$(document).ready(function(){
		var $lastCheckbox = $('.customer-name-checkbox').last();
		$lastCheckbox.trigger('click').prop('checked', true);
	});


</script>
@endpush