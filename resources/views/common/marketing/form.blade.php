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
					<div class="card-body p-0">
						<div class="p-4 border-bottom border-gray-200 bg-light-primary">
							<input type="text" id="marketing-recipient-search" class="form-control form-control-solid bg-secondary" placeholder="{{ $getCurrentTranslation['search_recipients'] ?? 'Search by name, email, phone, type, gender, date of birth, nationality...' }}" autocomplete="off">
							<div id="marketing-search-hint" class="form-text mt-1 small text-muted" style="display: none;" data-no-match="{{ $getCurrentTranslation['no_matching_recipients'] ?? 'No matching recipients' }}" data-one-match="{{ $getCurrentTranslation['one_matching_recipient'] ?? '1 matching recipient' }}" data-matches="{{ $getCurrentTranslation['matching_recipients'] ?? 'matching recipients' }}"></div>
						</div>
						<div class="marketing-user-list overflow-auto" style="max-height: 400px;">
							@forelse($users as $user)
								@php
									$paxType = $user->pax_type ?? null;
									$gender = $user->gender ?? null;
									$dob = $user->date_of_birth ?? null;
									$nationality = $user->nationality ?? null;
									$ageStr = null;
									if (!empty($dob)) {
										try { $ageStr = \Carbon\Carbon::parse($dob)->age . 'Y'; } catch (\Exception $e) {}
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
								<div class="d-flex align-items-center p-4 border-bottom border-gray-200 hover-bg-light-primary marketing-recipient-row cursor-pointer" data-search="{{ $dataSearch }}" data-original-index="{{ $loop->index }}">
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
<script>
	(function() {
		// Event delegation: work with form when it exists (handles dynamic content / load order)
		function getFormFromSearchInput(input) {
			if (!input || input.id !== 'marketing-recipient-search') return null;
			var form = input.closest ? input.closest('form') : null;
			if (!form) form = document.getElementById('marketing-form');
			return form && form.id === 'marketing-form' ? form : null;
		}

		function updateCheckAllState(form) {
			if (!form) return;
			var visibleRows = form.querySelectorAll('.marketing-recipient-row');
			var visibleCount = 0, visibleChecked = 0;
			for (var i = 0; i < visibleRows.length; i++) {
				var row = visibleRows[i];
				if (row.style.display !== 'none') {
					visibleCount++;
					var cb = row.querySelector('.marketing-user-check');
					if (cb && cb.checked) visibleChecked++;
				}
			}
			var checkAll = form.querySelector('#marketing-check-all');
			if (checkAll) checkAll.checked = visibleCount > 0 && visibleCount === visibleChecked;
		}

		function doFilter(searchInput) {
			var form = getFormFromSearchInput(searchInput);
			if (!form) return;
			var term = (searchInput.value || '').toLowerCase().replace(/\s+/g, ' ').trim();
			var listEl = form.querySelector('.marketing-user-list');
			var rows = form.querySelectorAll('.marketing-recipient-row');
			var visibleCount = 0;
			// Build list of all rows with match flag and score (for ordering)
			var list = [];
			for (var i = 0; i < rows.length; i++) {
				var row = rows[i];
				var searchAttr = row.getAttribute('data-search') || '';
				var searchText = (searchAttr && searchAttr.toLowerCase) ? searchAttr.toLowerCase() : '';
				var match = !term || (searchText && searchText.indexOf(term) !== -1);
				if (match) {
					row.style.removeProperty('display');
				} else {
					row.style.display = 'none';
				}
				if (match) visibleCount++;
				var originalIndex = parseInt(row.getAttribute('data-original-index'), 10);
				if (isNaN(originalIndex)) originalIndex = i;
				var pos = term && searchText ? searchText.indexOf(term) : 0;
				var score = term ? (100000 - Math.min(pos, 99999)) * 1000 + (1000 - originalIndex) : originalIndex;
				list.push({ row: row, score: score, match: match, originalIndex: originalIndex });
			}
			// Sort: when searching, matching rows first (by relevance), then non-matching (by original index). When empty, original order.
			if (term) {
				list.sort(function(a, b) {
					if (a.match && !b.match) return -1;
					if (!a.match && b.match) return 1;
					if (a.match && b.match) return b.score - a.score;
					return a.originalIndex - b.originalIndex;
				});
			} else {
				list.sort(function(a, b) { return a.originalIndex - b.originalIndex; });
			}
			// Reorder DOM: matching rows first (visible at top), then non-matching, then no-results
			var noResults = form.querySelector('.marketing-no-results');
			if (listEl) {
				for (var j = 0; j < list.length; j++) {
					listEl.appendChild(list[j].row);
				}
				if (noResults && noResults.parentNode === listEl) {
					listEl.appendChild(noResults);
				}
			}
			if (noResults) noResults.style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
			updateCheckAllState(form);
			// Smooth scroll list to top on search so matched results are in view
			if (listEl && term) {
				listEl.scrollTo({ top: 0, behavior: 'smooth' });
			}
			// Show search suggestion / hint
			var hint = form.querySelector('#marketing-search-hint');
			if (hint) {
				if (rows.length === 0) {
					hint.style.display = 'none';
				} else if (term) {
					hint.style.display = 'block';
					var noMatch = hint.getAttribute('data-no-match') || 'No matching recipients';
					var oneMatch = hint.getAttribute('data-one-match') || '1 matching recipient';
					var matches = hint.getAttribute('data-matches') || 'matching recipients';
					hint.textContent = visibleCount === 0 ? noMatch : (visibleCount === 1 ? oneMatch : visibleCount + ' ' + matches);
				} else {
					hint.style.display = 'none';
				}
			}
		}

		// Search: delegate to document so it fires whenever user types (input exists)
		function onSearchInput(e) {
			var input = e.target;
			if (input.id === 'marketing-recipient-search') doFilter(input);
		}
		document.addEventListener('input', onSearchInput, true);
		document.addEventListener('keyup', onSearchInput, true);

		// Full row click toggles checkbox (except when clicking the checkbox itself)
		document.addEventListener('click', function(e) {
			var row = e.target.closest && e.target.closest('.marketing-recipient-row');
			if (!row) return;
			if (e.target.closest('.marketing-user-check') || e.target.classList.contains('marketing-user-check')) return;
			e.preventDefault();
			var cb = row.querySelector('.marketing-user-check');
			if (cb) {
				cb.checked = !cb.checked;
				var form = document.getElementById('marketing-form');
				if (form) updateCheckAllState(form);
			}
		}, true);

		// Check-all and user-check: delegate from document
		document.addEventListener('change', function(e) {
			var form = document.getElementById('marketing-form');
			if (!form) return;
			if (e.target.id === 'marketing-check-all') {
				var visible = form.querySelectorAll('.marketing-recipient-row');
				for (var i = 0; i < visible.length; i++) {
					if (visible[i].style.display !== 'none') {
						var cb = visible[i].querySelector('.marketing-user-check');
						if (cb) cb.checked = e.target.checked;
					}
				}
				return;
			}
			if (e.target.classList && e.target.classList.contains('marketing-user-check')) {
				updateCheckAllState(form);
			}
		}, true);

		// Form submit validation
		document.addEventListener('submit', function(e) {
			if (e.target.id !== 'marketing-form') return;
			var checked = e.target.querySelectorAll('.marketing-user-check:checked');
			if (checked.length === 0) {
				e.preventDefault();
				var msg = '{{ $getCurrentTranslation["at_least_one_recipient_required"] ?? "At least one recipient should be selected." }}';
				if (typeof Swal !== 'undefined') Swal.fire({ icon: 'warning', title: msg });
				else alert(msg);
				return false;
			}
		}, true);

		// Run filter once on load; show suggestion when user focuses search
		function onFocus() { doFilter(this); }
		function initWhenReady() {
			var input = document.getElementById('marketing-recipient-search');
			if (input && getFormFromSearchInput(input)) {
				doFilter(input);
				if (!input._marketingSearchBound) {
					input._marketingSearchBound = true;
					input.addEventListener('focus', onFocus);
				}
			}
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initWhenReady);
		} else {
			initWhenReady();
		}
		setTimeout(initWhenReady, 500);
	})();
</script>
@endpush
