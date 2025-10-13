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
							<button type="submit" class="btn btn-primary form-submit-btn ajax-submit append-submit">
								@if(isset($editData))
								<span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
								@else
								<span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
								@endif
							</button>
						</div>
					</div>
					<div class="card-header text-center p-3 w-100" style="min-height: auto;">
						<b class="d-block w-100 text-danger">{{ $getCurrentTranslation['hotel_invoice_form_note'] ?? 'hotel_invoice_form_note' }}</b>
					</div>
					<div class="card-body">
						<div class="row">
							{{-- <div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['invoice_date_placeholder'] ?? 'invoice_date_placeholder' }}" class="form-control mb-2 flatpickr-input"  name="invoice_date" value="{{ $editData->invoice_date ?? '' }}" ip-required/>
									@error('invoice_date')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div> --}}
							
							{{-- <div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['invoice_id_placeholder'] ?? 'invoice_id_placeholder' }}" name="invoice_id" value="{{ $editData->invoice_id ?? generateInvoiceId() }}" ip-required/>
									@error('invoice_id')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div> --}}

							{{-- <div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['website_name_label'] ?? 'website_name_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['website_name_placeholder'] ?? 'website_name_placeholder' }}" name="website_name" ip-required value="{{ $editData->website_name ?? '' }}"/>
									@error('website_name')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div> --}}

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['pin_number_label'] ?? 'pin_number_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['pin_number_placeholder'] ?? 'pin_number_placeholder' }}" name="pin_number" ip-required value="{{ $editData->pin_number ?? '' }}"/>
									@error('pin_number')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['booking_number_label'] ?? 'booking_number_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['booking_number_placeholder'] ?? 'booking_number_placeholder' }}" name="booking_number" ip-required value="{{ $editData->booking_number ?? '' }}"/>
									@error('booking_number')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['hotel_informations'] ?? 'hotel_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-6">
								<div class="input-item-wrap mb-5">
									<label>{{ $getCurrentTranslation['hotel_image'] ?? 'hotel_image' }}:</label>
									@php
										$selected = old('hotel_image') ?? ($editData->hotel_image_url ?? '');

										$isFileExist = false;
										if (isset($selected) && !empty($selected)) {
											if (!empty($selected)) {
												$isFileExist = true;
											}
										}

									@endphp
									<div class="file-input-box">
										<input name="hotel_image" class="form-control image-input" type="file" max-size="0" accept=".heic,.jpeg,.png,.jpg" {{ empty($selected) ? '' : '' }}>
									</div>
									<div class="preview-image">
										<img old-selected="{{ $selected ? $selected : '' }}" src="{{ $selected ? $selected : '' }}" class="preview-img mt-2 ml-2" width="100" style="{{ $selected ? '' : 'display: none;' }}">
									</div>
									@error('hotel_image')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['hotel_name_label'] ?? 'hotel_name_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['hotel_name_placeholder'] ?? 'hotel_name_placeholder' }}" name="hotel_name" ip-required value="{{ $editData->hotel_name ?? '' }}"/>
									@error('hotel_name')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							{{-- <div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['hotel_phone_label'] ?? 'hotel_phone_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['hotel_phone_placeholder'] ?? 'hotel_phone_placeholder' }}" name="hotel_phone" ip-required value="{{ $editData->hotel_phone ?? '' }}"/>
									@error('hotel_phone')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div> --}}

							{{-- <div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['hotel_email_label'] ?? 'hotel_email_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['hotel_email_placeholder'] ?? 'hotel_email_placeholder' }}" name="hotel_email" ip-required value="{{ $editData->hotel_email ?? '' }}"/>
									@error('hotel_email')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div> --}}

							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['hotel_address_label'] ?? 'hotel_address_label' }}:</label>
									<textarea class="form-control" placeholder="{{ $getCurrentTranslation['hotel_address_placeholder'] ?? 'hotel_address_placeholder' }}" name="hotel_address" ip-required rows="4">{{ $editData->hotel_address ?? '' }}</textarea>
									@error('hotel_address')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['check_in_date_label'] ?? 'check_in_date_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['check_in_date_placeholder'] ?? 'check_in_date_placeholder' }}" class="form-control mb-2 flatpickr-input"  name="check_in_date" value="{{ $editData->check_in_date ?? '' }}"/>
									@error('check_in_date')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['check_in_time_label'] ?? 'check_in_time_label' }} ({{ $getCurrentTranslation['after'] ?? 'after' }}):</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['check_in_time_placeholder'] ?? 'check_in_time_placeholder' }}" class="form-control mb-2 flatpickr-input-time"  name="check_in_time" value="{{ $editData->check_in_time ?? '' }}"/>
									@error('check_in_time')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['check_out_date_label'] ?? 'check_out_date_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['check_out_date_placeholder'] ?? 'check_out_date_placeholder' }}" class="form-control mb-2 flatpickr-input"  name="check_out_date" value="{{ $editData->check_out_date ?? '' }}"/>
									@error('check_out_date')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['check_out_time_label'] ?? 'check_out_time_label' }} ({{ $getCurrentTranslation['before'] ?? 'before' }}):</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['check_out_time_placeholder'] ?? 'check_out_time_placeholder' }}" class="form-control mb-2 flatpickr-input-time"  name="check_out_time" value="{{ $editData->check_out_time ?? '' }}"/>
									@error('check_out_time')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['room_type_label'] ?? 'room_type_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['room_type_placeholder'] ?? 'room_type_placeholder' }}" name="room_type" ip-required value="{{ $editData->room_type ?? '' }}"/>
									@error('room_type')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['total_room_label'] ?? 'total_room_label' }}:</label>
									<input type="text" class="form-control integer-validate" placeholder="{{ $getCurrentTranslation['total_room_placeholder'] ?? 'total_room_placeholder' }}" name="total_room" ip-required value="{{ $editData->total_room ?? '' }}"/>
									@error('total_room')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['total_night_label'] ?? 'total_night_label' }}:</label>
									<input type="text" class="form-control integer-validate" placeholder="{{ $getCurrentTranslation['total_night_placeholder'] ?? 'total_night_placeholder' }}" name="total_night" ip-required value="{{ $editData->total_night ?? '' }}"/>
									@error('total_night')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['occupancy_info_label'] ?? 'occupancy_info_label' }}:</label>
									<textarea class="form-control" placeholder="{{ $getCurrentTranslation['occupancy_info_placeholder'] ?? 'occupancy_info_placeholder' }}" name="occupancy_info" ip-required rows="3">{{ $editData->occupancy_info ?? (getPrefillHotelData()['occupancy_info'] ?? '') }}</textarea>
									@error('occupancy_info')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['room_info_label'] ?? 'room_info_label' }}:</label>
									<textarea class="form-control" placeholder="{{ $getCurrentTranslation['room_info_placeholder'] ?? 'room_info_placeholder' }}" name="room_info" ip-required rows="3">{{ $editData->room_info ?? (getPrefillHotelData()['room_info'] ?? '') }}</textarea>
									@error('room_info')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['meal_info_label'] ?? 'meal_info_label' }}:</label>
									<textarea class="form-control" placeholder="{{ $getCurrentTranslation['meal_info_placeholder'] ?? 'meal_info_placeholder' }}" name="meal_info" ip-required rows="3">{{ $editData->meal_info ?? (getPrefillHotelData()['meal_info'] ?? '') }}</textarea>
									@error('meal_info')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label mb-0">{{ $getCurrentTranslation['room_amenities_label'] ?? 'room_amenities_label' }}:</label> <br>
									<small class="mb-2">({{ $getCurrentTranslation['room_amenities_note'] ?? 'room_amenities_note' }})</small>
									<textarea class="form-control" placeholder="{{ $getCurrentTranslation['room_amenities_placeholder'] ?? 'room_amenities_placeholder' }}" name="room_amenities" ip-required rows="3">{{ $editData->room_amenities ?? (getPrefillHotelData()['room_amenities'] ?? '') }}</textarea>
									@error('room_amenities')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['price_details'] ?? 'price_details' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['total_price_label'] ?? 'total_price_label' }} ({{ $globalData->company->currency->short_name ?? 'N/A' }}):</label>
									<input type="text" class="form-control number-validate" placeholder="{{ $getCurrentTranslation['total_price_placeholder'] ?? 'total_price_placeholder' }}" name="total_price" ip-required value="{{ $editData->total_price ?? '' }}"/>
									@error('total_price')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Paid', 'Unpaid'];

										$selected = $editData->payment_status ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['payment_status_label'] ?? 'payment_status_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['payment_status_placeholder'] ?? 'payment_status_placeholder' }}" name="payment_status" ip-required>
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('payment_status')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<div class="col-md-4">
								<div class="form-item mb-5">
									@php
										$options = ['Draft', 'Final'];

										$selected = $editData->invoice_status ?? '';
									@endphp
									<label class="form-label">{{ $getCurrentTranslation['invoice_status_label'] ?? 'invoice_status_label' }}:</label>
									<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['invoice_status_placeholder'] ?? 'invoice_status_placeholder' }}" name="invoice_status" ip-required>
										<option value="">----</option>
										@foreach($options as $option)
											<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
										@endforeach
									</select>
									@error('invoice_status')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>

						</div>
					</div>
				</div>

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['guests_informations'] ?? 'guests_informations' }}</h3>
						<div class="card-toolbar">
							<button type="button" class="btn btn-sm btn-success append-item-add-btn">
								<i class="fa-solid fa-plus"></i>
							</button>
						</div>
					</div>
					<div class="card-body append-item-wrapper">
						@if(isset($editData) && !empty($editData->guestInfo) && is_array($editData->guestInfo))
							@foreach($editData->guestInfo as $item)
								<div class="append-item rounded border p-5 mb-5">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['guest'] ?? 'guest' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button>
										</div>
									</div>
									<div class="row p-5">
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['guest_name_label'] ?? 'guest_name_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_name_placeholder'] ?? 'guest_name_placeholder' }}" name="guest_info[0][name]" value="{{ $item['name'] ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['guest_passport_number_label'] ?? 'guest_passport_number_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_passport_number_placeholder'] ?? 'guest_passport_number_placeholder' }}" name="guest_info[0][passport_number]" value="{{ $item['passport_number'] ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['guest_phone_label'] ?? 'guest_phone_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_phone_placeholder'] ?? 'guest_phone_placeholder' }}" name="guest_info[0][phone]" value="{{ $item['phone'] ?? '' }}"/>
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['guest_email_label'] ?? 'guest_email_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_email_placeholder'] ?? 'guest_email_placeholder' }}" name="guest_info[0][email]" value="{{ $item['email'] ?? '' }}"/>
											</div>
										</div>
									</div>
								</div>
							@endforeach
						@else
							<div class="append-item rounded border p-5 mb-5">
								<div class="append-item-header d-flex justify-content-between">
									<h3 class="append-item-title">{{ $getCurrentTranslation['guest'] ?? 'guest' }} <span class="append-item-count"></span></h3>
									<div class="append-item-toolbar d-flex justify-content-end">
										<button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
											<i class="fa-solid fa-minus"></i>
										</button>
									</div>
								</div>
								<div class="row p-5">
									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['guest_name_label'] ?? 'guest_name_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_name_placeholder'] ?? 'guest_name_placeholder' }}" name="guest_info[0][name]" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['guest_passport_number_label'] ?? 'guest_passport_number_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_passport_number_placeholder'] ?? 'guest_passport_number_placeholder' }}" name="guest_info[0][passport_number]" />
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['guest_phone_label'] ?? 'guest_phone_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_phone_placeholder'] ?? 'guest_phone_placeholder' }}" name="guest_info[0][phone]" value=""/>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['guest_email_label'] ?? 'guest_email_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['guest_email_placeholder'] ?? 'guest_email_placeholder' }}" name="guest_info[0][email]" value=""/>
										</div>
									</div>
								</div>
							</div>
						@endif
					</div>
				</div>


				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['cancellation_policy'] ?? 'cancellation_policy' }}</h3>
						<div class="card-toolbar">
							{{-- <button type="button" class="btn btn-sm btn-success append-item-add-btn">
								<i class="fa-solid fa-plus"></i>
							</button> --}}
						</div>
					</div>
					<div class="card-body append-item-wrapper">
						@if(isset($editData) && !empty($editData->cancellationPolicy) && is_array($editData->cancellationPolicy))
							@foreach($editData->cancellationPolicy as $item)
								<div class="append-item rounded border p-5 mb-5">
									<div class="append-item-header d-flex justify-content-between">
										<h3 class="append-item-title">{{ $getCurrentTranslation['policy'] ?? 'policy' }} <span class="append-item-count"></span></h3>
										<div class="append-item-toolbar d-flex justify-content-end">
											{{-- <button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
												<i class="fa-solid fa-minus"></i>
											</button> --}}
										</div>
									</div>
									<div class="row p-5">
										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['date_time_label'] ?? 'date_time_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['date_time_placeholder'] ?? 'date_time_placeholder' }}" name="cancellation_policy[0][date_time]" value="{{ $item['date_time'] }}"/>
											</div>
										</div>

										<div class="col-md-6">
											<div class="form-item mb-5">
												<label class="form-label">{{ $getCurrentTranslation['fee_label'] ?? 'fee_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['fee_placeholder'] ?? 'fee_placeholder' }}" name="cancellation_policy[0][fee]" value="{{ $item['fee'] }}"/>
											</div>
										</div>
									</div>
								</div>
							@endforeach
						@else
							<div class="append-item rounded border p-5 mb-5">
								<div class="append-item-header d-flex justify-content-between">
									<h3 class="append-item-title">{{ $getCurrentTranslation['policy'] ?? 'policy' }} <span class="append-item-count"></span></h3>
									<div class="append-item-toolbar d-flex justify-content-end">
										{{-- <button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
											<i class="fa-solid fa-minus"></i>
										</button> --}}
									</div>
								</div>
								<div class="row p-5">
									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['date_time_label'] ?? 'date_time_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['date_time_placeholder'] ?? 'date_time_placeholder' }}" name="cancellation_policy[0][date_time]" />
										</div>
									</div>

									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['fee_label'] ?? 'fee_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['fee_placeholder'] ?? 'fee_placeholder' }}" name="cancellation_policy[0][fee]" />
										</div>
									</div>
								</div>
							</div>
								<div class="append-item rounded border p-5 mb-5">
								<div class="append-item-header d-flex justify-content-between">
									<h3 class="append-item-title">{{ $getCurrentTranslation['policy'] ?? 'policy' }} <span class="append-item-count"></span></h3>
									<div class="append-item-toolbar d-flex justify-content-end">
										{{-- <button type="button" class="btn btn-sm btn-danger append-item-remove-btn me-2">
											<i class="fa-solid fa-minus"></i>
										</button> --}}
									</div>
								</div>
								<div class="row p-5">
									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['date_time_label'] ?? 'date_time_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['date_time_placeholder'] ?? 'date_time_placeholder' }}" name="cancellation_policy[0][date_time]" />
										</div>
									</div>

									<div class="col-md-6">
										<div class="form-item mb-5">
											<label class="form-label">{{ $getCurrentTranslation['fee_label'] ?? 'fee_label' }}:</label>
											<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['fee_placeholder'] ?? 'fee_placeholder' }}" name="cancellation_policy[0][fee]" />
										</div>
									</div>
								</div>
							</div>
						@endif
					</div>

					<div class="card-body border-0 pt-0">
						<div class="col-md-12">
							<div class="form-item mb-5">
								<label class="form-label">{{ $getCurrentTranslation['policy_note_label'] ?? 'policy_note_label' }}:</label>
								<textarea class="form-control" placeholder="{{ $getCurrentTranslation['policy_note_placeholder'] ?? 'policy_note_placeholder' }}" name="policy_note" rows="2">{{ $editData->policy_note ?? (getPrefillHotelData()['policy_note'] ?? '') }}</textarea>
								@error('policy_note')
									<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
								@enderror
							</div>
						</div>
					</div>
				</div>

	
				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">{{ $getCurrentTranslation['contact_informations'] ?? 'contact_informations' }}</h3>
						<div class="card-toolbar"></div>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['contact_info_label'] ?? 'contact_info_label' }}:</label>
									<textarea class="form-control" placeholder="{{ $getCurrentTranslation['contact_info_placeholder'] ?? 'contact_info_placeholder' }}" name="contact_info" ip-required rows="4">{{ $editData->contact_info ?? (getPrefillHotelData()['contact_info'] ?? '') }}</textarea>
									@error('contact_info')
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
	$(document).on('click', '.flight-transit', function(){
		resetFlightTransit();
	});
	

	$(document).on('input', '.calc-input', function () {
		calculateFare();
	});
</script>
@endpush