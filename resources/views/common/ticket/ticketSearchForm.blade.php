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
					
					@if(isset($editData))
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_ticket'] ?? 'edit_ticket' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_ticket'] ?? 'create_ticket' }}</li>
					@endif
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(isset($editData) && !empty($editData))
					<div class="btn-group">
                        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ $getCurrentTranslation['download'] ?? 'download' }}
                        </button>
                        <div class="dropdown-menu p-0">
                            <a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=1" class="dropdown-item btn btn-sm fw-bold btn-success">{{ $getCurrentTranslation['with_price'] ?? 'with_price' }}</a>
                            <a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0" class="dropdown-item btn btn-sm fw-bold btn-info">{{ $getCurrentTranslation['without_price'] ?? 'without_price' }}</a>

							@if(count($editData->passengers) > 0)
								@foreach($editData->passengers as $passenger)
									<a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0&passenger={{ $passenger->id }}" class="dropdown-item btn btn-sm fw-bold btn-info">
										{{ $passenger->name }}
									</a>

									{{-- <a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=1&passenger={{ $passenger->id }}" class="dropdown-item btn btn-sm fw-bold btn-success">
										{{ $passenger->name }} ({{ $getCurrentTranslation['with_price'] ?? 'with_price' }})
									</a>
									<a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0&passenger={{ $passenger->id }}" class="dropdown-item btn btn-sm fw-bold btn-info">
										{{ $passenger->name }} ({{ $getCurrentTranslation['without_price'] ?? 'without_price' }})
									</a> --}}
								@endforeach
							@endif
                        </div>
                    </div>
				@endif

				@if(hasPermission('ticket.show') && isset($editData) && !empty($editData))
                    <a href="{{ route('ticket.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pager"></i>
                        {{ $getCurrentTranslation['details'] ?? 'details' }}
                    </a>
                @endif

				@if(hasPermission('ticket.mail') && isset($editData) && !empty($editData))
                    <a href="{{ route('ticket.mail', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-envelope"></i>
                        {{ $getCurrentTranslation['mail'] ?? 'mail' }}
                    </a>
                @endif

                @if(hasPermission('ticket.search.form') && !isset($editData))
                	<a href="{{ route('ticket.search.form') }}?document_type=ticket" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-file-import"></i>
						{{ $getCurrentTranslation['import_data'] ?? 'import_data' }}
					</a>
                @endif
				
				@if(isset($listRoute) && !empty($listRoute))
					<a href="{{ $listRoute }}{{ request()->document_type ? '?document_type='.request()->document_type : '' }}" class="btn btn-sm fw-bold btn-primary">
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

			<div id="flightSearchFormContainer" class="">
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
									<select class="form-select select2-with-images parent-ip" data-control="select2" data-name="airline_name" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" name="airline_name">
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
										<select class="form-select select2-with-images" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" name="class">
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
										<select class="form-select select2-with-images" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" name="passenger">
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

			<form class="" method="post" action="{{ $saveRoute }}" enctype="multipart/form-data">
				@csrf
				@if(isset($editData) && !empty($editData))
					@method('put')
				@endif

				@if(isset($editData) && !empty($editData))
				    <input type="hidden" name="document_type" value="{{ $editData->document_type ?? 'ticket' }}"/>
				@else
				    <input type="hidden" name="document_type" value="{{ request()->document_type ?? 'ticket' }}"/>
				@endif

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['ticket_informations'] ?? 'ticket_informations' }}</h3>
						<div class="card-toolbar">
							<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
								@if(isset($editData))
									<span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
								@else
									<span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
								@endif
							</button>
						</div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['e-Booking', 'e-Ticket'];

										$selected = $editData->booking_type ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['booking_type_label'] ?? 'booking_type_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['booking_type_placeholder'] ?? 'booking_type_placeholder' }}" name="booking_type">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('booking_type')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['invoice_date_placeholder'] ?? 'invoice_date_placeholder' }}" class="form-control mb-2 flatpickr-input"  name="invoice_date" value="{{ $editData->invoice_date ?? '' }}"/>
									@error('invoice_date')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['invoice_id_placeholder'] ?? 'invoice_id_placeholder' }}" name="invoice_id" value="{{ $editData->invoice_id ?? generateInvoiceId() }}"/>
									@error('invoice_id')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['reservation_number_label'] ?? 'reservation_number_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['reservation_number_placeholder'] ?? 'reservation_number_placeholder' }}" name="reservation_number" ip-required value="{{ $editData->reservation_number ?? '' }}"/>
									@error('reservation_number')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							
							{{-- <div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">Airlines PNR:</label>
									<input type="text" class="form-control" placeholder="Enter airlines PNR" name="airlines_pnr" ip-required value="{{ $editData->airlines_pnr ?? '' }}"/>
									@error('airlines_pnr')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div> --}}

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['One Way', 'Round Trip', 'Multi City'];

										$selected = $editData->trip_type ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['trip_type_placeholder'] ?? 'trip_type_placeholder' }}" name="trip_type">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('trip_type')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Economy', 'Premium Economy', 'Business Class', 'First Class'];

										$selected = $editData->ticket_type ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['ticket_type_label'] ?? 'ticket_type_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['ticket_type_placeholder'] ?? 'ticket_type_placeholder' }}" name="ticket_type">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('ticket_type')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['On Hold', 'Processing', 'Confirmed', 'Cancelled'];

										$selected = $editData->booking_status ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['booking_status_label'] ?? 'booking_status_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['booking_status_placeholder'] ?? 'booking_status_placeholder' }}" name="booking_status">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('booking_status')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container trip-flight">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['flight_informations'] ?? 'flight_informations' }}</h3>
						<div class="card-toolbar">
							<button type="button" class="btn btn-sm btn-success append-item-add-btn" style="display: none">
								<i class="fa-solid fa-plus"></i>
							</button>
						</div>
					</div>
					<div class="card-body append-item-wrapper">
						@if(isset($editData) && count($editData->flights) > 0)
							@foreach($editData->flights as $item)
								<div class="append-item rounded border p-5 mb-5">
									<div class="append-item-header one-way-trip"style="display: none">
										<div class="d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['one_way'] ?? 'one_way' }}</h3>
										</div>
									</div>

									<div class="append-item-header round-trip" style="display: none">
										<div class="d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['round_trip_outbound'] ?? 'round_trip_outbound' }}</h3>
											<div class="append-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>
									</div>
									<div class="append-item-header multi-city-trip" style="display: none">
										<div class="d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['multi_city'] ?? 'multi_city' }} <span class="append-item-count"></span></h3>
											<div class="append-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>
									</div>
									<div class="row p-5">
										<div class="col-md-4">
											<input type="hidden" name="ticket_flight_info[0][flight_id]" value="{{ $item->id }}">
											@php
												$options = $airlines;
												$selected = $item->airline_id;
											@endphp
											<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
											<select class="form-select select2-with-images parent-ip" data-control="select2" data-name="airline_id" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" name="ticket_flight_info[0][airline_id]">
												<option value="">----</option>
												@foreach($options as $option)
													<option value="{{ $option->id }}" data-image="{{ $option->logo_url ?? defaultImage('s') }}" {{ $option->id == $selected ? 'selected' : '' }}>
														{{ $option->name }}
													</option>
												@endforeach
											</select>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</label>
												<input type="text" class="form-control parent-ip" data-name="flight_number" placeholder="{{ $getCurrentTranslation['flight_number_placeholder'] ?? 'flight_number_placeholder' }}" name="ticket_flight_info[0][flight_number]" value="{{ $item->flight_number ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['leaving_from_label'] ?? 'leaving_from_label' }}:</label>
												<input type="text" class="form-control parent-ip" data-name="leaving_from" placeholder="{{ $getCurrentTranslation['leaving_from_placeholder'] ?? 'leaving_from_placeholder' }}" name="ticket_flight_info[0][leaving_from]" value="{{ $item->leaving_from ?? '' }}"/>
											</div>
										</div>

										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['departure_date_time_label'] ?? 'departure_date_time_label' }}:</label>
												<input type="text" placeholder="{{ $getCurrentTranslation['departure_date_time_placeholder'] ?? 'departure_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour parent-ip main-datetime" data-name="departure_date_time" name="ticket_flight_info[0][departure_date_time]" value="{{ $item->departure_date_time ?? '' }}"/>
											</div>
										</div>

										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['going_to_label'] ?? 'going_to_label' }}:</label>
												<input type="text" class="form-control parent-ip" data-name="going_to" placeholder="{{ $getCurrentTranslation['going_to_placeholder'] ?? 'going_to_placeholder' }}" name="ticket_flight_info[0][going_to]" value="{{ $item->going_to ?? '' }}"/>
											</div>
										</div>

										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['arrival_date_time_label'] ?? 'arrival_date_time_label' }}:</label>
												<input type="text" placeholder="{{ $getCurrentTranslation['arrival_date_time_placeholder'] ?? 'arrival_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour parent-ip" data-name="arrival_date_time" name="ticket_flight_info[0][arrival_date_time]" value="{{ $item->arrival_date_time ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['total_fly_time_label'] ?? 'total_fly_time_label' }}:</label>
												<input type="text" class="form-control mb-2 parent-ip" data-name="total_fly_time" placeholder="{{ $getCurrentTranslation['total_fly_time_placeholder'] ?? 'total_fly_time_placeholder' }}" name="ticket_flight_info[0][total_fly_time]" value="{{ $item->total_fly_time ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['is_transit_label'] ?? 'is_transit_label' }}:</label>
												<div class="form-check form-check-custom form-check-solid">
													<br>
													<label class="form-check-label usn cursor-pointer">
														<input class="form-check-input flight-transit parent-ip" data-name="is_transit" type="checkbox" name="ticket_flight_info[0][is_transit]" value="1" {{ $item->is_transit == 1 ? 'checked' : '' }}>
														{{ $getCurrentTranslation['is_transit_checkbox_text'] ?? 'is_transit_checkbox_text' }}
													</label>
												</div>
											</div>
										</div>
									</div>

									<div class="card-body append-child-item-wrapper flight-transit-child-wrap" style="display: none">
										@if(isset($item->transits) && count($item->transits) > 0)
											@foreach($item->transits as $transit)
												<div class="append-child-item">
													<div class="append-item-header d-flex justify-content-between">
														<h3 class="append-child-item-title">
															{{-- Flight <span class="append-item-count"></span>  --}}
															{{ $getCurrentTranslation['is_transit_checkbox_text'] ?? 'is_transit_checkbox_text' }} <span class="append-child-item-count"></span></h3>
														<div class="append-child-item-toolbar d-flex justify-content-end">
															<button type="button" class="btn btn-sm btn-success append-child-item-add-btn me-2">
																<i class="fa-solid fa-plus"></i>
															</button>
															<button type="button" class="btn btn-sm btn-danger append-child-item-remove-btn" style="display: none">
																<i class="fa-solid fa-minus"></i>
															</button>
														</div>
													</div>

													<div class="row p-5">
														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['total_transit_time_label'] ?? 'total_transit_time_label' }}:</label>
																<input type="text" class="form-control mb-2" placeholder="{{ $getCurrentTranslation['total_transit_time_placeholder'] ?? 'total_transit_time_placeholder' }}"  name="ticket_flight_info[0]transit[0][total_transit_time]" data-name="total_transit_time" value="{{ $transit->total_transit_time ?? '' }}"/>
															</div>
														</div>
														<div class="col-md-4">
															<input type="hidden" name="ticket_flight_info[0]transit[0][flight_id]" data-name="flight_id" value="{{ $transit->id }}">
															@php
																$options = $airlines;
																$selected = $transit->airline_id;
															@endphp
															<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
															<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="ticket_flight_info[0]transit[0][airline_id]" data-name="airline_id">
																<option value="">----</option>
																@foreach($options as $option)
																	<option value="{{ $option->id }}" data-image="{{ $option->logo_url ?? defaultImage('s') }}" {{ $option->id == $selected ? 'selected' : '' }}>
																		{{ $option->name }}
																	</option>
																@endforeach
															</select>
														</div>
														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</label>
																<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['flight_number_placeholder'] ?? 'flight_number_placeholder' }}" name="ticket_flight_info[0]transit[0][flight_number]" data-name="flight_number" value="{{ $transit->flight_number ?? '' }}"/>
															</div>
														</div>
														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['leaving_from_label'] ?? 'leaving_from_label' }}:</label>
																<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['leaving_from_placeholder'] ?? 'leaving_from_placeholder' }}" name="ticket_flight_info[0]transit[0][leaving_from]" data-name="leaving_from" value="{{ $transit->leaving_from ?? '' }}"/>
															</div>
														</div>

														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['departure_date_time_label'] ?? 'departure_date_time_label' }}:</label>
																<input type="text" placeholder="{{ $getCurrentTranslation['departure_date_time_placeholder'] ?? 'departure_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour" name="ticket_flight_info[0]transit[0][departure_date_time]" data-name="departure_date_time" value="{{ $transit->departure_date_time ?? '' }}"/>
															</div>
														</div>

														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['going_to_label'] ?? 'going_to_label' }}:</label>
																<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['going_to_placeholder'] ?? 'going_to_placeholder' }}" name="ticket_flight_info[0]transit[0][going_to]" data-name="going_to" value="{{ $transit->going_to ?? '' }}"/>
															</div>
														</div>

														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['arrival_date_time_label'] ?? 'arrival_date_time_label' }}:</label>
																<input type="text" placeholder="{{ $getCurrentTranslation['arrival_date_time_placeholder'] ?? 'arrival_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour" name="ticket_flight_info[0]transit[0][arrival_date_time]" data-name="arrival_date_time" value="{{ $transit->arrival_date_time ?? '' }}"/>
															</div>
														</div>
														<div class="col-md-4">
															<div class="form-item mb-5">
																<label class="form-label">{{ $getCurrentTranslation['total_fly_time_label'] ?? 'total_fly_time_label' }}:</label>
																<input type="text" class="form-control mb-2" placeholder="{{ $getCurrentTranslation['total_fly_time_placeholder'] ?? 'total_fly_time_placeholder' }}"  name="ticket_flight_info[0]transit[0][total_fly_time]" data-name="total_fly_time" value="{{ $transit->total_fly_time ?? '' }}"/>
															</div>
														</div>
													</div>
												</div>
											@endforeach 
										@else
											<div class="append-child-item">
												<div class="append-item-header d-flex justify-content-between">
													<h3 class="append-child-item-title">{{ $getCurrentTranslation['flight'] ?? 'flight' }} <span class="append-item-count"></span> {{ $getCurrentTranslation['transit'] ?? 'transit' }} <span class="append-child-item-count"></span></h3>
													<div class="append-child-item-toolbar d-flex justify-content-end">
														<button type="button" class="btn btn-sm btn-success append-child-item-add-btn me-2">
															<i class="fa-solid fa-plus"></i>
														</button>
														<button type="button" class="btn btn-sm btn-danger append-child-item-remove-btn" style="display: none">
															<i class="fa-solid fa-minus"></i>
														</button>
													</div>
												</div>

												<div class="row p-5">
													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['total_transit_time_label'] ?? 'total_transit_time_label' }}:</label>
															<input type="text" class="form-control mb-2" placeholder="{{ $getCurrentTranslation['total_transit_time_placeholder'] ?? 'total_transit_time_placeholder' }}" name="ticket_flight_info[0]transit[0][total_transit_time]" data-name="total_transit_time"/>
														</div>
													</div>
													<div class="col-md-4">
														<input type="hidden" name="ticket_flight_info[0]transit[0][flight_id]" data-name="flight_id" value="">
														@php
															$options = $airlines;
															$selected = null;
														@endphp
														<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
														<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="ticket_flight_info[0]transit[0][airline_id]" data-name="airline_id">
															<option value="">----</option>
															@foreach($options as $option)
																<option value="{{ $option->id }}" data-image="{{ $option->logo_url ?? defaultImage('s') }}" {{ $option->id == $selected ? 'selected' : '' }}>
																	{{ $option->name }}
																</option>
															@endforeach
														</select>
													</div>
													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['flight_number_placeholder'] ?? 'flight_number_placeholder' }}" name="ticket_flight_info[0]transit[0][flight_number]" data-name="flight_number" value="{{ $editData->flight_number ?? '' }}"/>
														</div>
													</div>
													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['leaving_from_label'] ?? 'leaving_from_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['leaving_from_placeholder'] ?? 'leaving_from_placeholder' }}" name="ticket_flight_info[0]transit[0][leaving_from]" data-name="leaving_from"/>
														</div>
													</div>

													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['departure_date_time_label'] ?? 'departure_date_time_label' }}:</label>
															<input type="text" placeholder="{{ $getCurrentTranslation['departure_date_time_placeholder'] ?? 'departure_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour" name="ticket_flight_info[0]transit[0][departure_date_time]" data-name="departure_date_time"/>
														</div>
													</div>

													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['going_to_label'] ?? 'going_to_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['going_to_placeholder'] ?? 'going_to_placeholder' }}" name="ticket_flight_info[0]transit[0][going_to]" data-name="going_to"/>
														</div>
													</div>

													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['arrival_date_time_label'] ?? 'arrival_date_time_label' }}:</label>
															<input type="text" placeholder="{{ $getCurrentTranslation['arrival_date_time_placeholder'] ?? 'arrival_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour" name="ticket_flight_info[0]transit[0][arrival_date_time]" data-name="arrival_date_time"/>
														</div>
													</div>
													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['total_fly_time_label'] ?? 'total_fly_time_label' }}:</label>
															<input type="text" class="form-control mb-2" placeholder="{{ $getCurrentTranslation['total_fly_time_placeholder'] ?? 'total_fly_time_placeholder' }}" name="ticket_flight_info[0]transit[0][total_fly_time]" data-name="total_fly_time"/>
														</div>
													</div>
												</div>
											</div>
										@endif
									</div>
								</div>
							@endforeach
						@else
							<div class="append-item rounded border p-5 mb-5">
								<div class="append-item-header one-way-trip"style="display: none">
									<div class="d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['one_way'] ?? 'one_way' }}</h3>
									</div>
								</div>

								<div class="append-item-header round-trip" style="display: none">
									<div class="d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['round_trip_outbound'] ?? 'round_trip_outbound' }}</h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
								</div>
								<div class="append-item-header multi-city-trip" style="display: none">
									<div class="d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['multi_city'] ?? 'multi_city' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
								</div>
								<div class="row p-5">
									<div class="col-md-4">
										<input type="hidden" name="ticket_flight_info[0][flight_id]" data-name="flight_id" value="">
										@php
											$options = $airlines;
											$selected = null;
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
										<select class="form-select select2-with-images parent-ip" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="ticket_flight_info[0][airline_id]" data-name="airline_id">
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" data-image="{{ $option->logo_url ?? defaultImage('s') }}" {{ $option->id == $selected ? 'selected' : '' }}>
													{{ $option->name }}
												</option>
											@endforeach
										</select>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</label>
											<input type="text" class="form-control parent-ip" placeholder="{{ $getCurrentTranslation['flight_number_placeholder'] ?? 'flight_number_placeholder' }}" name="ticket_flight_info[0][flight_number]" data-name="flight_number" value="{{ $editData->flight_number ?? '' }}"/>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['leaving_from_label'] ?? 'leaving_from_label' }}:</label>
											<input type="text" class="form-control parent-ip" placeholder="{{ $getCurrentTranslation['leaving_from_placeholder'] ?? 'leaving_from_placeholder' }}" name="ticket_flight_info[0][leaving_from]" data-name="leaving_from"/>
										</div>
									</div>

									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['departure_date_time_label'] ?? 'departure_date_time_label' }}:</label>
											<input type="text" placeholder="{{ $getCurrentTranslation['departure_date_time_placeholder'] ?? 'departure_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour parent-ip main-datetime" name="ticket_flight_info[0][departure_date_time]" data-name="departure_date_time"/>
										</div>
									</div>

									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['going_to_label'] ?? 'going_to_label' }}:</label>
											<input type="text" class="form-control parent-ip" placeholder="{{ $getCurrentTranslation['going_to_placeholder'] ?? 'going_to_placeholder' }}" name="ticket_flight_info[0][going_to]" data-name="going_to"/>
										</div>
									</div>

									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['arrival_date_time_label'] ?? 'arrival_date_time_label' }}:</label>
											<input type="text" placeholder="{{ $getCurrentTranslation['arrival_date_time_placeholder'] ?? 'arrival_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour parent-ip" name="ticket_flight_info[0][arrival_date_time]" data-name="arrival_date_time"/>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['total_fly_time_label'] ?? 'total_fly_time_label' }}:</label>
											<input type="text" class="form-control mb-2 parent-ip" placeholder="{{ $getCurrentTranslation['total_fly_time_placeholder'] ?? 'total_fly_time_placeholder' }}" name="ticket_flight_info[0][total_fly_time]" data-name="total_fly_time"/>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['is_transit_label'] ?? 'is_transit_label' }}:</label>
											<div class="form-check form-check-custom form-check-solid">
												<br>
												<label class="form-check-label usn cursor-pointer">
													<input class="form-check-input flight-transit parent-ip" type="checkbox" name="ticket_flight_info[0][is_transit]" data-name="is_transit" value="1">
													{{ $getCurrentTranslation['is_transit_checkbox_text'] ?? 'is_transit_checkbox_text' }}
												</label>
											</div>
										</div>
									</div>
								</div>

								<div class="card-body append-child-item-wrapper flight-transit-child-wrap" style="display: none">
									<div class="append-child-item">
										<div class="append-item-header d-flex justify-content-between">
											<h3 class="append-child-item-title">
												{{-- Flight <span class="append-item-count"></span>  --}}
												Transit <span class="append-child-item-count"></span></h3>
											<div class="append-child-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-success append-child-item-add-btn me-2">
													<i class="fa-solid fa-plus"></i>
												</button>
												<button type="button" class="btn btn-sm btn-danger append-child-item-remove-btn" style="display: none">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>

										<div class="row p-5">
											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['total_transit_time_label'] ?? 'total_transit_time_label' }}:</label>
													<input type="text" class="form-control mb-2" placeholder="{{ $getCurrentTranslation['total_transit_time_placeholder'] ?? 'total_transit_time_placeholder' }}" name="ticket_flight_info[0]transit[0][total_transit_time]" data-name="total_transit_time"/>
												</div>
											</div>
											
											<div class="col-md-4">
												<input type="hidden" name="ticket_flight_info[0]transit[0][flight_id]" data-name="flight_id" value="">
												@php
													$options = $airlines;
													$selected = null;
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
												<select class="form-select select2-with-images" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="ticket_flight_info[0]transit[0][airline_id]" data-name="airline_id">
													<option value="">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" data-image="{{ $option->logo_url ?? defaultImage('s') }}" {{ $option->id == $selected ? 'selected' : '' }}>
															{{ $option->name }}
														</option>
													@endforeach
												</select>
											</div>
											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['flight_number_placeholder'] ?? 'flight_number_placeholder' }}" name="ticket_flight_info[0]transit[0][flight_number]" data-name="flight_number" value="{{ $editData->flight_number ?? '' }}"/>
												</div>
											</div>
											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['leaving_from_label'] ?? 'leaving_from_label' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['leaving_from_placeholder'] ?? 'leaving_from_placeholder' }}" name="ticket_flight_info[0]transit[0][leaving_from]" data-name="leaving_from"/>
												</div>
											</div>

											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['departure_date_time_label'] ?? 'departure_date_time_label' }}:</label>
													<input type="text" placeholder="{{ $getCurrentTranslation['departure_date_time_placeholder'] ?? 'departure_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour" name="ticket_flight_info[0]transit[0][departure_date_time]" data-name="departure_date_time"/>
												</div>
											</div>

											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['going_to_label'] ?? 'going_to_label' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['going_to_placeholder'] ?? 'going_to_placeholder' }}" name="ticket_flight_info[0]transit[0][going_to]" data-name="going_to"/>
												</div>
											</div>

											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['arrival_date_time_label'] ?? 'arrival_date_time_label' }}:</label>
													<input type="text" placeholder="{{ $getCurrentTranslation['arrival_date_time_placeholder'] ?? 'arrival_date_time_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour" name="ticket_flight_info[0]transit[0][arrival_date_time]" data-name="arrival_date_time"/>
												</div>
											</div>

											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['total_fly_time_label'] ?? 'total_fly_time_label' }}:</label>
													<input type="text" class="form-control mb-2" placeholder="{{ $getCurrentTranslation['total_fly_time_placeholder'] ?? 'total_fly_time_placeholder' }}" name="ticket_flight_info[0]transit[0][total_fly_time]" data-name="total_fly_time" value="{{ $item->total_fly_time ?? '' }}"/>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						@endif
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['passengers_informations'] ?? 'passengers_informations' }}</h3>
						<div class="card-toolbar">
							<button type="button" class="btn btn-sm btn-success append-item-add-btn">
								<i class="fa-solid fa-plus"></i>
							</button>
						</div>
					</div>
					<div class="card-body append-item-wrapper">
						@if(isset($editData) && count($editData->passengers) > 0)
							@foreach($editData->passengers as $item)
								<div class="append-item rounded border p-5 mb-5">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['passenger'] ?? 'passenger' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
									<div class="row p-5">
										<input type="hidden" name="passenger_info[0][passenger_id]" value="{{ $item->id }}">
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['passenger_name_label'] ?? 'passenger_name_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['passenger_name_placeholder'] ?? 'passenger_name_placeholder' }}" name="passenger_info[0][name]" value="{{ $item->name }}"/>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['passenger_phone_label'] ?? 'passenger_phone_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['passenger_phone_placeholder'] ?? 'passenger_phone_placeholder' }}" name="passenger_info[0][phone]" value="{{ $item->phone }}"/>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['passenger_email_label'] ?? 'passenger_email_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['passenger_email_placeholder'] ?? 'passenger_email_placeholder' }}" name="passenger_info[0][email]" value="{{ $item->email }}"/>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												@php
													$options = ['Adult', 'Child', 'Infant'];

													$selected = $item->pax_type;
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['pax_type_placeholder'] ?? 'pax_type_placeholder' }}" name="passenger_info[0][pax_type]" >
													<option value="">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['ticket_price_label'] ?? 'ticket_price_label' }}:</label>
												<input type="text" class="form-control number-validate" placeholder="{{ $getCurrentTranslation['ticket_price_placeholder'] ?? 'ticket_price_placeholder' }}" name="passenger_info[0][ticket_price]" value="{{ $item->ticket_price }}"/>
											</div>
										</div>
										<div class="col-md-12">
											<div class="mb-5">
												<label class="form-label">{{ $getCurrentTranslation['baggage_allowance_label'] ?? 'baggage_allowance_label' }}:</label>
												<textarea class="form-control baggage-allowance-ip" name="passenger_info[0][baggage_allowance]" rows="2" placeholder="{{ $getCurrentTranslation['baggage_allowance_placeholder'] ?? 'baggage_allowance_placeholder' }}">{{ $item->baggage_allowance ?? getPreBaggageAllowance() }}</textarea>
											</div>
										</div>
									</div>

									@if(isset($item->flights) && count($item->flights) > 0)
										<div class="card-body append-child-item-wrapper pb-0">
											@foreach($item->flights as $flight)
											<div class="append-child-item">
												<div class="append-item-header d-flex justify-content-between">
													<h3 class="append-child-item-title">{{ $getCurrentTranslation['passenger'] ?? 'passenger' }} <span class="append-item-count"></span> {{ $getCurrentTranslation['ticket_info'] ?? 'ticket_info' }}  <span class="append-child-item-count"></span></h3>
													<div class="append-child-item-toolbar d-flex justify-content-end">
														<button type="button" class="btn btn-sm btn-success append-child-item-add-btn me-2">
															<i class="fa-solid fa-plus"></i>
														</button>
														<button type="button" class="btn btn-sm btn-danger append-child-item-remove-btn">
															<i class="fa-solid fa-minus"></i>
														</button>
													</div>
												</div>

												<div class="row p-5">
													{{-- <div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">Flight Number:</label>
															<input type="text" class="form-control" placeholder="Enter flight number" name="passenger_info[0][flight][0][flight_number]" value="{{ $flight->flight_number }}"/>
														</div>
													</div> --}}
													<div class="col-md-4">
														<input type="hidden" name="passenger_info[0][flight][0][passenger_flight_id]" value="{{ $flight->id }}">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['airlines_pnr_label'] ?? 'airlines_pnr_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['airlines_pnr_placeholder'] ?? 'airlines_pnr_placeholder' }}" name="passenger_info[0][flight][0][airlines_pnr]" value="{{ $flight->airlines_pnr }}"/>
														</div>
													</div>
													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['ticket_number_label'] ?? 'ticket_number_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['ticket_number_placeholder'] ?? 'ticket_number_placeholder' }}" name="passenger_info[0][flight][0][ticket_number]" value="{{ $flight->ticket_number }}"/>
														</div>
													</div>
												</div>
											</div>
											@endforeach
										</div>
									@else
										<div class="card-body append-child-item-wrapper pb-0">
											<div class="append-child-item">
												<div class="append-item-header d-flex justify-content-between">
													<h3 class="append-child-item-title">{{ $getCurrentTranslation['passenger'] ?? 'passenger' }} <span class="append-item-count"></span> {{ $getCurrentTranslation['ticket_info'] ?? 'ticket_info' }}  <span class="append-child-item-count"></span></h3>
													<div class="append-child-item-toolbar d-flex justify-content-end">
														<button type="button" class="btn btn-sm btn-success append-child-item-add-btn me-2">
															<i class="fa-solid fa-plus"></i>
														</button>
														<button type="button" class="btn btn-sm btn-danger append-child-item-remove-btn" style="display: none">
															<i class="fa-solid fa-minus"></i>
														</button>
													</div>
												</div>

												<div class="row p-5">
													{{-- <div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">Flight Number:</label>
															<input type="text" class="form-control" placeholder="Enter flight number" name="passenger_info[0][flight][0][flight_number]" ip-required value=""/>
														</div>
													</div> --}}
													<div class="col-md-4">
														<input type="hidden" name="passenger_info[0][flight][0][passenger_flight_id]" value="">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['airlines_pnr_label'] ?? 'airlines_pnr_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['airlines_pnr_placeholder'] ?? 'airlines_pnr_placeholder' }}" name="passenger_info[0][flight][0][airlines_pnr]" ip-required value=""/>
														</div>
													</div>
													<div class="col-md-4">
														<div class="form-item mb-5">
															<label class="form-label">{{ $getCurrentTranslation['ticket_number_label'] ?? 'ticket_number_label' }}:</label>
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['ticket_number_placeholder'] ?? 'ticket_number_placeholder' }}" name="passenger_info[0][flight][0][ticket_number]" value=""/>
														</div>
													</div>

												</div>
											</div>
										</div>
									@endif
								</div>
							@endforeach
						@else
							<div class="append-item rounded border p-5 mb-5">
								<div class="append-item-header d-flex justify-content-between">
									<h3 class="append-item-title">{{ $getCurrentTranslation['passenger'] ?? 'passenger' }} <span class="append-item-count"></span></h3>
									<div class="append-item-toolbar d-flex justify-content-end">
										<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
											<i class="fa-solid fa-minus"></i>
										</button>
									</div>
								</div>
								<div class="row p-5">
									<input type="hidden" name="passenger_info[0][passenger_id]" value="">
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['passenger_name_label'] ?? 'passenger_name_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['passenger_name_placeholder'] ?? 'passenger_name_placeholder' }}" name="passenger_info[0][name]" />
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['passenger_phone_label'] ?? 'passenger_phone_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['passenger_phone_placeholder'] ?? 'passenger_phone_placeholder' }}" name="passenger_info[0][phone]" value=""/>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['passenger_email_label'] ?? 'passenger_email_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['passenger_email_placeholder'] ?? 'passenger_email_placeholder' }}" name="passenger_info[0][email]" value=""/>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-item mb-5">
											@php
												$options = ['Adult', 'Child', 'Infant'];

												$selected = '';
											@endphp
											<label class="form-label">{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}:</label>
											<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['pax_type_placeholder'] ?? 'pax_type_placeholder' }}" name="passenger_info[0][pax_type]" >
												<option value="">----</option>
												@foreach($options as $option)
													<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
												@endforeach
											</select>
										</div>
									</div>
									{{-- <div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">Ticket Number:</label>
											<input type="text" class="form-control" placeholder="Enter ticket number" name="passenger_info[0][ticket_number]" />
										</div>
									</div> --}}
									<div class="col-md-4">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['ticket_price_label'] ?? 'ticket_price_label' }}:</label>
											<input type="text" class="form-control number-validate" placeholder="{{ $getCurrentTranslation['ticket_price_placeholder'] ?? 'ticket_price_placeholder' }}" name="passenger_info[0][ticket_price]" />
										</div>
									</div>
									

									<div class="col-md-12">
										<div class="mb-5">
											<label class="form-label">{{ $getCurrentTranslation['baggage_allowance_label'] ?? 'baggage_allowance_label' }}:</label>
											<textarea class="form-control baggage-allowance-ip" name="passenger_info[0][baggage_allowance]" rows="2" placeholder="{{ $getCurrentTranslation['baggage_allowance_placeholder'] ?? 'baggage_allowance_placeholder' }}">{{ getPreBaggageAllowance() }}</textarea>
										</div>
									</div>

								</div>

								<div class="card-body append-child-item-wrapper pb-0">
									<div class="append-child-item">
										<div class="append-item-header d-flex justify-content-between">
											<h3 class="append-child-item-title">{{ $getCurrentTranslation['passenger'] ?? 'passenger' }} <span class="append-item-count"></span> {{ $getCurrentTranslation['ticket_info'] ?? 'ticket_info' }}  <span class="append-child-item-count"></span></h3>
											<div class="append-child-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-success append-child-item-add-btn me-2">
													<i class="fa-solid fa-plus"></i>
												</button>
												<button type="button" class="btn btn-sm btn-danger append-child-item-remove-btn" style="display: none">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>

										<div class="row p-5">
											{{-- <div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">Flight Number:</label>
													<input type="text" class="form-control" placeholder="Enter flight number" name="passenger_info[0][flight][0][flight_number]" ip-required value=""/>
												</div>
											</div> --}}
											<div class="col-md-4">
												<input type="hidden" name="passenger_info[0][flight][0][passenger_flight_id]" value="">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['airlines_pnr_label'] ?? 'airlines_pnr_label' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['airlines_pnr_placeholder'] ?? 'airlines_pnr_placeholder' }}" name="passenger_info[0][flight][0][airlines_pnr]" ip-required value=""/>
												</div>
											</div>
											<div class="col-md-4">
												<div class="form-item mb-5">
													<label class="form-label">{{ $getCurrentTranslation['ticket_number_label'] ?? 'ticket_number_label' }}:</label>
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['ticket_number_placeholder'] ?? 'ticket_number_placeholder' }}" name="passenger_info[0][flight][0][ticket_number]" value=""/>
												</div>
											</div>

										</div>
									</div>
								</div>
							</div>
						@endif
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['fare_summary'] ?? 'fare_summary' }}</h3>
						<div class="card-toolbar">
							<button type="button" class="btn btn-sm btn-success append-item-add-btn">
								<i class="fa-solid fa-plus"></i>
							</button>
						</div>
					</div>
					<div class="card-body append-item-wrapper">
						@if(isset($editData) && count($editData->fareSummary) > 0)
							@foreach($editData->fareSummary as $item)
								<div class="append-item">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['fare'] ?? 'fare' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
									<div class="row p-5">
										<input type="hidden" name="fare_summary[0][fare_summary_id]" value="{{ $item->id }}">
										<div class="col-3">
											<div class="form-item mb-5">
												@php
													$options = ['Adult', 'Child', 'Infant'];

													$selected = $item->pax_type;
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['pax_type_placeholder'] ?? 'pax_type_placeholder' }}" name="fare_summary[0][pax_type]" >
													<option value="">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-3">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['ticket_unit_price_label'] ?? 'ticket_unit_price_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '' }}):</label>
												<input type="text" class="form-control number-validate calc-input ticket-unit-price" placeholder="0.00" name="fare_summary[0][unit_price]" value="{{ $item->unit_price }}"/>
											</div>
										</div>
										<div class="col-3">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['pax_count_label'] ?? 'pax_count_label' }}:</label>
												<input type="text" class="form-control integer-validate calc-input pax-count" placeholder="0" name="fare_summary[0][pax_count]" value="{{ $item->pax_count }}"/>
											</div>
										</div>
										<div class="col-3">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['total_label'] ?? 'total_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '' }}):</label>
												<input type="text" class="form-control number-validate calc-input total-price" placeholder="0.00" name="fare_summary[0][total]" value="{{ $item->total }}"/>
											</div>
										</div>

									</div>
								</div>
							@endforeach
						@else
							<div class="append-item">
								<div class="append-item-header d-flex justify-content-between">
									<h3 class="append-item-title">{{ $getCurrentTranslation['fare'] ?? 'fare' }} <span class="append-item-count"></span></h3>
									<div class="append-item-toolbar d-flex justify-content-end">
										<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
											<i class="fa-solid fa-minus"></i>
										</button>
									</div>
								</div>
								<div class="row p-5">
									<input type="hidden" name="fare_summary[0][fare_summary_id]" value="">
									<div class="col-3">
										<div class="form-item mb-5">
											@php
												$options = ['Adult', 'Child', 'Infant'];

												$selected = '';
											@endphp
											<label class="form-label">{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}:</label>
											<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['pax_type_placeholder'] ?? 'pax_type_placeholder' }}" name="fare_summary[0][pax_type]" >
												<option value="">----</option>
												@foreach($options as $option)
													<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
												@endforeach
											</select>
										</div>
									</div>
									<div class="col-3">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['ticket_unit_price_label'] ?? 'ticket_unit_price_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '' }}):</label>
											<input type="text" class="form-control number-validate calc-input ticket-unit-price" placeholder="0.00" name="fare_summary[0][unit_price]" />
										</div>
									</div>
									<div class="col-3">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['pax_count_label'] ?? 'pax_count_label' }}:</label>
											<input type="text" class="form-control integer-validate calc-input pax-count" placeholder="0" name="fare_summary[0][pax_count]" />
										</div>
									</div>
									<div class="col-3">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['total_label'] ?? 'total_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '' }}):</label>
											<input type="text" class="form-control number-validate calc-input total-price" placeholder="0.00" name="fare_summary[0][total]" />
										</div>
									</div>

								</div>
							</div>
						@endif
					</div>
					<div class="row m-0 card-body pt-0 justify-content-end">
						<div class="col-4">
							<div class="fare-summary-footer">
								@php
									//$fareSummary = $editData->fareSummary ?? null;
								@endphp
								<table class="table table-rounded table-striped border gs-5 vertical-align-baseline">
									<tr>
										<th>{{ $getCurrentTranslation['subtotal_label'] ?? 'subtotal_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '' }})</th>
										<td>
											<input type="text" class="form-control number-validate calc-input subtotal" placeholder="0.00" name="subtotal" value="{{ $editData->fareSummary[0]->subtotal ?? '0.00' }}"/>
										</td>
									</tr>
									<tr>
										<th>{{ $getCurrentTranslation['discount_label'] ?? 'discount_label' }}(-)</th>
										<td>
											<input type="text" class="form-control number-validate calc-input discount" placeholder="0.00" name="discount" value="{{ $editData->fareSummary[0]->discount ?? '0.00' }}"/>
										</td>
									</tr>
									<tr>
										<th>{{ $getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '' }})</th>
										<td>
											<input type="text" class="form-control number-validate grandtotal" placeholder="0.00" name="grandtotal" value="{{ $editData->fareSummary[0]->grandtotal ?? '0.00' }}"/>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['billing_informations'] ?? 'billing_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-item mb-5">
									@php
										$options = ['Company', 'Individual'];

										$selected = $editData->bill_to ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['bill_to_label'] ?? 'bill_to_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['bill_to_placeholder'] ?? 'bill_to_placeholder' }}" name="bill_to">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
								</div>
							</div>
							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['bill_to_info_label'] ?? 'bill_to_info_label' }}: <br>
										<small class="text-info">{{ $getCurrentTranslation['bill_to_info_helper_text'] ?? 'bill_to_info_helper_text' }}</small>
									</label>
									<textarea class="form-control ck-editor" name="bill_to_info" rows="3">{{ old('bill_to_info') ?? $editData->bill_to_info ?? '' }}</textarea>
									@error('bill_to_info')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['footer_informations'] ?? 'footer_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['footer_title_label'] ?? 'footer_title_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['footer_title_placeholder'] ?? 'footer_title_placeholder' }}" class="form-control"  name="footer_title"  value="{{ old('footer_title') ?? $editData->footer_title ?? 'CONDITIONS AND IMPORTANT NOTICE:' }}"/>
									@error('footer_title')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['footer_text_label'] ?? 'footer_text_label' }}:</label>
									<textarea class="form-control ck-editor" name="footer_text" rows="3">{{ old('footer_text') ?? $editData->footer_text ?? getPreFooterDetails() }}</textarea>
									@error('footer_text')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['payment_informations'] ?? 'payment_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['bank_details_label'] ?? 'bank_details_label' }}:</label>
									<textarea class="form-control ck-editor" name="bank_details" rows="3">{{ old('bank_details') ?? $editData->bank_details ?? '' }}</textarea>
									@error('bank_details')
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
						
						// Store search parameters and full response for later use
						window.flightSearchParams = response.search_params || {};
						window.flightSearchResponse = response.data || null;
						
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

		// Function to format API flight data to match ticket form structure
		function formatFlightDataForForm(flightData, searchParams, fullResponse) {
			const formattedData = {
				trip_type: '',
				ticket_flight_info: []
			};
			
			// Determine trip type
			if (searchParams.flight_type === 'one_way') {
				formattedData.trip_type = 'One Way';
			} else if (searchParams.flight_type === 'round_trip') {
				formattedData.trip_type = 'Round Trip';
			} else if (searchParams.flight_type === 'multi_city') {
				formattedData.trip_type = 'Multi City';
			}
			
			// Helper function to find airline ID by name or IATA code
			// Note: This will be resolved on the ticket form page where airlines are available
			function findAirlineId(airlineName) {
				// Return the airline name/code - will be matched on ticket form
				return airlineName || null;
			}
			
			// Helper function to format date time
			function formatDateTime(dateStr, timeStr) {
				if (!dateStr) return '';
				const date = new Date(dateStr);
				if (isNaN(date.getTime())) return '';
				
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, '0');
				const day = String(date.getDate()).padStart(2, '0');
				const hours = timeStr ? String(timeStr.split(':')[0] || '00').padStart(2, '0') : '00';
				const minutes = timeStr ? String(timeStr.split(':')[1] || '00').padStart(2, '0') : '00';
				
				return `${year}-${month}-${day} ${hours}:${minutes}`;
			}
			
			// Helper function to calculate duration
			function calculateDuration(departure, arrival) {
				if (!departure || !arrival) return '';
				const dep = new Date(departure);
				const arr = new Date(arrival);
				if (isNaN(dep.getTime()) || isNaN(arr.getTime())) return '';
				
				const diff = arr - dep;
				const hours = Math.floor(diff / (1000 * 60 * 60));
				const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
				return `${hours}h ${minutes}m`;
			}
			
			// Process flight data based on type
			if (searchParams.flight_type === 'one_way') {
				// Single flight
				const departureDate = flightData.departure_at || flightData.search_departure_at || searchParams.departure_at;
				const arrivalDate = flightData.return_at || flightData.arrival_at || flightData.departure_at;
				
				const flight = {
					airline_id: findAirlineId(flightData.airline) || '',
					flight_number: flightData.flight_number || flightData.airline || '',
					leaving_from: flightData.search_origin || flightData.origin || searchParams.origin || '',
					going_to: flightData.search_destination || flightData.destination || searchParams.destination || '',
					departure_date_time: formatDateTime(departureDate),
					arrival_date_time: formatDateTime(arrivalDate),
					total_fly_time: calculateDuration(departureDate, arrivalDate),
					is_transit: 0,
					transit: []
				};
				
				// Check if there are segments (transits) in the route
				// Travelpayouts API might return route information
				if (flightData.route && Array.isArray(flightData.route) && flightData.route.length > 2) {
					flight.is_transit = 1;
					flight.transit = [];
					
					// Process route segments as transits (skip first and last as they are origin and destination)
					for (let i = 1; i < flightData.route.length - 1; i++) {
						const routeSegment = flightData.route[i];
						flight.transit.push({
							airline_id: findAirlineId(flightData.airline) || '',
							flight_number: flightData.flight_number || '',
							leaving_from: routeSegment || '',
							going_to: flightData.route[i + 1] || flightData.search_destination || '',
							departure_date_time: formatDateTime(departureDate),
							arrival_date_time: formatDateTime(arrivalDate),
							total_fly_time: '',
							total_transit_time: ''
						});
					}
				}
				
				formattedData.ticket_flight_info.push(flight);
				
			} else if (searchParams.flight_type === 'round_trip') {
				// Outbound flight
				const outboundFlight = {
					airline_id: findAirlineId(flightData.airline) || '',
					flight_number: flightData.flight_number || '',
					leaving_from: flightData.search_origin || flightData.origin || searchParams.origin || '',
					going_to: flightData.search_destination || flightData.destination || searchParams.destination || '',
					departure_date_time: formatDateTime(flightData.departure_at || flightData.search_departure_at || searchParams.departure_at),
					arrival_date_time: formatDateTime(flightData.return_at || flightData.arrival_at),
					total_fly_time: calculateDuration(
						flightData.departure_at || flightData.search_departure_at || searchParams.departure_at,
						flightData.return_at || flightData.arrival_at
					),
					is_transit: 0,
					transit: []
				};
				
				formattedData.ticket_flight_info.push(outboundFlight);
				
				// Return flight
				if (flightData.return_at || searchParams.return_at) {
					const returnFlight = {
						airline_id: findAirlineId(flightData.return_airline || flightData.airline) || '',
						flight_number: flightData.return_flight_number || flightData.flight_number || '',
						leaving_from: flightData.search_destination || flightData.destination || searchParams.destination || '',
						going_to: flightData.search_origin || flightData.origin || searchParams.origin || '',
						departure_date_time: formatDateTime(flightData.return_at || searchParams.return_at),
						arrival_date_time: formatDateTime(flightData.return_arrival_at),
						total_fly_time: calculateDuration(
							flightData.return_at || searchParams.return_at,
							flightData.return_arrival_at
						),
						is_transit: 0,
						transit: []
					};
					
					formattedData.ticket_flight_info.push(returnFlight);
				}
				
			} else if (searchParams.flight_type === 'multi_city') {
				// Multi-city flights - check if response has segments array
				if (fullResponse && fullResponse.segments && Array.isArray(fullResponse.segments)) {
					// Response from multi-city search with segments
					fullResponse.segments.forEach((segmentData, index) => {
						if (segmentData.data && Array.isArray(segmentData.data) && segmentData.data.length > 0) {
							// Use first flight from segment results
							const segmentFlight = segmentData.data[0];
							const flight = {
								airline_id: findAirlineId(segmentFlight.airline) || '',
								flight_number: segmentFlight.flight_number || segmentFlight.airline || '',
								leaving_from: segmentData.origin || segmentFlight.origin || '',
								going_to: segmentData.destination || segmentFlight.destination || '',
								departure_date_time: formatDateTime(segmentData.departure_at || segmentFlight.departure_at),
								arrival_date_time: formatDateTime(segmentFlight.return_at || segmentFlight.departure_at),
								total_fly_time: calculateDuration(
									segmentData.departure_at || segmentFlight.departure_at,
									segmentFlight.return_at || segmentFlight.departure_at
								),
								is_transit: 0,
								transit: []
							};
							
							formattedData.ticket_flight_info.push(flight);
						}
					});
				} else if (flightData.segments && Array.isArray(flightData.segments)) {
					// Flight data has segments array
					flightData.segments.forEach((segment, index) => {
						const flight = {
							airline_id: findAirlineId(segment.airline || flightData.airline) || '',
							flight_number: segment.flight_number || flightData.flight_number || '',
							leaving_from: segment.origin || segment.segment_origin || '',
							going_to: segment.destination || segment.segment_destination || '',
							departure_date_time: formatDateTime(segment.departure_at || segment.departure_at),
							arrival_date_time: formatDateTime(segment.arrival_at),
							total_fly_time: calculateDuration(segment.departure_at, segment.arrival_at),
							is_transit: 0,
							transit: []
						};
						
						formattedData.ticket_flight_info.push(flight);
					});
				} else {
					// Fallback to single flight
					const flight = {
						airline_id: findAirlineId(flightData.airline) || '',
						flight_number: flightData.flight_number || '',
						leaving_from: flightData.search_origin || flightData.origin || searchParams.origin || '',
						going_to: flightData.search_destination || flightData.destination || searchParams.destination || '',
						departure_date_time: formatDateTime(flightData.departure_at || flightData.search_departure_at || searchParams.departure_at),
						arrival_date_time: formatDateTime(flightData.return_at || flightData.arrival_at),
						total_fly_time: calculateDuration(
							flightData.departure_at || flightData.search_departure_at || searchParams.departure_at,
							flightData.return_at || flightData.arrival_at
						),
						is_transit: 0,
						transit: []
					};
					
					formattedData.ticket_flight_info.push(flight);
				}
			}
			
			return formattedData;
		}
		
		// Handle flight selection - auto-fill form on same page
		$(document).on('click', '.select-flight', function() {
			const flightData = $(this).data('flight');
			const searchParams = window.flightSearchParams || {};
			
			console.log('Selected flight:', flightData);
			console.log('Search params:', searchParams);
			
			// Show loading
			$('.r-preloader').show();
			
			// Process flight data on backend
			$.ajax({
				url: '{{ route("ticket.search.process.flight") }}',
				method: 'POST',
				data: {
					flight_data: flightData,
					search_params: searchParams,
					_token: '{{ csrf_token() }}'
				},
				success: function(response) {
					$('.r-preloader').hide();
					
					if (response.success && response.data) {
						console.log('Processed flight data:', response.data);
						
						// Auto-fill the form on the same page
						autoFillTicketFormFromSearch(response.data);
						
						// Smooth scroll to ticket form
						setTimeout(function() {
							$('html, body').animate({
								scrollTop: $('.trip-flight').offset().top - 100
							}, 800);
						}, 300);
						
						// Show success message
						Swal.fire({
							icon: 'success',
							title: '{{ $getCurrentTranslation["flight_selected"] ?? "flight_selected" }}',
							text: '{{ $getCurrentTranslation["flight_data_loaded"] ?? "Flight data has been loaded successfully" }}',
							timer: 2000,
							showConfirmButton: false
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: '{{ $getCurrentTranslation["error"] ?? "error" }}',
							text: response.message || '{{ $getCurrentTranslation["something_went_wrong"] ?? "something_went_wrong" }}'
						});
					}
				},
				error: function(xhr) {
					$('.r-preloader').hide();
					
					let errorMessage = '{{ $getCurrentTranslation["something_went_wrong"] ?? "something_went_wrong" }}';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMessage = xhr.responseJSON.message;
					}
					
					Swal.fire({
						icon: 'error',
						title: '{{ $getCurrentTranslation["error"] ?? "error" }}',
						text: errorMessage
					});
				}
			});
		});
		
		// Function to auto-fill ticket form from processed flight data
		function autoFillTicketFormFromSearch(data) {
			// Set trip type
			if (data.trip_type) {
				$('[name="trip_type"]').val(data.trip_type).trigger('change');
			}
			
			// Set ticket type (class)
			if (data.ticket_type) {
				$('[name="ticket_type"]').val(data.ticket_type).trigger('change');
			}
			
			// Wait for trip type change to complete
			setTimeout(function() {
				// Process each flight
				if (data.ticket_flight_info && Array.isArray(data.ticket_flight_info)) {
					// Clear existing flight rows (keep first one)
					const $flightContainer = $('.trip-flight .append-item-wrapper');
					$flightContainer.find('.append-item:not(:first)').remove();
					
					// Ensure correct number of flight rows
					while ($flightContainer.find('.append-item').length < data.ticket_flight_info.length) {
						$('.trip-flight .append-item-add-btn').data('programmatic', true).click();
					}
					// If there are more rows than needed, remove the excess ones from the end
					while ($flightContainer.find('.append-item').length > data.ticket_flight_info.length) {
						$flightContainer.find('.append-item').last().remove();
					}
					
					data.ticket_flight_info.forEach(function(flightInfo, index) {
						setTimeout(function() {
							const flightIndex = index;
							const flightRow = $flightContainer.find(`.append-item`).eq(flightIndex);
							
							// Fill flight data
							if (flightInfo.airline_id) {
								const airlineSelect = flightRow.find(`[name="ticket_flight_info[${flightIndex}][airline_id]"]`);
								if (airlineSelect.length) {
									airlineSelect.val(flightInfo.airline_id).trigger('change');
								}
							}
							if (flightInfo.flight_number) {
								flightRow.find(`[name="ticket_flight_info[${flightIndex}][flight_number]"]`).val(flightInfo.flight_number);
							}
							if (flightInfo.leaving_from) {
								flightRow.find(`[name="ticket_flight_info[${flightIndex}][leaving_from]"]`).val(flightInfo.leaving_from);
							}
							if (flightInfo.going_to) {
								flightRow.find(`[name="ticket_flight_info[${flightIndex}][going_to]"]`).val(flightInfo.going_to);
							}
							if (flightInfo.departure_date_time) {
								flightRow.find(`[name="ticket_flight_info[${flightIndex}][departure_date_time]"]`).val(flightInfo.departure_date_time).trigger('change');
							}
							if (flightInfo.arrival_date_time) {
								flightRow.find(`[name="ticket_flight_info[${flightIndex}][arrival_date_time]"]`).val(flightInfo.arrival_date_time).trigger('change');
							}
							if (flightInfo.total_fly_time) {
								flightRow.find(`[name="ticket_flight_info[${flightIndex}][total_fly_time]"]`).val(flightInfo.total_fly_time);
							}
							
							// Handle transit
							if (flightInfo.is_transit == 1 && flightInfo.transit && Array.isArray(flightInfo.transit) && flightInfo.transit.length > 0) {
								// Check transit checkbox
								const $transitCheckbox = flightRow.find(`[name="ticket_flight_info[${flightIndex}][is_transit]"]`);
								if (!$transitCheckbox.is(':checked')) {
									$transitCheckbox.prop('checked', true).trigger('change');
								}
								
								// Wait for transit wrapper to show
								setTimeout(function() {
									const $transitContainer = flightRow.find('.flight-transit-child-wrap .append-child-item-wrapper');
									$transitContainer.find('.append-child-item:not(:first)').remove(); // Clear existing transits
									
									// Ensure correct number of transit rows
									while ($transitContainer.find('.append-child-item').length < flightInfo.transit.length) {
										flightRow.find('.flight-transit-child-wrap .append-child-item-add-btn').data('programmatic', true).click();
									}
									while ($transitContainer.find('.append-child-item').length > flightInfo.transit.length) {
										$transitContainer.find('.append-child-item').last().remove();
									}
									
									flightInfo.transit.forEach(function(transitInfo, transitIndex) {
										setTimeout(function() {
											const transitRow = $transitContainer.find(`.append-child-item`).eq(transitIndex);
											
											// Fill transit data
											if (transitInfo.airline_id) {
												const transitAirlineSelect = transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][airline_id]"]`);
												if (transitAirlineSelect.length) {
													transitAirlineSelect.val(transitInfo.airline_id).trigger('change');
												}
											}
											if (transitInfo.flight_number) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][flight_number]"]`).val(transitInfo.flight_number);
											}
											if (transitInfo.leaving_from) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][leaving_from]"]`).val(transitInfo.leaving_from);
											}
											if (transitInfo.going_to) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][going_to]"]`).val(transitInfo.going_to);
											}
											if (transitInfo.departure_date_time) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][departure_date_time]"]`).val(transitInfo.departure_date_time).trigger('change');
											}
											if (transitInfo.arrival_date_time) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][arrival_date_time]"]`).val(transitInfo.arrival_date_time).trigger('change');
											}
											if (transitInfo.total_fly_time) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][total_fly_time]"]`).val(transitInfo.total_fly_time);
											}
											if (transitInfo.total_transit_time) {
												transitRow.find(`[name="ticket_flight_info[${flightIndex}]transit[${transitIndex}][total_transit_time]"]`).val(transitInfo.total_transit_time);
											}
										}, transitIndex * 200);
									});
								}, 300);
							} else {
								// Uncheck transit if not needed
								const $transitCheckbox = flightRow.find(`[name="ticket_flight_info[${flightIndex}][is_transit]"]`);
								if ($transitCheckbox.is(':checked')) {
									$transitCheckbox.prop('checked', false).trigger('change');
								}
							}
						}, index * 300);
					});
					
					// Reinitialize date pickers and select2 for newly added/updated fields
					setTimeout(function() {
						initializeDatePickers();
						// Reinitialize select2 for airline selects
						$('.select2-with-images').each(function() {
							if (!$(this).hasClass('select2-hidden-accessible')) {
								$(this).select2();
							}
						});
					}, 1000);
				}
			}, 500);
		}
	});
