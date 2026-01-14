@php
	$authUser = Auth::user();
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

					@php
						$breadcrumbLabel =
							request()->document_type === 'ticket' ? ($getCurrentTranslation['ticket_list'] ?? 'ticket_list') :
							(request()->document_type === 'invoice' ? ($getCurrentTranslation['invoice_list'] ?? 'invoice_list') :
							(request()->document_type === 'ticket-invoice' ? ($getCurrentTranslation['ticket_and_invoice_list'] ?? 'ticket_and_invoice_list') :
							(request()->document_type === 'quotation' ? ($getCurrentTranslation['quotation_list'] ?? 'quotation_list') :
							(request()->has('data_for') && request()->data_for === 'agent' ? ($getCurrentTranslation['agent_document_list'] ?? 'agent_document_list') :
							($getCurrentTranslation['all_document_list'] ?? 'all_document_list')))));
					@endphp

					<li class="breadcrumb-item {{ isset($listRoute) ? 'text-muted' : '' }}">
						@if(isset($listRoute) && !empty($listRoute))
							<a href="{{ $listRoute }}{{ request()->document_type ? '?document_type='.request()->document_type : '' }}" class="text-muted text-hover-primary">
								{{ $breadcrumbLabel }}
							</a> &nbsp; -
						@else
							{{ $breadcrumbLabel }}
						@endif
					</li>

					@if(isset($createRoute) && !empty($createRoute))
						<li class="breadcrumb-item text-muted">
							<a href="{{ $createRoute }}{{ request()->document_type ? '?document_type='.request()->document_type : '' }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['create'] ?? 'create' }}</a> &nbsp; - 
						</li>
					@endif

					<li class="breadcrumb-item">{{ $getCurrentTranslation['search_flight'] ?? 'search_flight' }}</li>
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
			<form class="flight-search-form" method="post" action="{{ $searchImportRoute }}" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="document_type" value="{{ $editData->document_type ?? 'ticket' }}"/>
				<input type="hidden" name="flight_type" id="flight_type" value="one_way"/>

				<!-- Flight Search Card -->
				<div class="card rounded border mt-5 bg-white">
					<div class="card-header">
						<h3 class="card-title">
							<i class="fa-solid fa-plane-departure me-2"></i>
							{{ $getCurrentTranslation['flight_search_information'] ?? 'flight_search_information' }}
						</h3>
						<div class="card-toolbar">
							<button type="submit" class="btn btn-primary search-form-submit-btn search-ajax-submit">
								<i class="fa-solid fa-magnifying-glass me-2"></i>
								<span class="indicator-label">{{ $getCurrentTranslation['search_flights'] ?? 'search_flights' }}</span>
								<span class="indicator-progress d-none">
									{{ $getCurrentTranslation['please_wait'] ?? 'please_wait' }}
									<span class="spinner-border spinner-border-sm align-middle ms-2"></span>
								</span>
							</button>
						</div>
					</div>
					<div class="card-body">
						<!-- Flight Type Tabs -->
						<ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" role="tablist">
							<li class="nav-item" role="presentation">
								<button class="nav-link active" data-bs-toggle="tab" data-bs-target="#one_way_tab" type="button" role="tab" aria-selected="true" data-flight-type="one_way">
									<i class="fa-solid fa-arrow-right me-2"></i>
									{{ $getCurrentTranslation['one_way'] ?? 'one_way' }}
								</button>
							</li>
							<li class="nav-item" role="presentation">
								<button class="nav-link" data-bs-toggle="tab" data-bs-target="#round_trip_tab" type="button" role="tab" aria-selected="false" data-flight-type="round_trip">
									<i class="fa-solid fa-arrows-left-right me-2"></i>
									{{ $getCurrentTranslation['round_trip'] ?? 'round_trip' }}
								</button>
							</li>
							<li class="nav-item" role="presentation">
								<button class="nav-link" data-bs-toggle="tab" data-bs-target="#multi_city_tab" type="button" role="tab" aria-selected="false" data-flight-type="multi_city">
									<i class="fa-solid fa-route me-2"></i>
									{{ $getCurrentTranslation['multi_city'] ?? 'multi_city' }}
								</button>
							</li>
						</ul>

						<!-- Tab Content -->
						<div class="tab-content" id="flight_type_tabs">
							<!-- One Way Tab -->
							<div class="tab-pane fade show active" id="one_way_tab" role="tabpanel">
								<div class="row">
									<!-- Origin Airport -->
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['origin_airport'] ?? 'origin_airport' }}: <span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control airport-input" 
												placeholder="{{ $getCurrentTranslation['origin_placeholder'] ?? 'origin_placeholder' }}" 
												name="one_way[origin]" 
												value="{{ old('one_way.origin', $editData->origin ?? '') }}" 
												autocomplete="off"
											/>
											<small class="form-text text-muted">
												<i class="fa-solid fa-circle-info me-1"></i>
												{{ $getCurrentTranslation['search_by_city_or_code'] ?? 'search_by_city_or_code' }}
											</small>
											@error('one_way.origin')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>

									<!-- Destination Airport -->
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['destination_airport'] ?? 'destination_airport' }}: <span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control airport-input" 
												placeholder="{{ $getCurrentTranslation['destination_placeholder'] ?? 'destination_placeholder' }}" 
												name="one_way[destination]" 
												value="{{ old('one_way.destination', $editData->destination ?? '') }}" 
												autocomplete="off"
											/>
											<small class="form-text text-muted">
												<i class="fa-solid fa-circle-info me-1"></i>
												{{ $getCurrentTranslation['search_by_city_or_code'] ?? 'search_by_city_or_code' }}
											</small>
											@error('one_way.destination')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>

									<!-- Departure Date -->
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['departure_date'] ?? 'departure_date' }}: <span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control flatpickr-input departure-date" 
												placeholder="{{ $getCurrentTranslation['select_departure_date'] ?? 'select_departure_date' }}" 
												name="one_way[departure_at]" 
												value="{{ old('one_way.departure_at', $editData->departure_at ?? '') }}"
												readonly
											/>
											@error('one_way.departure_at')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>
								</div>
							</div>

							<!-- Round Trip Tab -->
							<div class="tab-pane fade" id="round_trip_tab" role="tabpanel">
								<div class="row">
									<!-- Origin Airport -->
									<div class="col-md-3">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['origin_airport'] ?? 'origin_airport' }}:
												<span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control airport-input" 
												placeholder="{{ $getCurrentTranslation['origin_placeholder'] ?? 'origin_placeholder' }}" 
												name="round_trip[origin]" 
												value="{{ old('round_trip.origin', $editData->origin ?? '') }}" 
												autocomplete="off"
											/>
											<small class="form-text text-muted">
												<i class="fa-solid fa-circle-info me-1"></i>
												{{ $getCurrentTranslation['search_by_city_or_code'] ?? 'search_by_city_or_code' }}
											</small>
											@error('round_trip.origin')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>

									<!-- Destination Airport -->
									<div class="col-md-3">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['destination_airport'] ?? 'destination_airport' }}: <span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control airport-input" 
												placeholder="{{ $getCurrentTranslation['destination_placeholder'] ?? 'destination_placeholder' }}" 
												name="round_trip[destination]" 
												value="{{ old('round_trip.destination', $editData->destination ?? '') }}" 
												autocomplete="off"
											/>
											<small class="form-text text-muted">
												<i class="fa-solid fa-circle-info me-1"></i>
												{{ $getCurrentTranslation['search_by_city_or_code'] ?? 'search_by_city_or_code' }}
											</small>
											@error('round_trip.destination')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>

									<!-- Departure Date -->
									<div class="col-md-3">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['departure_date'] ?? 'departure_date' }}: <span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control flatpickr-input departure-date" 
												placeholder="{{ $getCurrentTranslation['select_departure_date'] ?? 'select_departure_date' }}" 
												name="round_trip[departure_at]" 
												value="{{ old('round_trip.departure_at', $editData->departure_at ?? '') }}"
												readonly
											/>
											@error('round_trip.departure_at')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>

									<!-- Return Date -->
									<div class="col-md-3">
										<div class="form-item mb-5">
											<label class="form-label">
												{{ $getCurrentTranslation['return_date'] ?? 'return_date' }}: <span class="text-danger">*</span>
											</label>
											<input 
												type="text" 
												class="form-control flatpickr-input return-date" 
												placeholder="{{ $getCurrentTranslation['select_return_date'] ?? 'select_return_date' }}" 
												name="round_trip[return_at]" 
												value="{{ old('round_trip.return_at', $editData->return_at ?? '') }}" 
												readonly
											/>
											@error('round_trip.return_at')
												<span class="text-danger text-sm text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>
								</div>
							</div>

							<!-- Multi City Tab -->
							<div class="tab-pane fade" id="multi_city_tab" role="tabpanel">
								<div id="multi_city_flights_container">
									<!-- Flight rows will be dynamically added here -->
								</div>
								<div class="d-flex justify-content-end mb-5">
									<button type="button" class="btn btn-sm btn-primary" id="add_flight_row">
										<i class="fa-solid fa-plus me-2"></i>
										{{ $getCurrentTranslation['add_flight'] ?? 'add_flight' }}
									</button>
								</div>
							</div>
						</div>

						<!-- Common Fields (Class, Passenger) -->
						<div class="row border-top pt-5 mt-5">
							<!-- Airline -->
							<div class="col-md-4">
								@php
									$options = $airlines;
									$selected = '';
								@endphp
								<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
								<select class="form-select select2-with-images parent-ip" data-name="airline_name" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="airline_name">
									<option value="">----</option>
									@foreach($options as $option)
										<option value="{{ $option->name }}" data-image="{{ $option->logo_url ?? defaultImage('s') }}" {{ $option->id == $selected ? 'selected' : '' }}>
											{{ $option->name }}
										</option>
									@endforeach
								</select>
							</div>

							<!-- Class -->
							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['class'] ?? 'class' }}: <span class="text-danger">*</span>
									</label>
									<select class="form-select select2-with-images" name="class">
										<option value="economy" {{ old('class', 'economy') == 'economy' ? 'selected' : '' }}>{{ $getCurrentTranslation['economy'] ?? 'economy' }}</option>
										<option value="business" {{ old('class') == 'business' ? 'selected' : '' }}>{{ $getCurrentTranslation['business'] ?? 'business' }}</option>
										<option value="first" {{ old('class') == 'first' ? 'selected' : '' }}>{{ $getCurrentTranslation['first_class'] ?? 'first_class' }}</option>
									</select>
									@error('class')
										<span class="text-danger text-sm text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<!-- Passenger -->
							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['passenger'] ?? 'passenger' }}: <span class="text-danger">*</span>
									</label>
									<select class="form-select select2-with-images" name="passenger">
										<option value="1" {{ old('passenger', '1') == '1' ? 'selected' : '' }}>1</option>
										<option value="2" {{ old('passenger') == '2' ? 'selected' : '' }}>2</option>
										<option value="3" {{ old('passenger') == '3' ? 'selected' : '' }}>3</option>
										<option value="4" {{ old('passenger') == '4' ? 'selected' : '' }}>4</option>
										<option value="5" {{ old('passenger') == '5' ? 'selected' : '' }}>5</option>
										<option value="6" {{ old('passenger') == '6' ? 'selected' : '' }}>6</option>
										<option value="7" {{ old('passenger') == '7' ? 'selected' : '' }}>7</option>
										<option value="8" {{ old('passenger') == '8' ? 'selected' : '' }}>8</option>
										<option value="9" {{ old('passenger') == '9' ? 'selected' : '' }}>9</option>
									</select>
									@error('passenger')
										<span class="text-danger text-sm text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Submit Button -->
				<div class="d-flex justify-content-end my-10">
					<button type="reset" class="btn btn-light me-3">
						<i class="fa-solid fa-rotate-left me-2"></i>
						{{ $getCurrentTranslation['reset'] ?? 'reset' }}
					</button>
					<button type="submit" class="btn btn-primary search-form-submit-btn search-ajax-submit">
						<i class="fa-solid fa-magnifying-glass me-2"></i>
						<span class="indicator-label">{{ $getCurrentTranslation['search_flights'] ?? 'search_flights' }}</span>
						<span class="indicator-progress d-none">
							{{ $getCurrentTranslation['please_wait'] ?? 'please_wait' }}
							<span class="spinner-border spinner-border-sm align-middle ms-2"></span>
						</span>
					</button>
				</div>
			</form>

			<!-- Search Results Container -->
			<div id="flight-results-container" class="mt-5 d-none">
				<div class="card rounded border bg-white">
					<div class="card-header">
						<h3 class="card-title">
							<i class="fa-solid fa-list me-2"></i>
							{{ $getCurrentTranslation['search_results'] ?? 'search_results' }}
						</h3>
						<div class="card-toolbar">
							<span class="badge badge-light-primary fs-7" id="results-count">0 {{ $getCurrentTranslation['flights_found'] ?? 'flights_found' }}</span>
						</div>
					</div>
					<div class="card-body">
						<div id="flight-results-content">
							<!-- Results will be populated here via AJAX -->
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
<script>
	$(document).ready(function() {
		// Flight type tracking
		let currentFlightType = 'one_way';
		let multiCityFlightCount = 2; // Minimum 2 flights

		// Handle tab switching
		$('[data-flight-type]').on('click', function() {
			currentFlightType = $(this).data('flight-type');
			$('#flight_type').val(currentFlightType);
			
			// Reinitialize date pickers for active tab
			setTimeout(function() {
				initializeDatePickers();
			}, 100);
		});

		// Initialize Flatpickr for departure dates
		function initializeDatePickers() {
			$('.departure-date').each(function() {
				if (!$(this).data('flatpickr')) {
					$(this).flatpickr({
						dateFormat: 'Y-m-d',
						minDate: 'today',
						onChange: function(selectedDates, dateStr, instance) {
							// Update return date minimum to be after departure
							const $returnDate = $(instance.input).closest('.tab-pane, .flight-row').find('.return-date');
							if ($returnDate.length) {
								$returnDate.flatpickr({
									dateFormat: 'Y-m-d',
									minDate: dateStr
								});
							}
						}
					});
				}
			});

			// Initialize Flatpickr for return dates
			$('.return-date').each(function() {
				if (!$(this).data('flatpickr')) {
					$(this).flatpickr({
						dateFormat: 'Y-m-d',
						minDate: 'today'
					});
				}
			});
		}

		// Initialize date pickers on page load
		initializeDatePickers();

		// Enhanced Airport Autocomplete with City Name Search
		class AirportAutocomplete {
			constructor(inputElement, options = {}) {
				this.input = $(inputElement)[0];
				this.options = {
					minLength: 2,
					maxResults: 15,
					apiUrl: options.apiUrl || '{{ route("airports.search") }}',
					commonAirportsUrl: options.commonAirportsUrl || '{{ route("airports.common") }}',
					onSelect: options.onSelect || null,
					...options
				};
				
				this.resultsContainer = null;
				this.commonAirports = [];
				this.selectedAirport = null;
				this.searchTimeout = null;
				
				this.init();
			}

			init() {
				this.createResultsContainer();
				this.fetchCommonAirports();
				this.attachEventListeners();
			}

			createResultsContainer() {
				this.resultsContainer = document.createElement('div');
				this.resultsContainer.className = 'airport-autocomplete-results';
				this.resultsContainer.style.cssText = `
					position: absolute;
					z-index: 1050;
					background: white;
					border: 1px solid #ddd;
					border-radius: 6px;
					max-height: 400px;
					overflow-y: auto;
					display: none;
					box-shadow: 0 8px 16px rgba(0,0,0,0.15);
					width: 100%;
					margin-top: 2px;
				`;
				
				this.input.parentNode.style.position = 'relative';
				this.input.parentNode.appendChild(this.resultsContainer);
			}

			attachEventListeners() {
				// Input event with debouncing
				this.input.addEventListener('input', (e) => {
					const value = e.target.value;
					
					// Clear previous timeout
					if (this.searchTimeout) {
						clearTimeout(this.searchTimeout);
					}
					
					// Debounce search
					this.searchTimeout = setTimeout(() => {
						if (value.length >= this.options.minLength) {
							this.search(value);
						} else if (value.length === 0) {
							this.showCommonAirports();
						} else {
							this.hideResults();
						}
					}, 300);
				});

				// Focus event
				this.input.addEventListener('focus', () => {
					if (this.input.value.length === 0) {
						this.showCommonAirports();
					} else if (this.input.value.length >= this.options.minLength) {
						this.search(this.input.value);
					}
				});

				// Click outside to hide
				document.addEventListener('click', (e) => {
					if (!this.input.contains(e.target) && !this.resultsContainer.contains(e.target)) {
						this.hideResults();
					}
				});

				// Keyboard navigation
				this.input.addEventListener('keydown', (e) => {
					if (e.key === 'Escape') {
						this.hideResults();
					} else if (e.key === 'ArrowDown') {
						e.preventDefault();
						this.navigateResults('down');
					} else if (e.key === 'ArrowUp') {
						e.preventDefault();
						this.navigateResults('up');
					} else if (e.key === 'Enter') {
						const selected = this.resultsContainer.querySelector('.airport-result-item.active');
						if (selected) {
							e.preventDefault();
							selected.click();
						}
					}
				});
			}

			async fetchCommonAirports() {
				try {
					$('.r-preloader').show();
					const response = await fetch(this.options.commonAirportsUrl);
					const data = await response.json();
					
					if (data.success) {
						this.commonAirports = data.data;
					}
				} catch (error) {
					console.error('Error fetching common airports:', error);
				} finally {
					$('.r-preloader').hide();
				}
			}

			async search(query) {
				try {
					// Show loading state
					this.showLoading();
					$('.r-preloader').show();
					
					const response = await fetch(`${this.options.apiUrl}?query=${encodeURIComponent(query)}`);
					const data = await response.json();
					
					if (data.success && data.data.length > 0) {
						this.displayResults(data.data);
					} else {
						this.showNoResults();
					}
				} catch (error) {
					console.error('Error searching airports:', error);
					this.showError();
				} finally {
					$('.r-preloader').hide();
				}
			}

			showCommonAirports() {
				if (this.commonAirports.length > 0) {
					this.displayResults(this.commonAirports, 'Popular Airports');
				}
			}

			showLoading() {
				this.resultsContainer.innerHTML = `
					<div style="padding: 20px; text-align: center; color: #666;">
						<div class="spinner-border spinner-border-sm me-2" role="status"></div>
						Searching airports...
					</div>
				`;
				this.resultsContainer.style.display = 'block';
			}

			showNoResults() {
				this.resultsContainer.innerHTML = `
					<div style="padding: 20px; text-align: center; color: #999;">
						<i class="fa-solid fa-magnifying-glass me-2"></i>
						No airports found matching your search
					</div>
				`;
				this.resultsContainer.style.display = 'block';
			}

			showError() {
				this.resultsContainer.innerHTML = `
					<div style="padding: 20px; text-align: center; color: #dc3545;">
						<i class="fa-solid fa-circle-exclamation me-2"></i>
						Error loading airports. Please try again.
					</div>
				`;
				this.resultsContainer.style.display = 'block';
			}

			displayResults(airports, title = null) {
				if (airports.length === 0) {
					this.showNoResults();
					return;
				}

				let html = '';
				
				if (title) {
					html += `
						<div style="padding: 10px 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">
							<i class="fa-solid fa-star me-2"></i>${title}
						</div>
					`;
				}

				airports.forEach((airport, index) => {
					const countryFlag = this.getCountryFlag(airport.country || '');
					html += `
						<div class="airport-result-item ${index === 0 ? 'active' : ''}" 
							 data-code="${airport.code}" 
							 data-name="${airport.name}" 
							 data-city="${airport.city}"
							 data-country="${airport.country || ''}"
							 style="padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: all 0.2s;">
							<div style="display: flex; align-items: center; gap: 10px;">
								<div style="background: #667eea; color: white; padding: 6px 10px; border-radius: 4px; font-weight: bold; font-size: 13px; min-width: 50px; text-align: center;">
									${airport.code}
								</div>
								<div style="flex: 1;">
									<div style="font-weight: 600; color: #333; font-size: 14px; margin-bottom: 2px;">
										${this.highlightMatch(airport.city, this.input.value)}
									</div>
									<div style="font-size: 12px; color: #666;">
										${this.highlightMatch(airport.name, this.input.value)}
									</div>
									${airport.country ? `
										<div style="font-size: 11px; color: #999; margin-top: 2px;">
											${countryFlag} ${airport.country}
										</div>
									` : ''}
								</div>
							</div>
						</div>
					`;
				});

				this.resultsContainer.innerHTML = html;
				this.resultsContainer.style.display = 'block';

				// Attach event handlers
				this.resultsContainer.querySelectorAll('.airport-result-item').forEach(item => {
					item.addEventListener('mouseenter', function() {
						// Remove active from all
						document.querySelectorAll('.airport-result-item').forEach(i => i.classList.remove('active'));
						this.classList.add('active');
						this.style.background = '#f8f9fa';
					});
					
					item.addEventListener('mouseleave', function() {
						this.style.background = 'white';
					});
					
					item.addEventListener('click', () => {
						const code = item.dataset.code;
						const name = item.dataset.name;
						const city = item.dataset.city;
						const country = item.dataset.country;
						
						this.selectAirport({ code, name, city, country });
					});
				});
			}

			highlightMatch(text, query) {
				if (!query || query.length < 2) return text;
				
				const regex = new RegExp(`(${query})`, 'gi');
				return text.replace(regex, '<span style="background: #fff3cd; font-weight: 600;">$1</span>');
			}

			getCountryFlag(country) {
				const flags = {
					'United States': '🇺🇸',
					'United Kingdom': '🇬🇧',
					'France': '🇫🇷',
					'Germany': '🇩🇪',
					'Spain': '🇪🇸',
					'Italy': '🇮🇹',
					'Japan': '🇯🇵',
					'China': '🇨🇳',
					'India': '🇮🇳',
					'Bangladesh': '🇧🇩',
					'UAE': '🇦🇪',
					'Saudi Arabia': '🇸🇦',
					'Singapore': '🇸🇬',
					'Thailand': '🇹🇭',
					'Australia': '🇦🇺',
					'Canada': '🇨🇦',
					'Netherlands': '🇳🇱',
				};
				return flags[country] || '✈️';
			}

			navigateResults(direction) {
				const items = Array.from(this.resultsContainer.querySelectorAll('.airport-result-item'));
				const currentActive = this.resultsContainer.querySelector('.airport-result-item.active');
				
				if (items.length === 0) return;
				
				let newIndex = 0;
				
				if (currentActive) {
					const currentIndex = items.indexOf(currentActive);
					newIndex = direction === 'down' 
						? (currentIndex + 1) % items.length 
						: (currentIndex - 1 + items.length) % items.length;
				}
				
				items.forEach(item => {
					item.classList.remove('active');
					item.style.background = 'white';
				});
				
				items[newIndex].classList.add('active');
				items[newIndex].style.background = '#f8f9fa';
				items[newIndex].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
			}

			selectAirport(airport) {
				this.selectedAirport = airport;
				
				// Display format: "City - Code" or just "Code" based on preference
				this.input.value = airport.code;
				
				// Store full airport data as data attribute
				$(this.input).data('airportData', airport);
				
				this.hideResults();
				
				// Mark as valid
				$(this.input).removeClass('is-invalid').addClass('is-valid');
				
				// Remove any error messages
				$(this.input).parent().find('.invalid-feedback').hide();
				
				// Trigger custom event
				$(this.input).trigger('airportSelected', [airport]);
				
				// Call callback if provided
				if (this.options.onSelect) {
					this.options.onSelect(airport);
				}
			}

			hideResults() {
				this.resultsContainer.style.display = 'none';
			}

			validate() {
				const value = this.input.value.trim();
				
				// Check if empty
				if (value.length === 0) {
					this.markInvalid('Please select an airport');
					return false;
				}
				
				// Check if it's exactly 3 letters (IATA code)
				if (/^[A-Z]{3}$/.test(value.toUpperCase())) {
					// Check against known invalid city codes
					const cityCodes = ['NYC', 'LON', 'PAR', 'TOK', 'MIL', 'BER', 'ROM', 'OSA'];
					if (cityCodes.includes(value.toUpperCase())) {
						this.markInvalid(`${value} is a city code. Please select a specific airport from the dropdown.`);
						return false;
					}
					
					this.markValid();
					return true;
				}
				
				// If not a valid code, show error
				this.markInvalid('Please select an airport from the dropdown');
				return false;
			}

			markInvalid(message) {
				$(this.input).removeClass('is-valid').addClass('is-invalid');
				
				let errorDiv = $(this.input).parent().find('.invalid-feedback');
				if (errorDiv.length === 0) {
					errorDiv = $('<div class="invalid-feedback" style="display: block;"></div>');
					$(this.input).parent().append(errorDiv);
				}
				errorDiv.html(`<i class="fa-solid fa-circle-exclamation me-1"></i>${message}`).show();
			}

			markValid() {
				$(this.input).removeClass('is-invalid').addClass('is-valid');
				$(this.input).parent().find('.invalid-feedback').hide();
			}
		}

		// Initialize Multi City flights
		function initializeMultiCity() {
			const container = $('#multi_city_flights_container');
			container.empty();
			
			// Add initial 2 flights (minimum)
			for (let i = 0; i < 2; i++) {
				addMultiCityFlightRow(i);
			}
		}

		// Add Multi City flight row
		function addMultiCityFlightRow(index) {
			const container = $('#multi_city_flights_container');
			const flightNumber = index + 1;
			
			const rowHtml = `
				<div class="flight-row mb-4 p-4 border rounded" data-flight-index="${index}">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="mb-0">
							<i class="fa-solid fa-plane me-2"></i>
							{{ $getCurrentTranslation['flight'] ?? 'flight' }} ${flightNumber}
						</h5>
						<button type="button" class="btn btn-sm btn-danger remove-flight-row" ${index < 2 ? 'style="display:none;"' : ''}>
							<i class="fa-solid fa-trash me-1"></i>
							{{ $getCurrentTranslation['remove'] ?? 'remove' }}
						</button>
					</div>
					<div class="row">
						<div class="col-md-4">
							<div class="form-item mb-3">
								<label class="form-label">
									{{ $getCurrentTranslation['origin_airport'] ?? 'origin_airport' }}: <span class="text-danger">*</span>
								</label>
								<input 
									type="text" 
									class="form-control airport-input" 
									placeholder="{{ $getCurrentTranslation['origin_placeholder'] ?? 'origin_placeholder' }}" 
									name="multi_city[${index}][origin]" 
									autocomplete="off"
								/>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-item mb-3">
								<label class="form-label">
									{{ $getCurrentTranslation['destination_airport'] ?? 'destination_airport' }}: <span class="text-danger">*</span>
								</label>
								<input 
									type="text" 
									class="form-control airport-input" 
									placeholder="{{ $getCurrentTranslation['destination_placeholder'] ?? 'destination_placeholder' }}" 
									name="multi_city[${index}][destination]" 
									autocomplete="off"
								/>
							</div>
						</div>
						<div class="col-md-4">
							<div class="form-item mb-3">
								<label class="form-label">
									{{ $getCurrentTranslation['departure_date'] ?? 'departure_date' }}: <span class="text-danger">*</span>
								</label>
								<input 
									type="text" 
									class="form-control flatpickr-input departure-date" 
									placeholder="{{ $getCurrentTranslation['select_departure_date'] ?? 'select_departure_date' }}" 
									name="multi_city[${index}][departure_at]" 
									readonly
								/>
							</div>
						</div>
					</div>
				</div>
			`;
			
			container.append(rowHtml);
			multiCityFlightCount++;
			
			// Initialize airport autocomplete for new row
			const newRow = container.find(`[data-flight-index="${index}"]`);
			initializeAirportAutocomplete(newRow.find('input[name*="[origin]"]'));
			initializeAirportAutocomplete(newRow.find('input[name*="[destination]"]'));
			
			// Initialize date picker for new row
			initializeDatePickers();
			
			// Update remove button visibility
			updateRemoveButtons();
		}

		// Remove Multi City flight row
		$(document).on('click', '.remove-flight-row', function() {
			const $flightRow = $(this).closest('.flight-row');
			const rowIndex = parseInt($flightRow.attr('data-flight-index'));
			const flightNumber = rowIndex + 1;
			
			// Prevent removal of first 2 rows
			if (rowIndex < 2) {
				Swal.fire({
					icon: 'warning',
					title: '{{ $getCurrentTranslation["minimum_flights"] ?? "minimum_flights" }}',
					text: '{{ $getCurrentTranslation["minimum_two_flights"] ?? "minimum_two_flights" }}'
				});
				return;
			}
			
			if (multiCityFlightCount <= 2) {
				Swal.fire({
					icon: 'warning',
					title: '{{ $getCurrentTranslation["minimum_flights"] ?? "minimum_flights" }}',
					text: '{{ $getCurrentTranslation["minimum_two_flights"] ?? "minimum_two_flights" }}'
				});
				return;
			}
			
			// Show confirmation dialog before removing
			Swal.fire({
				icon: 'question',
				title: '{{ $getCurrentTranslation["confirm_remove"] ?? "confirm_remove" }}',
				text: '{{ $getCurrentTranslation["confirm_remove_flight"] ?? "confirm_remove_flight" }}',
				showCancelButton: true,
				confirmButtonText: '{{ $getCurrentTranslation["yes_remove"] ?? "yes_remove" }}',
				cancelButtonText: '{{ $getCurrentTranslation["cancel"] ?? "cancel" }}',
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
			}).then((result) => {
				if (result.isConfirmed) {
					$flightRow.remove();
					multiCityFlightCount--;
					
					// Renumber flights
					renumberMultiCityFlights();
					updateRemoveButtons();
					
					// Show success message
					// Swal.fire({
					// 	icon: 'success',
					// 	title: '{{ $getCurrentTranslation["removed"] ?? "removed" }}',
					// 	text: '{{ $getCurrentTranslation["flight_removed_success"] ?? "flight_removed_success" }}',
					// 	timer: 2000,
					// 	showConfirmButton: false
					// });
				}
			});
		});

		// Add flight row button
		$('#add_flight_row').on('click', function() {
			$('.r-preloader').show();
			const nextIndex = $('#multi_city_flights_container .flight-row').length;
			addMultiCityFlightRow(nextIndex);
			// Hide preloader after a short delay to allow DOM updates
			setTimeout(function() {
				$('.r-preloader').hide();
			}, 300);
		});

		// Renumber flights
		function renumberMultiCityFlights() {
			$('#multi_city_flights_container .flight-row').each(function(index) {
				const flightNumber = index + 1;
				$(this).find('h5').html(`<i class="fa-solid fa-plane me-2"></i>{{ $getCurrentTranslation['flight'] ?? 'flight' }} ${flightNumber}`);
				$(this).attr('data-flight-index', index);
				
				// Update input names
				$(this).find('input[name*="[origin]"]').attr('name', `multi_city[${index}][origin]`);
				$(this).find('input[name*="[destination]"]').attr('name', `multi_city[${index}][destination]`);
				$(this).find('input[name*="[departure_at]"]').attr('name', `multi_city[${index}][departure_at]`);
				
				// Hide remove button for first 2 rows (index 0 and 1)
				const $removeBtn = $(this).find('.remove-flight-row');
				if (index < 2) {
					$removeBtn.hide();
				} else {
					$removeBtn.show();
				}
			});
		}

		// Update remove button visibility
		function updateRemoveButtons() {
			$('#multi_city_flights_container .flight-row').each(function(index) {
				const $removeBtn = $(this).find('.remove-flight-row');
				// First 2 rows (index 0 and 1) should always be hidden
				// Other rows should be visible only if there are more than 2 flights
				if (index < 2) {
					$removeBtn.hide();
				} else {
					$removeBtn.toggle(multiCityFlightCount > 2);
				}
			});
		}

		// Initialize airport autocomplete for an input
		function initializeAirportAutocomplete($input) {
			if ($input.length && !$input.data('autocomplete-initialized')) {
				new AirportAutocomplete($input[0], {
					apiUrl: '{{ route("airports.search") }}',
					commonAirportsUrl: '{{ route("airports.common") }}',
					onSelect: function(airport) {
						$input.attr('title', `${airport.name}, ${airport.city}`);
					}
				});
				$input.data('autocomplete-initialized', true);
			}
		}

		// Initialize airport autocomplete for all existing inputs
		$('.airport-input').each(function() {
			initializeAirportAutocomplete($(this));
		});

		// Initialize Multi City with 2 default flights (after AirportAutocomplete class is defined)
		initializeMultiCity();

		// AJAX Form Submission
		$('.flight-search-form').on('submit', function(e) {
			e.preventDefault();
			
			const form = $(this);
			const flightType = $('#flight_type').val();
			
			// Validate based on flight type
			let isValid = true;
			let validationErrors = [];
			
			if (flightType === 'one_way') {
				const originInput = form.find('input[name="one_way[origin]"]');
				const destInput = form.find('input[name="one_way[destination]"]');
				const depDateInput = form.find('input[name="one_way[departure_at]"]');
				
				if (!originInput.val() || originInput.val().length !== 3) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["origin_required"] ?? "origin_required" }}');
				}
				if (!destInput.val() || destInput.val().length !== 3) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["destination_required"] ?? "destination_required" }}');
				}
				if (!depDateInput.val()) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["departure_date_required"] ?? "departure_date_required" }}');
				}
			} else if (flightType === 'round_trip') {
				const originInput = form.find('input[name="round_trip[origin]"]');
				const destInput = form.find('input[name="round_trip[destination]"]');
				const depDateInput = form.find('input[name="round_trip[departure_at]"]');
				const retDateInput = form.find('input[name="round_trip[return_at]"]');
				
				if (!originInput.val() || originInput.val().length !== 3) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["origin_required"] ?? "origin_required" }}');
				}
				if (!destInput.val() || destInput.val().length !== 3) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["destination_required"] ?? "destination_required" }}');
				}
				if (!depDateInput.val()) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["departure_date_required"] ?? "departure_date_required" }}');
				}
				if (!retDateInput.val()) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["return_date_required"] ?? "return_date_required" }}');
				}
			} else if (flightType === 'multi_city') {
				const flightRows = form.find('#multi_city_flights_container .flight-row');
				
				if (flightRows.length < 2) {
					isValid = false;
					validationErrors.push('{{ $getCurrentTranslation["minimum_two_flights"] ?? "minimum_two_flights" }}');
				}
				
				flightRows.each(function(index) {
					const originInput = $(this).find('input[name*="[origin]"]');
					const destInput = $(this).find('input[name*="[destination]"]');
					const depDateInput = $(this).find('input[name*="[departure_at]"]');
					
					if (!originInput.val() || originInput.val().length !== 3) {
						isValid = false;
						validationErrors.push(`{{ $getCurrentTranslation["flight"] ?? "Flight" }} ${index + 1}: {{ $getCurrentTranslation["origin_required"] ?? "origin_required" }}`);
					}
					if (!destInput.val() || destInput.val().length !== 3) {
						isValid = false;
						validationErrors.push(`{{ $getCurrentTranslation["flight"] ?? "Flight" }} ${index + 1}: {{ $getCurrentTranslation["destination_required"] ?? "destination_required" }}`);
					}
					if (!depDateInput.val()) {
						isValid = false;
						validationErrors.push(`{{ $getCurrentTranslation["flight"] ?? "Flight" }} ${index + 1}: {{ $getCurrentTranslation["departure_date_required"] ?? "departure_date_required" }}`);
					}
				});
			}
			
			if (!isValid) {
				Swal.fire({
					icon: 'error',
					title: '{{ $getCurrentTranslation["validation_error"] ?? "validation_error" }}',
					html: validationErrors.join('<br>')
				});
				return false;
			}
			
			const submitBtn = form.find('.search-form-submit-btn');
			const indicator = submitBtn.find('.indicator-label');
			const progress = submitBtn.find('.indicator-progress');
			
			// Show loading state
			submitBtn.prop('disabled', true);
			indicator.addClass('d-none');
			progress.removeClass('d-none');
			$('.r-preloader').show();
			
			// Prepare form data
			const formData = form.serialize();
			
			$.ajax({
				url: form.attr('action'),
				method: 'POST',
				data: formData,
				success: function(response) {
					if (response.success) {
						$('#flight-results-container').removeClass('d-none');
						
						const count = response.data?.data?.length || 0;
						$('#results-count').text(count + ' {{ $getCurrentTranslation["flights_found"] ?? "flights_found" }}');
						
						displayFlightResults(response.data);
						
						// Smooth scroll to results container after a short delay to ensure DOM is updated
						setTimeout(function() {
							$('html, body').animate({
								scrollTop: $('#flight-results-container').offset().top - 100
							}, 800);
						}, 300);
						
						Swal.fire({
							icon: 'success',
							title: '{{ $getCurrentTranslation["success"] ?? "Success" }}',
							text: count + ' {{ $getCurrentTranslation["flights_found"] ?? "flights found" }}',
							timer: 2000,
							showConfirmButton: false
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: '{{ $getCurrentTranslation["error"] ?? "Error" }}',
							text: response.message || '{{ $getCurrentTranslation["something_went_wrong"] ?? "something_went_wrong" }}'
						});
					}
				},
				error: function(xhr) {
					let errorMessage = '{{ $getCurrentTranslation["something_went_wrong"] ?? "something_went_wrong" }}';
					
					if (xhr.responseJSON) {
						if (xhr.responseJSON.message) {
							errorMessage = xhr.responseJSON.message;
						} else if (xhr.responseJSON.errors) {
							const errors = Object.values(xhr.responseJSON.errors).flat();
							errorMessage = errors.join('<br>');
						}
					}
					
					Swal.fire({
						icon: 'error',
						title: '{{ $getCurrentTranslation["error"] ?? "Error" }}',
						html: errorMessage
					});
				},
				complete: function() {
					submitBtn.prop('disabled', false);
					indicator.removeClass('d-none');
					progress.addClass('d-none');
					$('.r-preloader').hide();
				}
			});
		});

		// Handle form reset - remove validation classes
		$('.flight-search-form').on('reset', function() {
			// Remove is-valid and is-invalid classes from all form inputs
			$(this).find('.is-valid, .is-invalid').removeClass('is-valid is-invalid');
			
			// Remove invalid-feedback messages
			$(this).find('.invalid-feedback').hide();
			
			// Reset flight type to one_way
			$('#flight_type').val('one_way');
			currentFlightType = 'one_way';
			
			// Reset to first tab
			$('[data-flight-type="one_way"]').tab('show');
			
			// Hide results container
			$('#flight-results-container').addClass('d-none');
			
			// Reset multi-city flights
			setTimeout(function() {
				multiCityFlightCount = 2;
				initializeMultiCity();
			}, 100);
		});

		// Function to display flight results
		function displayFlightResults(data) {
			const container = $('#flight-results-content');
			container.empty();
			
			if (!data || !data.data || data.data.length === 0) {
				container.html(`
					<div class="alert alert-warning">
						<i class="fa-solid fa-circle-exclamation me-2"></i>
						{{ $getCurrentTranslation['no_flights_found'] ?? 'no_flights_found' }}
					</div>
				`);
				return;
			}
			
			let html = '<div class="table-responsive"><table class="table table-hover table-striped align-middle">';
			html += '<thead class="table-light">';
			html += '<tr>';
			html += '<th>{{ $getCurrentTranslation["airline"] ?? "airline" }}</th>';
			html += '<th>{{ $getCurrentTranslation["departure"] ?? "departure" }}</th>';
			html += '<th>{{ $getCurrentTranslation["arrival"] ?? "arrival" }}</th>';
			html += '<th>{{ $getCurrentTranslation["price"] ?? "price" }}</th>';
			html += '<th>{{ $getCurrentTranslation["action"] ?? "action" }}</th>';
			html += '</tr>';
			html += '</thead><tbody>';
			
			data.data.forEach(function(flight) {
				html += '<tr>';
				html += '<td>' + (flight.airline || 'N/A') + '</td>';
				html += '<td>' + (flight.departure_at || 'N/A') + '</td>';
				html += '<td>' + (flight.return_at || 'N/A') + '</td>';
				html += '<td><strong>' + (flight.price || 'N/A') + ' ' + (flight.currency || '') + '</strong></td>';
				html += '<td><button class="btn btn-sm btn-primary select-flight" data-flight=\'' + JSON.stringify(flight) + '\'>{{ $getCurrentTranslation["select"] ?? "select" }}</button></td>';
				html += '</tr>';
			});
			
			html += '</tbody></table></div>';
			container.html(html);
		}

		// Handle flight selection
		$(document).on('click', '.select-flight', function() {
			const flightData = $(this).data('flight');
			
			console.log('Selected flight:', flightData);
			
			Swal.fire({
				icon: 'success',
				title: '{{ $getCurrentTranslation["flight_selected"] ?? "flight_selected" }}',
				text: '{{ $getCurrentTranslation["flight_selected_message"] ?? "flight_selected_message" }}',
				timer: 2000,
				showConfirmButton: false
			});
		});
	});
</script>
@endpush