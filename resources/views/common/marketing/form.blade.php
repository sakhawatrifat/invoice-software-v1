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
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{ $pageTitle ?? '' }}</h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'Dashboard' }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item">{{ $pageTitle ?? '' }}</li>
				</ul>
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<form id="marketing-form" method="post" action="{{ $submitRoute }}" enctype="multipart/form-data">
				@csrf
				@if($type === 'whatsapp')
				<div class="card rounded border mt-5 bg-white">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['content'] ?? 'Content' }}</h3>
					</div>
					<div class="card-body">
						<div class="form-item mb-0">
							<label class="form-label">{{ $getCurrentTranslation['content'] ?? 'Content' }}: <span class="text-danger">*</span></label>
							<textarea class="form-control" name="content" rows="10" placeholder="{{ $getCurrentTranslation['enter_content'] ?? 'Enter message content' }}" ip-required required>{{ old('content', '') }}</textarea>
							@error('content')
								<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
							@enderror
						</div>
					</div>
				</div>
				@else
				<div class="card rounded border mt-5 bg-white">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['message_content'] ?? 'Message Content' }}</h3>
					</div>
					<div class="card-body">
						<div class="form-item mb-5">
							<label class="form-label">{{ $getCurrentTranslation['email_subject'] ?? 'Email subject' }} / {{ $getCurrentTranslation['message_title'] ?? 'Message title' }}: <span class="text-danger">*</span></label>
							<input type="text" class="form-control" name="subject" placeholder="{{ $getCurrentTranslation['enter_subject_or_title'] ?? 'Enter subject or title for email and message' }}" value="{{ old('subject', '') }}" maxlength="255" ip-required required>
							@error('subject')
								<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
							@enderror
						</div>
						<div class="form-item mb-5 mail-content-wrapper">
							<label class="form-label mb-0">{{ $getCurrentTranslation['mail_content_label'] ?? 'Content' }}: <span class="text-danger">*</span></label>
							<textarea class="form-control summernote" name="content" rows="10" ip-required>{{ old('content', '') }}</textarea>
						</div>
					</div>
				</div>
				@endif

				<div class="card rounded border mt-5 bg-white">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['attachment'] ?? 'Attachment' }}</h3>
					</div>
					<div class="card-body">
						<div class="input-item-wrap mb-0">
							<label class="form-label">{{ $getCurrentTranslation['file_label'] ?? 'File' }} ({{ $getCurrentTranslation['allowed_file_types'] ?? 'jpg, jpeg, png, webp, pdf' }}):</label>
							<div class="file-input-box">
								<input name="attachment" class="form-control image-input" type="file" max-size="5120" accept=".pdf,.png,.jpg,.jpeg,.webp" data-old="">
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
				</div>

				<div class="card rounded border mt-5 bg-white">
					<div class="card-header d-flex align-items-center justify-content-between flex-wrap">
						<h3 class="card-title mb-0">{{ $getCurrentTranslation['select_recipients'] ?? 'Select Recipients' }} <span class="badge badge-light-primary fs-7 ms-2">{{ count($users) }}</span></h3>
						<label class="form-check form-check-custom form-check-solid form-check-inline mb-0">
							<input type="checkbox" class="form-check-input" id="marketing-check-all">
							<span class="ps-2 user-select-none form-check-label fw-semibold">{{ $getCurrentTranslation['check_all'] ?? 'Check all' }}</span>
						</label>
					</div>
					<div class="card-body p-4">
						<div class="row g-5">
							<div class="col-xl-4 col-lg-5">
								<div class="border border-gray-200 rounded bg-light-primary p-4 h-100">
									<h5 class="fw-bold mb-4">{{ $getCurrentTranslation['filter'] ?? 'Filter' }}</h5>
									<div class="mb-5">
										<label class="form-label fw-semibold mb-3">{{ $getCurrentTranslation['gender'] ?? 'Gender' }}</label>
										<div class="d-flex flex-column gap-3">
											<label class="form-check form-check-custom form-check-solid">
												<input class="form-check-input marketing-filter-gender" type="checkbox" value="male">
												<span class="form-check-label ms-2">{{ $getCurrentTranslation['male'] ?? 'Male' }}</span>
											</label>
											<label class="form-check form-check-custom form-check-solid">
												<input class="form-check-input marketing-filter-gender" type="checkbox" value="female">
												<span class="form-check-label ms-2">{{ $getCurrentTranslation['female'] ?? 'Female' }}</span>
											</label>
											<label class="form-check form-check-custom form-check-solid">
												<input class="form-check-input marketing-filter-gender" type="checkbox" value="other">
												<span class="form-check-label ms-2">{{ $getCurrentTranslation['other'] ?? 'Other' }}</span>
											</label>
										</div>
									</div>
									<div class="mb-5">
										<label class="form-label fw-semibold mb-3">{{ $getCurrentTranslation['age'] ?? 'Age' }}</label>
										<input type="text" id="marketing-age-range" value="">
										<div class="form-text mt-2">
											<span id="marketing-age-range-label">0 - 100</span>
										</div>
									</div>
									<button type="button" id="marketing-filter-reset" class="btn btn-secondary btn-sm w-100">{{ $getCurrentTranslation['reset_filter'] ?? 'Reset filters' }}</button>
								</div>
							</div>
							<div class="col-xl-8 col-lg-7">
								<div class="border border-gray-200 rounded overflow-hidden">
									<div class="p-4 border-bottom border-gray-200 bg-light-primary">
										<input type="text" id="marketing-recipient-search" class="form-control form-control-solid bg-secondary" placeholder="{{ $getCurrentTranslation['search_recipients'] ?? 'Search by name, email, phone, type, gender, date of birth, nationality...' }}" autocomplete="off">
										<div id="marketing-search-hint" class="form-text mt-1 small text-muted" style="display: none;" data-no-match="{{ $getCurrentTranslation['no_matching_recipients'] ?? 'No matching recipients' }}" data-one-match="{{ $getCurrentTranslation['one_matching_recipient'] ?? '1 matching recipient' }}" data-matches="{{ $getCurrentTranslation['matching_recipients'] ?? 'matching recipients' }}"></div>
									</div>
									<div class="marketing-user-list overflow-auto" style="max-height: 460px;">
										@forelse($users as $user)
											@php
												$paxType = $user->pax_type ?? null;
												$gender = $user->gender ?? null;
												$genderLower = strtolower(trim((string) $gender));
												$dob = $user->date_of_birth ?? null;
												$nationality = $user->nationality ?? null;
												$age = null;
												$ageStr = null;
												if (!empty($dob)) {
													try {
														$age = \Carbon\Carbon::parse($dob)->age;
														$ageStr = $age . 'Y';
													} catch (\Exception $e) {}
												}
												$searchParts = array_filter([
													$user->name ?? '',
													$user->email ?? '',
													$user->phone ?? '',
													$paxType,
													$gender,
													$dob,
													$nationality,
													$ageStr ? 'Age ' . $ageStr : null,
												]);
												$dataSearch = strtolower(implode(' ', array_map('trim', $searchParts)));
											@endphp
											<div class="d-flex align-items-center p-4 border-bottom border-gray-200 hover-bg-light-primary marketing-recipient-row cursor-pointer" data-search="{{ $dataSearch }}" data-original-index="{{ $loop->index }}" data-gender="{{ $genderLower }}" data-age="{{ $age !== null ? $age : '' }}">
												<div class="form-check form-check-custom form-check-solid me-4">
													<input type="checkbox" class="form-check-input marketing-user-check" name="user_ids[]" value="{{ $user->id }}" id="user-{{ $user->id }}">
													<label class="form-check-label" for="user-{{ $user->id }}"></label>
												</div>
												<div class="symbol symbol-45px me-4">
													@if($user->image)
														<img src="{{ getUploadedUrl($user->image) }}" alt="{{ $user->name }}">
													@else
														<span class="symbol-label bg-primary text-inverse-primary fw-bold">{{ strtoupper(substr($user->name ?? '?', 0, 1)) }}</span>
													@endif
												</div>
												<div class="flex-grow-1 min-w-0">
													<span class="fw-bold text-gray-800 d-block">{{ $user->name ?? '-' }}</span>
													<div class="text-muted fs-7">
														@if(!empty(trim((string)($user->email ?? ''))))
															<span class="d-block">{{ $user->email }}</span>
														@endif
														@if(!empty(trim((string)($user->phone ?? ''))))
															<span class="d-block">{{ $user->phone }}</span>
														@endif
														@if(empty(trim((string)($user->email ?? ''))) && empty(trim((string)($user->phone ?? ''))))
															<span class="d-block">-</span>
														@endif
													</div>
													@if(!empty(trim((string)$paxType)) || !empty(trim((string)$gender)) || $ageStr !== null || !empty(trim((string)$nationality)))
														<div class="text-muted fs-8 mt-0">
															@if(!empty(trim((string)$paxType)))
																<span>{{ $paxType }}</span>
															@endif
															@if(!empty(trim((string)$gender)))
																@if(!empty(trim((string)$paxType)))<span class="mx-1">•</span>@endif
																<span>{{ $gender }}</span>
															@endif
															@if($ageStr !== null)
																@if(!empty(trim((string)$paxType)) || !empty(trim((string)$gender)))<span class="mx-1">•</span>@endif
																<span>(Age: {{ $ageStr }})</span>
															@endif
															@if(!empty(trim((string)$nationality)))
																@if(!empty(trim((string)$paxType)) || !empty(trim((string)$gender)) || $ageStr !== null)<span class="mx-1">•</span>@endif
																<span>{{ $nationality }}</span>
															@endif
														</div>
													@endif
													@if(!empty($user->employee_uid))
														<span class="badge badge-light-primary ms-1">{{ $user->employee_uid }}</span>
													@endif
												</div>
											</div>
										@empty
											<div class="p-5 text-center text-muted">
												{{ $getCurrentTranslation['no_users_available'] ?? 'No users available' }}
											</div>
										@endforelse
										<div class="p-5 text-center text-muted marketing-no-results" style="display: none;">
											{{ $getCurrentTranslation['no_matching_recipients'] ?? 'No matching recipients' }}
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="d-flex justify-content-end my-10">
					<button type="submit" class="btn btn-primary form-submit-btn ajax-submit">
						@if($type === 'email')
							<span class="indicator-label">{{ $getCurrentTranslation['send_via_email'] ?? 'Send via Email' }}</span>
						@else
							<span class="indicator-label">{{ $getCurrentTranslation['send_via_whatsapp'] ?? 'Send via WhatsApp' }}</span>
						@endif
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.formScripts')
<link rel="stylesheet" href="{{ asset('assets/plugins/custom/ion-rangeslider/ion.rangeSlider.min.css') }}"/>
<script src="{{ asset('assets/plugins/custom/ion-rangeslider/ion.rangeSlider.min.js') }}"></script>
<style>
	.marketing-recipient-row.marketing-hidden {
		display: none !important;
	}