</script>

<script>
	$(document).on('click', '.flight-transit', function(){
		resetFlightTransit();
	});
	

	$(document).on('input', '.calc-input', function () {
		calculateFare();
	});

	function resetTripType(){
		var tripType = $('[name="trip_type"]').val();

		$('.trip-flight .append-item-header').slideUp();

		if(tripType === 'One Way'){
			$('.append-item-header.one-way-trip').slideDown();

			if($('.trip-flight .append-item').length > 1){
				$('.trip-flight .append-item').last().remove();
			}
		} else if(tripType === 'Round Trip'){
			$('.trip-flight .append-item-add-btn').data('programmatic', true).click();
			setTimeout(() => {
				$('.trip-flight .append-item-header').slideUp();
				$('.append-item-header.round-trip').slideDown();
				$('.trip-flight .card-toolbar .btn').slideUp();
				$('.trip-flight .append-item-toolbar .btn').slideUp();
				$('.trip-flight .append-item').last().find('.append-item-title').text('Round Trip Return');
			}, 1000);
		} else if(tripType === 'Multi City'){
			$('.append-item-header.multi-city-trip').slideDown();
			$('.trip-flight .card-toolbar .btn').slideDown();
			$('.trip-flight .append-item-toolbar .btn').slideDown();
			@if(!isset($editData))
				if($('.trip-flight .append-item').length > 1){
					$('.trip-flight .append-item').last().remove();
				}
			@endif
		}
	}


	resetTripType();

	$(document).on('change', '[name="trip_type"]', function () {
		resetTripType();
	});

	@if(!isset($editData))
		$(document).on('input change', '.parent-ip', function () {
			var thisName = $(this).attr('data-name');
			var thisValue = $(this).val();

			// Update all same data-name inputs, excluding going_to and leaving_from
			if (thisName !== 'leaving_from' && thisName !== 'going_to') {
				$(`[data-name="${thisName}"]`).not(this).val(thisValue);

				if($(this).hasClass('select2') || $(this).hasClass('select2-with-images')){
					$(`[data-name="${thisName}"]`).not(this).val(thisValue).trigger('change');

				}

				if($(this).hasClass('append-datepicker')){
					$(`[data-name="${thisName}`).closest('div').find('.append-datepicker').val(thisValue);
				}
				
			}

			// If this is a going_to input, copy its value to the next leaving_from
			if (thisName === 'going_to') {
				let goingToInputs = $('[data-name="going_to"]');
				let leavingFromInputs = $('[data-name="leaving_from"]');

				goingToInputs.each(function(index) {
					let value = $(this).val();
					let nextLeavingFrom = leavingFromInputs.eq(index + 1); // next in sequence
					let previousLeavingFrom = leavingFromInputs.eq(index); // current one (previous for next)

					if (nextLeavingFrom.length) {
						if (value) {
							nextLeavingFrom.val(value);
						} else {
							// If value is empty, retain the previous value
							nextLeavingFrom.val(previousLeavingFrom.val());
						}
					}
				});
			}

		});

		$(document).on('input change', '[data-name="going_to"]', function () {
			let currentInput = $(this);
			let goingToInputs = $('[data-name="going_to"]');
			let leavingFromInputs = $('[data-name="leaving_from"]');

			let startIndex = goingToInputs.index(currentInput); // Start from this going_to

			for (let i = startIndex; i < goingToInputs.length; i++) {
				let value = goingToInputs.eq(i).val();
				let nextLeavingFrom = leavingFromInputs.eq(i + 1);
				let currentLeavingFrom = leavingFromInputs.eq(i);

				if (nextLeavingFrom.length) {
					if (value) {
						nextLeavingFrom.val(value);
					} else {
						nextLeavingFrom.val(currentLeavingFrom.val());
					}
				}
			}
		});
	@endif

</script>

@endpush