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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['invoice_list'] ?? 'invoice_list' }}</a> &nbsp; - 
						</li>
					@endif
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_invoice'] ?? 'edit_invoice' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_invoice'] ?? 'create_invoice' }}</li>
					@endif
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($editData) && !empty($editData))
					<a href="{{ route('hotel.invoice.downloadPdf', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['download'] ?? 'download' }}</a>
				@endif

				@if(hasPermission('hotel.invoice.show') && isset($editData) && !empty($editData))
                    <a href="{{ route('hotel.invoice.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
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

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">
							{{ $getCurrentTranslation['invoice_informations'] ?? 'invoice_informations' }}
						</h3>
						<div class="card-toolbar">
						</div>
					</div>
					{{-- <div class="card-header text-center p-3 w-100" style="min-height: auto;">
						<b class="d-block w-100 text-danger">{{ $getCurrentTranslation['hotel_invoice_form_note'] ?? 'hotel_invoice_form_note' }}</b>
					</div> --}}
					<div class="card-body step-form">
						<div class="tab">
							<div class="row">
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['pin_number_label'] ?? 'pin_number_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['pin_number_placeholder'] ?? 'pin_number_placeholder' }}" name="pin_number" s-ip-required value="{{ $editData->pin_number ?? '' }}"/>
										@error('pin_number')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['booking_number_label'] ?? 'booking_number_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['booking_number_placeholder'] ?? 'booking_number_placeholder' }}" name="booking_number" s-ip-required value="{{ $editData->booking_number ?? '' }}"/>
										@error('booking_number')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
							</div>
						</div>

						<div class="tab" style="display: none">
							<div class="row">
								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['hotel_image'] ?? 'hotel_image' }}:</label>
										@php
											$isFileExist = false;
											if (isset($editData) && !empty($editData->hotel_image)) {
												if (!empty($editData->hotel_image_url)) {
													$isFileExist = true;
												}
											}
										@endphp

										<input type="file" accept=".heic,.png,.jpg,.jpeg" class="form-control" name="hotel_image" @if(empty(old('hotel_image')) && empty($editData->hotel_image)) s-ip-required @endif {{ $isFileExist ? '' : 's-ip-required' }}/>

										@if($isFileExist)
											<div class="mt-3"><img src="{{ $editData->hotel_image_url }}" alt="Hotel Image" style="max-height:100px; max-width:100%; object-fit:contain;" /></div>
										@endif

										@error('hotel_image')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<div class="col-md-6">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['hotel_name_label'] ?? 'hotel_name_label' }}:</label>
										<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['hotel_name_placeholder'] ?? 'hotel_name_placeholder' }}" name="hotel_name" s-ip-required value="{{ $editData->hotel_name ?? '' }}"/>
										@error('hotel_name')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
							</div>
						</div>

						<div class="tab" style="display: none">
							<div class="row">
								<div class="col-md-12">
									<div class="form-item mb-5">
										<label class="form-label">{{ $getCurrentTranslation['hotel_address_label'] ?? 'hotel_address_label' }}:</label>
										<textarea class="form-control" placeholder="{{ $getCurrentTranslation['hotel_address_placeholder'] ?? 'hotel_address_placeholder' }}" name="hotel_address" s-ip-required rows="4">{{ $editData->hotel_address ?? '' }}</textarea>
										@error('hotel_address')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>
							</div>
						</div>

						<!-- Navigation buttons -->
						<div class="mt-4" style="overflow:auto;">
							<div style="float:right;">
								<button class="btn btn-secondary" type="button" id="prevBtn" onclick="nextPrev(-1)">Previous</button>
								<button class="btn btn-primary" type="button" id="nextBtn" onclick="nextPrev(1)">Next</button>
								<button class="btn btn-primary " type="button" id="submitBtn" style="display:none;">
									@if(isset($editData)) {{ $getCurrentTranslation['update_data'] ?? 'Update Data' }} 
									@else {{ $getCurrentTranslation['save_data'] ?? 'Save Data' }} 
									@endif
								</button>
							</div>
						</div>

						<!-- Step indicators -->
						<div id="stepIndicators" style="display: none; text-align:center; margin-top:40px;"></div>
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
<script type="text/javascript">
	document.addEventListener("DOMContentLoaded", function () {
		const tabs = document.querySelectorAll(".card-body .tab");
		const stepContainer = document.getElementById("stepIndicators");

		if (stepContainer) {
			stepContainer.innerHTML = ""; // clear existing

			tabs.forEach(() => {
				const step = document.createElement("span");
				step.className = "step";
				stepContainer.appendChild(step);
			});
		}

		// Start with first tab
		currentTab = 0;
		showTab(currentTab);

		// Attach click event to submit button
		const submitBtn = document.getElementById("submitBtn");
		if(submitBtn) {
			submitBtn.addEventListener("click", function(e){
				// Validate last step
				const lastTab = document.getElementsByClassName("tab")[currentTab];
				const requiredFields = lastTab.querySelectorAll("[s-ip-required]");
				let valid = true;

				requiredFields.forEach(field => {
					if (!isFieldValid(field)) {
						showValidationMessage(field);
						valid = false;
					}
					attachValidationLive(field);
				});

				if(valid) {
					// Validation passed → allow submission
					submitBtn.classList.add("form-submit-btn", "ajax-submit");
					submitBtn.type = "submit";
				} else {
					// Validation failed → prevent submission
					e.preventDefault();

					// Remove classes and type
					submitBtn.classList.remove("form-submit-btn", "ajax-submit");
					submitBtn.type = "button"; // reset to button
				}
			});
		}

	});

	let currentTab = 0; // Global current tab index

	function showTab(n) {
		const x = document.getElementsByClassName("tab");

		// Hide all tabs
		for (let i = 0; i < x.length; i++) {
			x[i].style.display = "none";
		}

		// Show current tab
		x[n].style.display = "block";

		// Prev button
		document.getElementById("prevBtn").style.display = (n === 0) ? "none" : "inline";

		// Next button
		const nextBtn = document.getElementById("nextBtn");
		const submitBtn = document.getElementById("submitBtn"); // separate ajax submit button

		if (n === (x.length - 1)) {
			// Last step → hide Next, show Submit
			nextBtn.style.display = "none";
			submitBtn.style.display = "inline";
			// Reset type in case user navigates back
			submitBtn.type = "button";
			submitBtn.classList.remove("form-submit-btn", "ajax-submit");
		} else {
			// Middle steps → show Next, hide Submit
			nextBtn.style.display = "inline";
			nextBtn.innerHTML = "Next";
			nextBtn.type = "button";  
			submitBtn.style.display = "none";
		}

		// Step indicator
		fixStepIndicator(n);
	}

	function nextPrev(n) {
		const x = document.getElementsByClassName("tab");

		// ✅ Step 1: Run validation first before hiding tab
		if (n === 1 && !validateForm()) {
			return false; // stop if invalid
		}

		// Step 2: Hide current tab
		x[currentTab].style.display = "none";

		// Step 3: Move index
		currentTab = currentTab + n;

		// Step 4: Stop if beyond last step (form auto-submits by button)
		if (currentTab >= x.length) {
			return false;
		}

		// Step 5: Show next/previous tab
		showTab(currentTab);
	}

	function fixStepIndicator(n) {
		const x = document.getElementsByClassName("step");
		for (let i = 0; i < x.length; i++) {
			x[i].className = x[i].className.replace(" active", "");
		}
		if (x[n]) {
			x[n].className += " active";
		}
	}

	function isFieldValid(field) {
		const tag = field.tagName.toLowerCase();
		if (tag === "input") {
			const type = field.type.toLowerCase();
			if (["text", "number", "email", "password"].includes(type)) {
				return field.value.trim() !== "";
			} else if (type === "radio" || type === "checkbox") {
				const group = field.closest(".tab").querySelectorAll(`input[name="${field.name}"]`);
				return Array.from(group).some(input => input.checked);
			}
		} else if (tag === "textarea") {
			return field.value.trim() !== "";
		} else if (tag === "select") {
			return field.value && field.value.trim() !== "";
		}
		return true;
	}

	function showValidationMessage(field) {
		const oldMsg = field.closest("div")?.querySelector(".ip-validation-msg");
		if (oldMsg) oldMsg.remove();

		const newMsg = document.createElement("div");
		newMsg.className = "ip-validation-msg text-danger small mt-1";
		newMsg.style.display = "none";
		newMsg.innerText = (typeof getCurrentTranslation !== "undefined" && getCurrentTranslation.this_field_is_required) 
			? getCurrentTranslation.this_field_is_required 
			: "This field is required";

		field.closest("div").appendChild(newMsg);
		setTimeout(() => { newMsg.style.display = "block"; }, 50);
		field.classList.add("invalid");
	}

	function attachValidationLive(field) {
		const handler = () => {
			const submitBtn = document.getElementById("submitBtn");
			const tabs = document.getElementsByClassName("tab");

			if (isFieldValid(field)) {
				const msg = field.closest("div")?.querySelector(".ip-validation-msg");
				if (msg) msg.remove();
				field.classList.remove("invalid");

				// If on last tab, check all required fields to enable submit
				if (submitBtn && currentTab === tabs.length - 1) {
					const requiredFields = tabs[currentTab].querySelectorAll("[s-ip-required]");
					let allValid = true;
					requiredFields.forEach(f => {
						if (!isFieldValid(f)) allValid = false;
					});
					if (allValid) {
						submitBtn.type = "submit";
						submitBtn.classList.add("form-submit-btn", "ajax-submit");
					}
				}
			} else {
				showValidationMessage(field);
				
				// If on last tab and invalid, disable submit
				if (submitBtn && currentTab === tabs.length - 1) {
					submitBtn.type = "button";
					submitBtn.classList.remove("form-submit-btn", "ajax-submit");
				}
			}
		};

		field.addEventListener("input", handler);
		field.addEventListener("change", handler);
		field.addEventListener("click", handler);
	}


	function validateForm() {
		let valid = true;
		const tabs = document.getElementsByClassName("tab");
		const requiredFields = tabs[currentTab].querySelectorAll("[s-ip-required]");

		requiredFields.forEach(field => {
			if (!isFieldValid(field)) {
				showValidationMessage(field);
				valid = false;
			}
			attachValidationLive(field);
		});

		const steps = document.getElementsByClassName("step");
		if (steps[currentTab]) {
			if (valid) {
				steps[currentTab].classList.add("finish");
			} else {
				steps[currentTab].classList.remove("finish");
			}
		}

		return valid;
	}
</script>



@endpush