</style>
<script>
	(function($) {
		var ageFilter = {
			min: 0,
			max: 100,
			from: 0,
			to: 100
		};

		function getForm() {
			return $('#marketing-form');
		}

		function updateCheckAllState() {
			var $form = getForm();
			if (!$form.length) return;
			var $visibleRows = $form.find('.marketing-recipient-row:visible');
			var visibleCount = $visibleRows.length;
			var checkedCount = $visibleRows.find('.marketing-user-check:checked').length;
			$form.find('#marketing-check-all').prop('checked', visibleCount > 0 && visibleCount === checkedCount);
		}

		function clearAllSelections() {
			var $form = getForm();
			if (!$form.length) return;
			$form.find('.marketing-user-check').prop('checked', false);
			$form.find('#marketing-check-all').prop('checked', false);
		}

		function updateAgeLabel() {
			$('#marketing-age-range-label').text(ageFilter.from + ' - ' + ageFilter.to);
		}

		function initAgeRangeSlider() {
			var $slider = $('#marketing-age-range');
			if (!$slider.length || !$.fn.ionRangeSlider) return;

			$slider.ionRangeSlider({
				type: 'double',
				min: ageFilter.min,
				max: ageFilter.max,
				from: ageFilter.from,
				to: ageFilter.to,
				grid: true,
				skin: 'round',
				onStart: function(data) {
					ageFilter.from = data.from;
					ageFilter.to = data.to;
					updateAgeLabel();
				},
				onChange: function(data) {
					ageFilter.from = data.from;
					ageFilter.to = data.to;
					updateAgeLabel();
					clearAllSelections();
					applyMarketingFilters();
				}
			});
		}

		function applyMarketingFilters() {
			var $form = getForm();
			if (!$form.length) return;

			var searchTerm = ($form.find('#marketing-recipient-search').val() || '').toLowerCase().replace(/\s+/g, ' ').trim();
			var isAgeFilterActive = ageFilter.from !== ageFilter.min || ageFilter.to !== ageFilter.max;
			var selectedGenders = [];
			$form.find('.marketing-filter-gender:checked').each(function() {
				selectedGenders.push(String($(this).val() || '').toLowerCase());
			});
			var $rows = $form.find('.marketing-recipient-row');
			var visibleCount = 0;
			$rows.each(function() {
				var $row = $(this);
				var searchText = String($row.attr('data-search') || '').toLowerCase();
				var rowGender = String($row.attr('data-gender') || '').toLowerCase();
				var rowAge = parseInt($row.attr('data-age'), 10);

				var searchMatch = !searchTerm || searchText.indexOf(searchTerm) !== -1;
				var genderMatch = !selectedGenders.length || selectedGenders.indexOf(rowGender) !== -1;
				var ageMatch = !isAgeFilterActive || (!isNaN(rowAge) && rowAge >= ageFilter.from && rowAge <= ageFilter.to);
				var isVisible = searchMatch && genderMatch && ageMatch;

				$row.toggleClass('marketing-hidden', !isVisible);
				if (isVisible) visibleCount++;
			});

			$form.find('.marketing-no-results').toggle(visibleCount === 0 && $rows.length > 0);

			var $hint = $form.find('#marketing-search-hint');
			if ($rows.length === 0) {
				$hint.hide();
			} else if (searchTerm || selectedGenders.length || isAgeFilterActive) {
				var noMatch = $hint.data('no-match') || 'No matching recipients';
				var oneMatch = $hint.data('one-match') || '1 matching recipient';
				var matches = $hint.data('matches') || 'matching recipients';
				$hint.text(visibleCount === 0 ? noMatch : (visibleCount === 1 ? oneMatch : visibleCount + ' ' + matches)).show();
			} else {
				$hint.hide();
			}

			updateCheckAllState();
		}

		$(document).on('input keyup', '#marketing-recipient-search', function() {
			clearAllSelections();
			applyMarketingFilters();
		});
		$(document).on('change', '.marketing-filter-gender', function() {
			clearAllSelections();
			applyMarketingFilters();
		});
		$(document).on('click', '#marketing-filter-reset', function() {
			var $form = getForm();
			$form.find('#marketing-recipient-search').val('');
			$form.find('.marketing-filter-gender').prop('checked', false);

			var ageSlider = $('#marketing-age-range').data('ionRangeSlider');
			if (ageSlider) {
				ageSlider.update({ from: ageFilter.min, to: ageFilter.max });
			}
			ageFilter.from = ageFilter.min;
			ageFilter.to = ageFilter.max;
			updateAgeLabel();

			clearAllSelections();
			applyMarketingFilters();
		});

		$(document).on('click', '.marketing-recipient-row', function(e) {
			if ($(e.target).closest('.marketing-user-check').length) return;
			e.preventDefault();
			var $cb = $(this).find('.marketing-user-check').first();
			$cb.prop('checked', !$cb.prop('checked'));
			updateCheckAllState();
		});

		$(document).on('change', '#marketing-check-all', function() {
			var checked = $(this).is(':checked');
			$('#marketing-form .marketing-recipient-row:visible .marketing-user-check').prop('checked', checked);
		});

		$(document).on('change', '.marketing-user-check', updateCheckAllState);

		$(document).on('submit', '#marketing-form', function(e) {
			var checkedCount = $(this).find('.marketing-user-check:checked').length;
			if (checkedCount === 0) {
				e.preventDefault();
				var msg = '{{ $getCurrentTranslation["at_least_one_recipient_required"] ?? "At least one recipient should be selected." }}';
				if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: msg });
				else alert(msg);
				return false;
			}
		});

		$(function() {
			initAgeRangeSlider();
			applyMarketingFilters();
		});
	})(jQuery);
</script>
@endpush
