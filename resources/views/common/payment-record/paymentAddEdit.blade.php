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
						<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_payment'] ?? 'edit_payment' }}</li>
					@else
						<li class="breadcrumb-item">{{ $getCurrentTranslation['create_payment'] ?? 'create_payment' }}</li>
					@endif
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@if(hasPermission('payment.show') && isset($editData) && !empty($editData))
                    <a href="{{ route('payment.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
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
						<h3 class="card-title">{{ $getCurrentTranslation['invoice_informations'] ?? 'invoice_informations' }}</h3>
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
									<label class="form-label">{{ $getCurrentTranslation['payment_invoice_id_label'] ?? 'payment_invoice_id_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['payment_invoice_id_placeholder'] ?? 'payment_invoice_id' }}" name="payment_invoice_id" value="{{ $editData->payment_invoice_id ?? generateInvoiceId('payments') }}" />
									@error('payment_invoice_id')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$selected = $editData->ticket_id ?? '';
										$options = getWhereInModelData('Ticket', 'id', [$selected]);
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['ticket_label'] ?? 'ticket_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['ticket_placeholder'] ?? 'ticket_placeholder' }}" name="ticket_id" >
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->invoice_id }} ({{ $option->reservation_number }})</option>
										@endforeach
									</select>
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
									<label class="form-label">{{ $getCurrentTranslation['client_name_label'] ?? 'client_name_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['client_name_placeholder'] ?? 'client_name_placeholder' }}" class="form-control mb-2"  name="client_name" value="{{ $editData->client_name ?? '' }}" />
									@error('client_name')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['client_phone_label'] ?? 'client_phone_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['client_phone_placeholder'] ?? 'client_phone_placeholder' }}" class="form-control mb-2"  name="client_phone" value="{{ $editData->client_phone ?? '' }}" />
									@error('client_phone')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['client_email_label'] ?? 'client_email_label' }}:</label>
									<input type="email" placeholder="{{ $getCurrentTranslation['client_email_placeholder'] ?? 'client_email_placeholder' }}" class="form-control mb-2"  name="client_email" value="{{ $editData->client_email ?? '' }}" />
									@error('client_email')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('IntroductionSource', 'status', [1]);
											$selected = $editData->introduction_source_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['introduction_source_label'] ?? 'introduction_source_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['introduction_source_placeholder'] ?? 'introduction_source_placeholder' }}" name="introduction_source_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>

									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('introductionSource.create.ajax')}}', 'introduction_source_id', '{{ $getCurrentTranslation['introduction_source'] ?? 'introduction_source'}}')" {{ !hasPermission('introductionSource') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>

							
							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = getAllModelData('Country');

										$selected = $editData->customer_country_id ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['customer_country_label'] ?? 'customer_country_label' }}:</label>
									<select class="form-select select2-with-images" data-class="flag" data-placeholder="{{ $getCurrentTranslation['customer_country_placeholder'] ?? 'customer_country_placeholder' }}" name="customer_country_id" >
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option->id }}" data-image="{{ getStaticFile('flags', strtolower($option->short_name))}}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
										@endforeach
									</select>
								</div>
							</div>

							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											// Get all active issued suppliers
											$options = getWhereInModelData('IssuedSupplier', 'status', [1]);

											// Selected IDs should be an array (from editData)
											$selected = $editData->issued_supplier_ids ?? [];
											if (!is_array($selected)) {
												$selected = json_decode($selected, true) ?? [];
											}
										@endphp

										<label class="form-label">
											{{ $getCurrentTranslation['issued_supplier_label'] ?? 'issued_supplier_label' }}:
										</label>

										<select class="form-select" data-control="select2"
												data-placeholder="{{ $getCurrentTranslation['issued_supplier_placeholder'] ?? 'issued_supplier_placeholder' }}"
												name="issued_supplier_ids[]" multiple>
											@foreach($options as $option)
												<option value="{{ $option->id }}" 
													{{ in_array($option->id, $selected) ? 'selected' : '' }}>
													{{ $option->name }}
												</option>
											@endforeach
										</select>
									</div>

									<button type="button" 
											class="btn btn-success btn-sm" 
											onclick="openAjaxCreateModal('{{ route('issuedSupplier.create.ajax') }}', 'issued_supplier_ids[]', '{{ $getCurrentTranslation['issued_supplier'] ?? 'issued_supplier' }}')"
											{{ !hasPermission('issuedSupplier') ? 'disabled' : '' }}>
										<i class="fa-solid fa-plus"></i>
									</button>
								</div>
							</div>


							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('IssuedBy', 'status', [1]);
											$selected = $editData->issued_by_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['issued_by_label'] ?? 'issued_by_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['issued_by_placeholder'] ?? 'issued_by_placeholder' }}" name="issued_by_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>
									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('issuedBy.create.ajax')}}', 'issued_by_id', '{{ $getCurrentTranslation['issued_by'] ?? 'issued_by' }}')" {{ !hasPermission('issuedBy') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>
							
						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['documents'] ?? 'documents' }}</h3>
						<div class="card-toolbar">
							<button type="button" class="btn btn-sm btn-success append-item-add-btn">
								<i class="fa-solid fa-plus"></i>
							</button>
						</div>
					</div>
					<div class="card-body append-item-wrapper">
						@php
							$documents = $editData->paymentDocuments ?? collect();
						@endphp

						@if($documents->count())
							@foreach($documents as $index => $doc)
								{{-- @php
									$fileUrl = $doc->file_full_url;
									$extension = strtolower(pathinfo($doc->file_url, PATHINFO_EXTENSION));
									$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
									$isImage = in_array($extension, $imageExtensions);
									$isPdf = in_array($extension, ['pdf']);
									$isDocx = in_array($extension, ['docx']);
								@endphp --}}

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
										{{-- <div class="col-10">
											<!-- File input to allow replacement -->
											<div class="form-item mt-2">
												<input type="hidden" name="documents[0][id]" value="{{ $doc->id }}">
												<label class="form-label">{{ $getCurrentTranslation['file_label'] ?? 'file_label' }}:</label>
												<input type="file" class="form-control" name="documents[0][file]" accept=".heic,.jpeg,.jpg,.png,.pdf,.doc,.docx"/>
											</div>

											@if($isImage)
												<div class="append-prev mf-prev hover-effect mt-2" data-src="{{ $fileUrl }}">
													<img src="{{ $fileUrl }}" alt="Document" style="max-height:100px; max-width:100%; object-fit:contain;">
												</div>
											@else
												<a class="append-prev file-prev-thumb mt-2" href="{{ $fileUrl }}" target="_blank" 
												onclick="return confirm('Are you sure you want to download this file?');" download>
													@if($isPdf)
														<i class="fas fa-file-pdf"></i>
													@else
														<i class="fas fa-file-alt"></i>
													@endif
												</a>
											@endif
										</div> --}}

										<div class="col-10">
											<div class="input-item-wrap mt-2">
												<input type="hidden" name="documents[0][id]" value="{{ $doc->id }}">
												<label class="form-label">{{ $getCurrentTranslation['file_label'] ?? 'file_label' }}:</label>
												@php
													$selected = $doc->file_full_url ?? '';

													$isFileExist = false;
													if (isset($selected) && !empty($selected)) {
														if (!empty($selected)) {
															$isFileExist = true;
														}
													}

													$fileUrl = $selected;
													$fileName = basename($fileUrl);
													$extension = strtolower(pathinfo($doc->file_url, PATHINFO_EXTENSION));
													$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
													$isImage = in_array($extension, $imageExtensions);
													$isPdf = in_array($extension, ['pdf']);
													$isDocx = in_array($extension, ['docx']);

												@endphp
												<div class="file-input-box">
													<input name="documents[0][file]" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.jpg,.png,.pdf,.doc,.docx" {{ empty($selected) ? '' : '' }}>
												</div>
												<div class="preview-image mf-prev hover-effect mt-2" data-src="{{ $selected ? $selected : '' }}" style="{{ $selected ? '' : 'display: none;' }}">
													<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img fixed-value ml-2" width="100" alt='You selected "{{ $fileName }}"'>
												</div>

												{{-- <a class="append-prev file-prev-thumb mt-2" href="{{ $fileUrl }}" target="_blank" 
												onclick="return confirm('Are you sure you want to download this file?');" download style="{{ $selected && !$isImage ? '' : 'display: none;' }}">
													@if($isPdf)
														<i class="fas fa-file-pdf"></i>
													@else
														<i class="fas fa-file-alt"></i>
													@endif
												</a> --}}
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
									<div class="col-10">
										<div class="input-item-wrap mt-2">
											<label class="form-label">{{ $getCurrentTranslation['file_label'] ?? 'file_label' }}:</label>
											@php
												$selected = '';
											@endphp
											<div class="file-input-box">
												<input name="documents[0][file]" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.jpg,.png,.pdf,.doc,.docx" {{ empty($selected) ? '' : '' }}>
											</div>
											<div class="preview-image mf-prev hover-effect mt-2" data-src="{{ $selected ? $selected : '' }}" style="{{ $selected ? '' : 'display: none;' }}">
												<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img fixed-value ml-2" width="100">
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
						<h3 class="card-title">{{ $getCurrentTranslation['trip_informations'] ?? 'trip_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">

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
									<label class="form-label">{{ $getCurrentTranslation['departure_date_time_label'] ?? 'departure_date_time_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['departure_date_time_placeholder'] ?? 'departure_date_time_placeholder' }}" class="form-control mb-2 flatpickr-input datetime 12-hour" name="departure_date_time" value="{{ $editData->departure_date_time ?? '' }}"/>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['return_date_time_label'] ?? 'return_date_time_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['return_date_time_placeholder'] ?? 'return_date_time_placeholder' }}" class="form-control mb-2 flatpickr-input datetime 12-hour" name="return_date_time" value="{{ $editData->return_date_time ?? '' }}"/>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['departure_city_label'] ?? 'departure_city_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['departure_placeholder'] ?? 'departure_placeholder' }}" class="form-control mb-2" name="departure" value="{{ $editData->departure ?? '' }}"/>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['destination_city_label'] ?? 'destination_city_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['destination_placeholder'] ?? 'destination_placeholder' }}" class="form-control mb-2" name="destination" value="{{ $editData->destination ?? '' }}"/>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['flight_route_label'] ?? 'flight_route_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['flight_route_placeholder'] ?? 'flight_route_placeholder' }}" class="form-control mb-2" name="flight_route" value="{{ $editData->flight_route ?? '' }}"/>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Window', 'Aisle', 'Not Chosen'];

										$selected = $editData->seat_confirmation ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['seat_confirmation_label'] ?? 'seat_confirmation_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['seat_confirmation_placeholder'] ?? 'seat_confirmation_placeholder' }}" name="seat_confirmation">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('seat_confirmation')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Wheelchair', 'Baby Bassinet Seat', 'Meet & Assist', 'Not Chosen'];

										$selected = $editData->mobility_assistance ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['mobility_assistance_label'] ?? 'mobility_assistance_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['mobility_assistance_placeholder'] ?? 'mobility_assistance_placeholder' }}" name="mobility_assistance">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('mobility_assistance')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('Airline', 'status', [1]);
											$selected = $editData->airline_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="airline_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>
									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('admin.airline.create.ajax')}}', 'airline_id', '{{ $getCurrentTranslation['airline'] ?? 'airline' }}')" {{ !hasPermission('airline.create') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Need To Do', 'Done', 'No Need'];

										$selected = $editData->transit_visa_application ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['transit_visa_application_label'] ?? 'transit_visa_application_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['transit_visa_application_placeholder'] ?? 'transit_visa_application_placeholder' }}" name="transit_visa_application">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('transit_visa_application')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Need To Do', 'Done', 'No Need'];

										$selected = $editData->halal_meal_request ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['halal_meal_request_label'] ?? 'halal_meal_request_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['halal_meal_request_placeholder'] ?? 'halal_meal_request_placeholder' }}" name="halal_meal_request">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('halal_meal_request')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Need To Do', 'Done', 'No Need'];

										$selected = $editData->transit_hotel ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['transit_hotel_label'] ?? 'transit_hotel_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['transit_hotel_placeholder'] ?? 'transit_hotel_placeholder' }}" name="transit_hotel">
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('transit_hotel')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-12">
								<div class="form-item mb-5">
									@php
										$selected = $editData->note ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['note_label'] ?? 'note_label' }}:</label>
									<textarea class="form-control" name="note" rows="3">{{ old('note') ?? $editData->note ?? '' }}</textarea>
									@error('note')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['card_owner_informations'] ?? 'card_owner_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('TransferTo', 'status', [1]);
											$selected = $editData->transfer_to_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['transfer_to_label'] ?? 'transfer_to_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['transfer_to_placeholder'] ?? 'transfer_to_placeholder' }}" name="transfer_to_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>

									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('transferTo.create.ajax')}}', 'transfer_to_id', '{{ $getCurrentTranslation['transfer_to'] ?? 'transfer_to' }}')" {{ !hasPermission('transferTo') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>

							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('PaymentMethod', 'status', [1]);
											$selected = $editData->payment_method_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['payment_method_label'] ?? 'payment_method_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['payment_method_placeholder'] ?? 'payment_method_placeholder' }}" name="payment_method_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>

									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('paymentMethod.create.ajax')}}', 'payment_method_id', '{{ $getCurrentTranslation['payment_method'] ?? 'payment_method' }}')" {{ !hasPermission('paymentMethod') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>


							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('IssuedCardType', 'status', [1]);
											$selected = $editData->issued_card_type_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['issued_card_type_label'] ?? 'issued_card_type_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['issued_card_type_placeholder'] ?? 'issued_card_type_placeholder' }}" name="issued_card_type_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>

									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('issuedCardType.create.ajax')}}', 'issued_card_type_id', '{{ $getCurrentTranslation['issued_card_type'] ?? 'issued_card_type' }}')" {{ !hasPermission('issuedCardType') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>

							<div class="col-md-4">
								<div class="input-item-with-create-btn mb-5">
									<div class="input-item">
										@php
											$options = getWhereInModelData('CardOwner', 'status', [1]);
											$selected = $editData->card_owner_id ?? '';
										@endphp
										<label class="form-label">{{ $getCurrentTranslation['card_owner_label'] ?? 'card_owner_label' }}:</label>
										<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['card_owner_placeholder'] ?? 'card_owner_placeholder' }}" name="card_owner_id" >
											<option value="">----</option>
											@foreach($options as $option)
												<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
											@endforeach
										</select>
									</div>

									<button type="button" class="btn btn-success btn-sm" onclick="openAjaxCreateModal('{{route('cardOwner.create.ajax')}}', 'card_owner_id', '{{ $getCurrentTranslation['card_owner'] ?? 'card_owner' }}')" {{ !hasPermission('cardOwner') ? 'disabled' : '' }}><i class="fa-solid fa-plus"></i></button>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['card_digit_label'] ?? 'card_digit_label' }}:</label>
									<input type="email" placeholder="{{ $getCurrentTranslation['card_digit_placeholder'] ?? 'card_digit_placeholder' }}" class="form-control mb-2"  name="card_digit" value="{{ $editData->card_digit ?? '' }}" />
									@error('card_digit')
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
							<div class="col-6">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['total_purchase_price_label'] ?? 'total_purchase_price_label' }}
										({{Auth::user()->company_data->currency->short_name ?? ''}})
										:
									</label>
									<input type="text" class="form-control number-validate calc-input ticket-purchase-price" placeholder="0.00" name="total_purchase_price" value="{{ $editData->total_purchase_price ?? '' }}"/>
								</div>
							</div>
							
							<div class="col-6">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['total_selling_price_label'] ?? 'total_selling_price_label' }}
										({{Auth::user()->company_data->currency->short_name ?? ''}})
										:
									</label>
									<input type="text" class="form-control number-validate calc-input ticket-selling-price" placeholder="0.00" name="total_selling_price" value="{{ $editData->total_selling_price ?? '' }}"/>
								</div>
							</div>
						</div>
						<div class="card rounded border mt-5 bg-white append-item-container">
							<div class="card-header">
								<h3 class="card-title">{{ $getCurrentTranslation['payment_collection'] ?? 'payment_collection' }}</h3>
								<div class="card-toolbar">
									<button type="button" class="btn btn-sm btn-success append-item-add-btn">
										<i class="fa-solid fa-plus"></i>
									</button>
								</div>
							</div>
							<div class="card-body append-item-wrapper">
								@if(!empty($editData->paymentData) && is_array($editData->paymentData))
									@foreach($editData->paymentData as $index => $payment)
										<div class="append-item">
											<div class="append-item-header d-flex justify-content-between">
												<h3 class="append-item-title">
													{{ $getCurrentTranslation['payment'] ?? 'payment' }}
													<span class="append-item-count">{{ $loop->iteration }}</span>
												</h3>
												<div class="append-item-toolbar d-flex justify-content-end">
													<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
														<i class="fa-solid fa-minus"></i>
													</button>
												</div>
											</div>

											<div class="row p-5">
												<div class="col-5">
													<div class="form-item mb-5">
														<label class="form-label">
															{{ $getCurrentTranslation['paid_amount_label'] ?? 'paid_amount_label' }}
															({{Auth::user()->company_data->currency->short_name ?? ''}})
															:
														</label>
														<input 
															type="text"
															class="form-control number-validate calc-input ticket-paid-amount"
															placeholder="0.00"
															name="paymentData[{{ $index }}][paid_amount]"
															value="{{ $payment['paid_amount'] ?? '' }}"
														/>
													</div>
												</div>

												<div class="col-5">
													<div class="form-item mb-5">
														<label class="form-label">{{ $getCurrentTranslation['date_label'] ?? 'date_label' }}:</label>
														<input 
															type="text"
															placeholder="{{ $getCurrentTranslation['date_placeholder'] ?? 'date_placeholder' }}"
															class="form-control mb-2 append-datepicker flatpickr-input date"
															name="paymentData[{{ $index }}][date]"
															value="{{ $payment['date'] ?? '' }}"
														/>
													</div>
												</div>
											</div>
										</div>
									@endforeach
								@else
									<div class="append-item">
										<div class="append-item-header d-flex justify-content-between">
											<h3 class="append-item-title">{{ $getCurrentTranslation['payment'] ?? 'payment' }} <span class="append-item-count"></span></h3>
											<div class="append-item-toolbar d-flex justify-content-end">
												<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
													<i class="fa-solid fa-minus"></i>
												</button>
											</div>
										</div>
										<div class="row p-5">
											<div class="col-5">
												<div class="form-item mb-5">
													<label class="form-label">
														{{ $getCurrentTranslation['paid_amount_label'] ?? 'paid_amount_label' }}
														({{Auth::user()->company_data->currency->short_name ?? ''}})
														:
													</label>
													<input type="text" class="form-control number-validate calc-input ticket-paid-amount" placeholder="0.00" name="paymentData[0][paid_amount]" />
												</div>
											</div>
											<div class="col-5">
												<div class="form-item mb-5">
													<label class="form-label">
														{{ $getCurrentTranslation['date_label'] ?? 'date_label' }}:
													</label>
													<input type="text" placeholder="{{ $getCurrentTranslation['date_placeholder'] ?? 'date_placeholder' }}" class="form-control mb-2 append-datepicker flatpickr-input date" name="paymentData[0][date]" value=""/>
												</div>
											</div>

										</div>
									</div>
								@endif
							</div>
							<div class="card-body pt-0">
								<div class="remaining-due-balance text-danger text-end">
									<h3 class="text-danger">
										{{ $getCurrentTranslation['remaining_due'] ?? 'remaining_due' }}:
										{{Auth::user()->company_data->currency->short_name ?? ''}} <span class="number">0.00</span>
									</h3>
								</div>
							</div>
						</div>
						<div class="row mt-5">
							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['next_payment_deadline_label'] ?? 'next_payment_deadline_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['next_payment_deadline_placeholder'] ?? 'next_payment_deadline_placeholder' }}" class="form-control mb-2 flatpickr-input date" name="next_payment_deadline" value="{{ $editData->next_payment_deadline ?? '' }}"/>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-item mb-5">
									@php
										$options = ['Unpaid', 'Paid', 'Partial', 'Unknown'];
	
										$selected = $editData->payment_status ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['payment_status_label'] ?? 'payment_status_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['payment_status_placeholder'] ?? 'payment_status_placeholder' }}" name="payment_status" >
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				@if(isset($editData))
				<div class="card rounded border mt-5 bg-white refund-data-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['refund'] ?? 'refund' }}</h3>
						<div class="card-toolbar">
							<button type="button" class="btn btn-sm btn-secondary clear-refund-data-btn">
								<i class="fas fa-broom"></i>
								{{ $getCurrentTranslation['clear'] ?? 'clear' }}
							</button>
						</div>
					</div>
					<div class="card-body">
						<div class="row">

							<div class="col-4">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['cancellation_fee_label'] ?? 'cancellation_fee_label' }}
										({{Auth::user()->company_data->currency->short_name ?? ''}})
										:
									</label>
									<input type="text" class="form-control number-validate calc-input cancellation-fee" placeholder="0.00" name="cancellation_fee" value="{{ $editData->cancellation_fee ?? '' }}"/>
								</div>
							</div>

							<div class="col-4">
								<div class="form-item mb-5">
									<label class="form-label">
										{{ $getCurrentTranslation['service_fee_label'] ?? 'service_fee_label' }}
										({{Auth::user()->company_data->currency->short_name ?? ''}})
										:
									</label>
									<input type="text" class="form-control number-validate calc-input service-fee" placeholder="0.00" name="service_fee" value="{{ $editData->service_fee ?? '' }}"/>
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Unpaid', 'Paid'];

										$selected = $editData->refund_payment_status ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['refund_payment_status_label'] ?? 'refund_payment_status_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['refund_payment_status_placeholder'] ?? 'refund_payment_status_placeholder' }}" name="refund_payment_status">
										<option value="0">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('refund_payment_status')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-12">
								<div class="form-item mb-5">
									@php
										$selected = $editData->refund_note ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['refund_note_label'] ?? 'refund_note_label' }}:</label>
									<textarea class="form-control" name="refund_note" rows="3">{{ old('refund_note') ?? $editData->refund_note ?? '' }}</textarea>
									@error('refund_note')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>
				@endif


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
	let searchRequest; // store ongoing AJAX request

	$(document).on('select2:open', '[name="ticket_id"]', function () {
		const $select = $(this);

		const attachSearchListener = () => {
			const searchInput = document.querySelector('.select2-container--open .select2-search__field');
			const resultsList = document.querySelector('.select2-container--open .select2-results__options');

			if (!searchInput || !resultsList) {
				// Retry until Select2 has rendered the input and results area
				return setTimeout(attachSearchListener, 50);
			}

			// Unbind previous listener to avoid duplicates
			searchInput.removeEventListener('input', searchInput._ticketSearchListener);

			// Define the live search handler
			searchInput._ticketSearchListener = async function () {
				const searchVal = this.value.trim();

				// Only trigger when 3+ chars
				if (searchVal.length < 3) {
					resultsList.innerHTML = `<li class="select2-results__option" role="option">Type at least 3 characters...</li>`;
					return;
				}

				// Abort previous AJAX request if still running
				if (searchRequest) searchRequest.abort();

				$('.r-preloader').show();

				searchRequest = $.ajax({
					url: "{{ route('payment.ticket.search') }}",
					method: 'GET',
					data: { search: searchVal },
					dataType: 'json',
					success: function (response) {
						$('.r-preloader').hide();

						resultsList.innerHTML = ''; // clear previous results

						if (response.is_success && Array.isArray(response.ticketData) && response.ticketData.length) {
							response.ticketData.forEach(ticket => {
								const text = `${ticket.invoice_id} (${ticket.reservation_number})`;

								// Build dropdown list items manually
								const li = document.createElement('li');
								li.className = 'select2-results__option';
								li.setAttribute('role', 'option');
								li.textContent = text;
								li.dataset.value = ticket.id;

								// When user clicks the option, set it as selected
								li.addEventListener('mousedown', () => {
									const optionExists = $select.find(`option[value="${ticket.id}"]`).length > 0;
									const jsonDetails = JSON.stringify(ticket).replace(/"/g, '&quot;'); // escape quotes for HTML

									if (!optionExists) {
										// Create a new <option> with full ticket details in data-details attribute
										const newOption = new Option(text, ticket.id, true, true);
										$(newOption).attr('data-details', jsonDetails);
										$select.append(newOption);
									} else {
										// Update data-details if it already exists
										$select.find(`option[value="${ticket.id}"]`).attr('data-details', jsonDetails);
									}

									$select.val(ticket.id).trigger('change');
									$select.select2('close');
								});

								resultsList.appendChild(li);
							});

						} else {
							resultsList.innerHTML = `<li class="select2-results__option" role="option">No results found.</li>`;
						}
					},
					error: function (xhr, status) {
						$('.r-preloader').hide();

						if (status === 'abort') return;

						if (xhr.status === 419) {
							Swal.fire({
								icon: 'error',
								title: getCurrentTranslation.csrf_token_mismatch ?? 'CSRF Token Mismatch',
								text: getCurrentTranslation.csrf_token_mismatch_msg ?? 'Your session has expired. Please reload the page.',
								confirmButtonText: getCurrentTranslation.yes_reload_page ?? 'Reload Page'
							}).then(() => location.reload());
							return;
						}

						resultsList.innerHTML = `<li class="select2-results__option" role="option">Error loading results.</li>`;

						Swal.fire(
							getCurrentTranslation.error ?? 'Error',
							getCurrentTranslation.something_went_wrong ?? 'Something went wrong. Please try again.',
							'error'
						);
					}
				});
			};

			// Bind the input event
			searchInput.addEventListener('input', searchInput._ticketSearchListener);
		};

		attachSearchListener();
	});


	$(document).on('change', '[name="ticket_id"]', function(){
		$('.r-preloader').show();

		const selectedOption = $(this).find(':selected');
		const dataDetails = selectedOption.attr('data-details');

		if (dataDetails) {
			// Parse and show JSON in readable format
			const ticketData = JSON.parse(dataDetails.replace(/&quot;/g, '"'));
			//console.log(ticketData); // shows as full JSON object

			const today = new Date();
			const formattedToday = today.getFullYear() + '-' +
				String(today.getMonth() + 1).padStart(2, '0') + '-' +
				String(today.getDate()).padStart(2, '0');

			let invoiceDate = ticketData.invoice_date ? new Date(ticketData.invoice_date) : today;

			// Format both cases as YYYY-MM-DD
			const formattedInvoiceDate = invoiceDate.getFullYear() + '-' +
				String(invoiceDate.getMonth() + 1).padStart(2, '0') + '-' +
				String(invoiceDate.getDate()).padStart(2, '0');

			$('[name="invoice_date"]').closest('div').find('input').val(formattedInvoiceDate);

			$('[name="client_name"]').val(ticketData.passengers?.[0]?.name ?? '');
			$('[name="client_phone"]').val(ticketData.passengers?.[0]?.phone ?? '');
			$('[name="client_email"]').val(ticketData.passengers?.[0]?.email ?? '');

			$('[name="trip_type"]').val(ticketData.trip_type).trigger('change');


			const flights = ticketData.flights ?? [];
			if (flights.length === 0) return;

			const firstFlight = flights[0];
			const lastFlight = flights[flights.length - 1];

			// === Set Departure Date & Time ===
			$('[name="departure_date_time"]').closest('div').find('.flatpickr-input')
			.val(firstFlight?.departure_date_time ?? '').trigger('change');

			// === Determine Final Arrival Time ===
			let finalDetermineTime = lastFlight?.departure_date_time ?? '';
			let finalArrivalTime = lastFlight?.arrival_date_time ?? '';
			if (lastFlight?.transits?.length > 0) {
			const lastTransit = lastFlight.transits[lastFlight.transits.length - 1];
			//finalArrivalTime = lastTransit?.arrival_date_time ?? finalArrivalTime;
			}

			if(ticketData.trip_type == 'Round Trip' && firstFlight?.departure_date_time != finalDetermineTime){
				$('[name="return_date_time"]').closest('div').find('.flatpickr-input').val(finalDetermineTime).trigger('change');
			}else{
				$('[name="return_date_time"]').closest('div').find('.flatpickr-input').val('').trigger('change');
			}
			

			// === Build full route in strict travel order ===
			let routeParts = [];

			// start with the very first departure city
			routeParts.push(extractPrimaryCity(firstFlight.leaving_from) || '');

			// For each main flight: push its direct destination first, then its transits in order.
			// Use last-pushed check to avoid consecutive duplicates while preserving order.
			flights.forEach(flight => {
			const mainDest = extractPrimaryCity(flight.going_to);
			if (mainDest && routeParts[routeParts.length - 1] !== mainDest) {
				routeParts.push(mainDest);
			}

			if (Array.isArray(flight.transits) && flight.transits.length > 0) {
				flight.transits.forEach(transit => {
				const tDest = extractPrimaryCity(transit.going_to);
				if (tDest && routeParts[routeParts.length - 1] !== tDest) {
					routeParts.push(tDest);
				}
				});
			}
			});

			// clean any falsy entries and join
			const cleaned = routeParts.filter(Boolean);
			const fullRoute = cleaned.join(' - ');

			// === Set Form Fields ===
			$('[name="departure"]').val(cleaned[0] ?? '');
			$('[name="destination"]').val(cleaned[cleaned.length - 1] ?? '');
			$('[name="flight_route"]').val(fullRoute);
			$('[name="airline_id"]').val(flights?.[0]?.airline_id ?? '').trigger('change');


			$('.ticket-selling-price').val(ticketData.fare_summary?.[0]?.grandtotal ?? '');
		}

		calculatePaymentAmount();

		$('.r-preloader').hide();
	});

	calculatePaymentAmount();
	$(document).on('input', '.calc-input', function() {
		calculatePaymentAmount();

	});


	function calculatePaymentAmount() {
		let sellingPrice = parseFloat($('.ticket-selling-price').val()) || 0;
		let paidAmount = 0;

		$('.ticket-paid-amount').each(function() {
			let val = $(this).val().trim();

			if (val !== '' && !isNaN(val)) {
				paidAmount += parseFloat(val);
			}

			// Toggle datepicker requirement based on payment presence
			if (parseFloat(val) > 0) {
				$(this).closest('.append-item').find('.append-datepicker').attr('ip-required', 'ip-required');
			} else {
				$(this).closest('.append-item').find('.append-datepicker').removeAttr('ip-required');
			}
		});

		// Calculate remaining (prevent negatives)
		let remaining = sellingPrice - paidAmount;

		// Update remaining balance text
		$('.remaining-due-balance .number').text(remaining.toFixed(2));

		// Determine payment status
		if (remaining === 0) {
			$('[name="payment_status"]').val('Paid').trigger('change');
		} else if (remaining === sellingPrice) {
			$('[name="payment_status"]').val('Unpaid').trigger('change');
		} else if (remaining < sellingPrice && remaining > 0) {
			$('[name="payment_status"]').val('Partial').trigger('change');
		}

		let paymentStatus = $('[name="payment_status"]').find('option:selected').val() || $('[name="payment_status"]').val();
		// Toggle required attribute based on payment status
		if (paymentStatus === 'Paid') {
			$('[name="payment_status"]').removeAttr('ip-required');
			$('[name="next_payment_deadline"]').closest('div').find('.flatpickr-input').removeAttr('ip-required');
		} else {
			$('[name="next_payment_deadline"]').closest('div').find('.flatpickr-input').attr('ip-required', 'ip-required');
		}
	}


	$(document).on('click', '.clear-refund-data-btn', function () {
		var refundContainer = $('.refund-data-container');

		Swal.fire({
			title: '{{ $getCurrentTranslation['are_you_sure'] ?? 'are_you_sure' }}',
			text: '{{ $getCurrentTranslation['clear_refund_data_warning'] ?? 'clear_refund_data_warning' }}',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#3085d6',
			confirmButtonText: '{{ $getCurrentTranslation['yes_clear_it'] ?? 'yes_clear_it' }}',
			cancelButtonText: '{{ $getCurrentTranslation['cancel'] ?? 'cancel' }}'
		}).then((result) => {
			if (result.isConfirmed) {
				// Clear input and textarea values except .fixed-data
				refundContainer.find('input:not(.fixed-data), textarea:not(.fixed-data)').val('');

				// Reset select elements to their first option and trigger change
				refundContainer.find('select:not(.fixed-data)').each(function () {
					$(this).val($(this).find('option:first').val()).trigger('change');
				});

				Swal.fire({
					title: '{{ $getCurrentTranslation['cleared'] ?? 'cleared' }}',
					text: '{{ $getCurrentTranslation['refund_data_cleared_message'] ?? 'refund_data_cleared_message' }}',
					icon: 'success',
					timer: 1500,
					showConfirmButton: false
				});
			}
		});
	});




</script>
@endpush