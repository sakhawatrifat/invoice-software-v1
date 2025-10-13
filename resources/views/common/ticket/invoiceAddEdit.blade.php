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
					<a href="{{ route('ticket.downloadPdf', $editData->id) }}?invoice=1&withPrice=1" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['download'] ?? 'download' }}</a>
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

				@if(isset($editData) && !empty($editData))
				    <input type="hidden" name="document_type" value="{{ $editData->document_type ?? 'ticket' }}"/>
				@else
				    <input type="hidden" name="document_type" value="{{ request()->document_type ?? 'ticket' }}"/>
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
									<label class="form-label">{{ $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label' }}:</label>
									<input type="text" placeholder="{{ $getCurrentTranslation['invoice_date_placeholder'] ?? 'invoice_date_placeholder' }}" class="form-control mb-2 flatpickr-input"  name="invoice_date" value="{{ $editData->invoice_date ?? '' }}" ip-required/>
									@error('invoice_date')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							
							<div class="col-md-4">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label' }}:</label>
									<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['invoice_id_placeholder'] ?? 'invoice_id_placeholder' }}" name="invoice_id" value="{{ $editData->invoice_id ?? generateInvoiceId() }}" ip-required/>
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
							
						</div>
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
															<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['airlines_pnr_placeholder'] ?? 'airlines_pnr_placeholder' }}" name="passenger_info[0][flight][0][airlines_pnr]" value=""/>
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
													<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['airlines_pnr_placeholder'] ?? 'airlines_pnr_placeholder' }}" name="passenger_info[0][flight][0][airlines_pnr]" value=""/>
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
										<th>{{ $getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label' }} ({{ Auth::user()->company_data->currency->short_name ?? '0.00' }})</th>
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
									<input type="text" placeholder="{{ $getCurrentTranslation['footer_title_placeholder'] ?? 'footer_title_placeholder' }}" class="form-control"  name="footer_title"  value="{{ old('footer_title') ?? $editData->footer_title ?? '' }}"/>
									@error('footer_title')
										<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
									@enderror
								</div>
							</div>
							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label">{{ $getCurrentTranslation['footer_text_label'] ?? 'footer_text_label' }}:</label>
									<textarea class="form-control ck-editor" name="footer_text" rows="3">{{ old('footer_text') ?? $editData->footer_text ?? '' }}</textarea>
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
	$(document).on('click', '.flight-transit', function(){
		resetFlightTransit();
	});
	

	$(document).on('input', '.calc-input', function () {
		calculateFare();
	});
</script>
@endpush