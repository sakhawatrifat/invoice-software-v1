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
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{ $getCurrentTranslation['admin_dashboard'] ?? 'admin_dashboard' }}</h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				{{-- <a href="#" class="btn btn-sm fw-bold bg-body btn-color-gray-700 btn-active-color-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create_app">Rollover</a>
				<a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">Add Target</a> --}}
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="row">
				@if(Auth::user()->is_staff != 1)
					@if(
						!$user->company ||
						(
							$user->company &&
							(
								empty($user->company->company_name) ||
								empty($user->company->dark_logo) ||
								empty($user->company->dark_icon) ||
								empty($user->company->address) ||
								empty($user->company->phone_1) ||
								empty($user->company->email_1) ||
								empty($user->company->currency_id)
							)
						)
					)
						<div class="col-md-12 mb-6">
							<div class="alert alert-warning d-flex align-items-center" role="alert">
								<div class="w-100 d-flex align-items-center justify-content-between">
									<div>
										<i class="bi bi-exclamation-triangle-fill me-2"></i>
										<strong>{{ $getCurrentTranslation['incomplete_company_profile'] ?? 'incomplete_company_profile' }}</strong> {{ $getCurrentTranslation['please_update_all_required_company_details'] ?? 'please_update_all_required_company_details' }}
									</div>
									<a href="{{ route('myProfile') }}" class="btn btn-primary btn-sm alert-link">{{ $getCurrentTranslation['update_now'] ?? 'update_now' }}</a>
								</div>
							</div>
						</div>
					@endif
				@endif

				@if(Auth::user()->user_type == 'admin')
					<form class="col-md-12" method="get">
						
						@if(hasPermission('toDoList'))
							<div class="col-md-12 mb-6 p-sticky-wrapper">
								<div class="p-sticky-part d-flex align-items-center justify-content-end bg-white py-5 px-5">
									<div class="col-md-3">
										<div class="input-item-wrap">
											{{-- <label class="form-label">{{ $getCurrentTranslation['date_range_label'] ?? '日付範囲' }}:</label> --}}
											
											@php
												$defaultTodoStart = \Carbon\Carbon::today()->format('Y/m/d'); // today
												$defaultTodoEnd = \Carbon\Carbon::today()->addDays(30)->format('Y/m/d'); //next 30 days
												$selectedTodoDateRange = request()->todo_date_range ?? "$defaultTodoStart-$defaultTodoEnd";

												$toDoListData = toDoListData($selectedTodoDateRange);
												//dd($toDoListData);
											@endphp

											<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
												<div class="cursor-pointer dateRangePicker future-date {{ $selectedTodoDateRange ? 'filled' : 'empty' }}">
													<i class="fa fa-calendar"></i>&nbsp;
													<span>{{ $selectedTodoDateRange }}</span> <i class="fa fa-caret-down"></i>

													<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" 
														name="todo_date_range" 
														data-value="{{ $selectedTodoDateRange }}" 
														value="{{ $selectedTodoDateRange }}" 
														style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
												</div>
												<span class="clear-date-range"><i class="fa fa-times"></i></span>
											</div>

											@error('todo_date_range')
												<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
											@enderror
										</div>
									</div>

									<button type="submit" class="btn btn-primary">
										{{ $getCurrentTranslation['filter'] ?? 'filter' }}
									</button>

									<a class="ms-2 btn btn-secondary" href="{{ route('admin.dashboard') }}">
										{{ $getCurrentTranslation['reset'] ?? 'reset' }}
									</a>
								</div>


								<div class="card rounded border mt-0 bg-white">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['to_do_list'] ?? 'to_do_list' }}
											(Filtered By: Departure & Return Date {{$selectedTodoDateRange}}) <br>
											@if(isset($toDoListData))
											{{ $getCurrentTranslation['total_data'] ?? 'total_data' }}: {{ count($toDoListData) }}
											@endif
										</h3>
										<div class="card-toolbar">
											<a class="btn btn-primary btn-sm" href="{{ route('payment.toDoList') }}?flight_date_range={{ $selectedTodoDateRange }}">{{ $getCurrentTranslation['full_to_do_list'] ?? 'full_to_do_list' }}</a>
										</div>
									</div>
									<div class="card-body">
										@php
											//$toDoListData = toDoListData($selectedTodoDateRange);
											//dd($toDoListData);
										@endphp
										<div class="todo-wrap">
											@if(isset($toDoListData) && count($toDoListData))
												<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
													<table class="table table-bordered table-striped align-middle text-center mb-0">
														<thead class="table-dark" style="position: sticky; top: 0; z-index: 10;">
															<tr>
																<th>#</th>
																<th class="text-start">{{ $getCurrentTranslation['trip_info'] ?? 'trip_info' }}</th>
																<th>{{ $getCurrentTranslation['seat_confirmation_label'] ?? 'seat_confirmation_label' }}</th>
																<th>{{ $getCurrentTranslation['mobility_assistance_label'] ?? 'mobility_assistance_label' }}</th>
																<th>{{ $getCurrentTranslation['transit_visa_application_label'] ?? 'transit_visa_application_label' }}</th>
																<th>{{ $getCurrentTranslation['halal_meal_request_label'] ?? 'halal_meal_request_label' }}</th>
																<th>{{ $getCurrentTranslation['transit_hotel_label'] ?? 'transit_hotel_label' }}</th>
																<th>{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
															</tr>
														</thead>
														<tbody>
															@foreach($toDoListData as $index => $item)
																@php
																	$seatBadge = match($item->seat_confirmation) {
																		'Window' => '<span class="badge bg-primary">Window</span>',
																		'Aisle' => '<span class="badge bg-success">Aisle</span>',
																		'Not Chosen' => '<span class="badge bg-warning text-dark">Not Chosen</span>',
																		default => '<span class="badge bg-light text-dark">—</span>',
																	};

																	$mobilityBadge = match($item->mobility_assistance) {
																		'Wheelchair' => '<span class="badge bg-primary">Wheelchair</span>',
																		'Baby Bassinet Seat' => '<span class="badge bg-info">Baby Bassinet Seat</span>',
																		'Meet & Assist' => '<span class="badge bg-success">Meet & Assist</span>',
																		'Not Chosen' => '<span class="badge bg-warning text-dark">Not Chosen</span>',
																		default => '<span class="badge bg-light text-dark">—</span>',
																	};

																	$visaBadge = match($item->transit_visa_application) {
																		'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
																		'Done' => '<span class="badge bg-success">Done</span>',
																		'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
																		default => '<span class="badge bg-light text-dark">—</span>',
																	};

																	$halalBadge = match($item->halal_meal_request) {
																		'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
																		'Done' => '<span class="badge bg-success">Done</span>',
																		'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
																		default => '<span class="badge bg-light text-dark">—</span>',
																	};

																	$hotelBadge = match($item->transit_hotel) {
																		'Need To Do' => '<span class="badge bg-danger text-white">Need To Do</span>',
																		'Done' => '<span class="badge bg-success">Done</span>',
																		'No Need' => '<span class="badge bg-secondary text-dark">No Need</span>',
																		default => '<span class="badge bg-light text-dark">—</span>',
																	};
																@endphp

																<tr>
																	<td>{{ $index + 1 }}</td>
																	<td class="text-start">
																		<strong>{{ $getCurrentTranslation['payment_invoice_id_label'] ?? 'payment_invoice_id_label' }} :</strong> {{ $item->payment_invoice_id ?? 'N/A' }} <br>
																		<strong>{{ $getCurrentTranslation['ticket_invoice_id_label'] ?? 'ticket_invoice_id_label' }} :</strong> {{ $item->ticket->invoice_id ?? 'N/A' }} <br>

																		<strong>{{ $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label' }} :</strong> {{ $item->trip_type }} <br>
																		
																		<strong>{{ $getCurrentTranslation['flight_route_label'] ?? 'flight_route_label' }} :</strong> {{ $item->flight_route }} <br>
																		
																		<strong>{{ $getCurrentTranslation['departure_label'] ?? 'departure_label' }} :</strong>
																		{{ $item->departure_date_time ? date('Y-m-d, H:i', strtotime($item->departure_date_time)) : 'N/A' }}
																		<br>

																		<strong>{{ $getCurrentTranslation['return_label'] ?? 'return_label' }} :</strong>
																		{{ $item->return_date_time ? date('Y-m-d, H:i', strtotime($item->return_date_time)) : 'N/A' }}
																		<br>

																		<strong>{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }} :</strong> {{ $item->airline->name }} <br>

																	</td>

																	<td>{!! $seatBadge !!}</td>
																	<td>{!! $mobilityBadge !!}</td>
																	<td>{!! $visaBadge !!}</td>
																	<td>{!! $halalBadge !!}</td>
																	<td>{!! $hotelBadge !!}</td>
																	<td>
																		@php $hasAction = false; @endphp

																		@if(hasPermission('payment.show'))
																			@php $hasAction = true; @endphp
																			<a href="{{ route('payment.show', $item->id) }}" class="btn btn-sm btn-info my-1" title="Details">
																				<i class="fa-solid fa-pager"></i>
																			</a>
																		@endif

																		@if(hasPermission('payment.edit'))
																			@php $hasAction = true; @endphp
																			<a href="{{ route('payment.edit', $item->id) }}" class="btn btn-sm btn-primary my-1" title="Edit">
																				<i class="fa-solid fa-pen-to-square"></i>
																			</a>
																		@endif

																		@unless($hasAction)
																			N/A
																		@endunless
																	</td>
																</tr>

															@endforeach
														</tbody>
													</table>
												</div>
											@else
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_data_found_for_selected_filter'] ?? 'no_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							</div>
						@endif
						
						@php
							$summaryPermissions = [
								'toDoList', 'saleGraph', 'airlineBasedGraph', 'transitCityBasedGraph', 'departureCityBasedGraph', 'returnCityBasedGraph', 'introductionSourceBasedGraph', 'issuedSupplierBasedGraph', 'issuedByBasedGraph', 'countryBasedGraph', 'transferToBasedGraph', 'paymentMethodBasedGraph', 'cardTypeBasedGraph', 'cardOwnerBasedGraph' 
							];
						@endphp
						<div class="col-md-12 mb-6 p-sticky-wrapper">
							<div class="p-sticky-part d-flex align-items-center justify-content-end bg-white py-5 px-5">
								<div class="col-md-3">
									<div class="input-item-wrap">
										{{-- <label class="form-label">{{ $getCurrentTranslation['date_range_label'] ?? '日付範囲' }}:</label> --}}
										
										@php
											$defaultStart = \Carbon\Carbon::today()->subDays(6)->format('Y/m/d'); // 6 days ago
											$defaultEnd = \Carbon\Carbon::today()->format('Y/m/d'); // today
											$selectedDateRange = request()->date_range ?? "$defaultStart-$defaultEnd";

											$reportData = reportData($selectedDateRange);
											//dd($reportData);
										@endphp

										<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
											<div class="cursor-pointer dateRangePicker {{ $selectedDateRange ? 'filled' : 'empty' }}">
												<i class="fa fa-calendar"></i>&nbsp;
												<span>{{ $selectedDateRange }}</span> <i class="fa fa-caret-down"></i>

												<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" 
													name="date_range" 
													data-value="{{ $selectedDateRange }}" 
													value="{{ $selectedDateRange }}" 
													style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
											</div>
											<span class="clear-date-range"><i class="fa fa-times"></i></span>
										</div>

										@error('date_range')
											<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
										@enderror
									</div>
								</div>

								<button type="submit" class="btn btn-primary">
									{{ $getCurrentTranslation['filter'] ?? 'filter' }}
								</button>

								<a class="ms-2 btn btn-secondary" href="{{ route('admin.dashboard') }}">
									{{ $getCurrentTranslation['reset'] ?? 'reset' }}
								</a>
							</div>

							@if(hasPermission('saleGraph'))
								<div class="card rounded border mt-0 bg-white">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['sale_graph'] ?? 'sale_graph' }}
											(Filtered By: Payment Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalSale']) && count($reportData['totalSale']))
												<h6 class="text-center title">{{ $getCurrentTranslation['sale_graph'] ?? 'sale_graph' }}</h6>
												<canvas id="saleGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['sale_graph'] ?? 'sale_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('airlineBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['airline_based_graph'] ?? 'airline_based_graph' }}
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalAirline']) && count($reportData['totalAirline']))
												<h6 class="text-center title">{{ $getCurrentTranslation['airline_based_graph'] ?? 'airline_based_graph' }}</h6>
												<canvas id="airlineGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['airline_based_graph'] ?? 'airline_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif
						
							@if(hasPermission('transitCityBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['transit_city_based_graph'] ?? 'transit_city_based_graph' }} 
											(Filtered By: Departure Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalTransitCity']) && count($reportData['totalTransitCity']))
												<h6 class="text-center title">{{ $getCurrentTranslation['transit_city_based_graph'] ?? 'transit_city_based_graph' }}</h6>
												<canvas id="transitCityGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['transit_city_based_graph'] ?? 'transit_city_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('departureCityBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['departure_city_based_graph'] ?? 'departure_city_based_graph' }} 
											(Filtered By: Departure Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalDepartureCity']) && count($reportData['totalDepartureCity']))
												<h6 class="text-center title">{{ $getCurrentTranslation['departure_city_based_graph'] ?? 'departure_city_based_graph' }}</h6>
												<canvas id="departureCityGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['departure_city_based_graph'] ?? 'departure_city_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('returnCityBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['return_city_based_graph'] ?? 'return_city_based_graph' }} 
											(Filtered By: Departure Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalReturnCity']) && count($reportData['totalReturnCity']))
												<h6 class="text-center title">{{ $getCurrentTranslation['return_city_based_graph'] ?? 'return_city_based_graph' }}</h6>
												<canvas id="returnCityGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['return_city_based_graph'] ?? 'return_city_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('introductionSourceBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['introduction_source_based_graph'] ?? 'introduction_source_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalIntroductionSource']) && count($reportData['totalIntroductionSource']))
												<h6 class="text-center title">{{ $getCurrentTranslation['introduction_source_based_graph'] ?? 'introduction_source_based_graph' }}</h6>
												<canvas id="introductionSourceGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['introduction_source_based_graph'] ?? 'introduction_source_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('issuedSupplierBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['issued_supplier_based_graph'] ?? 'issued_supplier_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalIssuedSupplier']) && count($reportData['totalIssuedSupplier']))
												<h6 class="text-center title">{{ $getCurrentTranslation['issued_supplier_based_graph'] ?? 'issued_supplier_based_graph' }}</h6>
												<canvas id="issuedSupplierGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['issued_supplier_based_graph'] ?? 'issued_supplier_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif
							
							@if(hasPermission('issuedByBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['issued_by_based_graph'] ?? 'issued_by_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalIssuedBy']) && count($reportData['totalIssuedBy']))
												<h6 class="text-center title">{{ $getCurrentTranslation['issued_by_based_graph'] ?? 'issued_by_based_graph' }}</h6>
												<canvas id="issuedByGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['issued_by_based_graph'] ?? 'issued_by_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('countryBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['country_based_graph'] ?? 'country_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalCountry']) && count($reportData['totalCountry']))
												<h6 class="text-center title">{{ $getCurrentTranslation['country_based_graph'] ?? 'country_based_graph' }}</h6>
												<canvas id="countryGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['country_based_graph'] ?? 'country_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('transferToBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['transfer_to_based_graph'] ?? 'transfer_to_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalTransferTo']) && count($reportData['totalTransferTo']))
												<h6 class="text-center title">{{ $getCurrentTranslation['transfer_to_based_graph'] ?? 'transfer_to_based_graph' }}</h6>
												<canvas id="transferToGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['transfer_to_based_graph'] ?? 'transfer_to_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('paymentMethodBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['payment_method_based_graph'] ?? 'payment_method_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalPaymentMethod']) && count($reportData['totalPaymentMethod']))
												<h6 class="text-center title">{{ $getCurrentTranslation['payment_method_based_graph'] ?? 'payment_method_based_graph' }}</h6>
												<canvas id="paymentMethodGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['payment_method_based_graph'] ?? 'payment_method_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('cardTypeBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['card_type_based_graph'] ?? 'card_type_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalCardType']) && count($reportData['totalCardType']))
												<h6 class="text-center title">{{ $getCurrentTranslation['card_type_based_graph'] ?? 'card_type_based_graph' }}</h6>
												<canvas id="cardTypeGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['card_type_based_graph'] ?? 'card_type_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							@if(hasPermission('cardOwnerBasedGraph'))
								<div class="card rounded border mt-5 bg-white" style="display: none">
									<div class="card-header">
										<h3 class="card-title">
											{{ $getCurrentTranslation['card_owner_based_graph'] ?? 'card_owner_based_graph' }} 
											(Filtered By: Invoice Date {{$selectedDateRange}})
										</h3>
										<div class="card-toolbar"></div>
									</div>
									<div class="card-body">
										@php
											//$reportData = reportData(request()->date_range ?? null);
											//dd($reportData);
										@endphp
										<div class="graph-wrap">
											@if(isset($reportData) && isset($reportData['totalCardOwner']) && count($reportData['totalCardOwner']))
												<h6 class="text-center title">{{ $getCurrentTranslation['card_owner_based_graph'] ?? 'card_owner_based_graph' }}</h6>
												<canvas id="cardOwnerGraph" width="390" height="100"></canvas>
											@else
												<h6 class="text-center title">{{ $getCurrentTranslation['card_owner_based_graph'] ?? 'card_owner_based_graph' }}</h6>
												<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_graph_data_found_for_selected_filter'] ?? 'no_graph_data_found_for_selected_filter' }}</div>
												<hr>
											@endif
										</div>
									</div>
								</div>
							@endif

							<div class="row">
								@if(hasPermission('tripTypeBasedPieChart'))
									<div class="col-md-6">
										<div class="card rounded border mt-5 bg-white" style="display: none">
											<div class="card-header">
												<h3 class="card-title">
													{{ $getCurrentTranslation['trip_type_based_pie_chart'] ?? 'trip_type_based_pie_chart' }} 
													(Filtered By: Invoice Date {{$selectedDateRange}})
												</h3>
												<div class="card-toolbar"></div>
											</div>
											<div class="card-body">
												@php
													//$reportData = reportData(request()->date_range ?? null);
													//dd($reportData);
												@endphp
												@if(isset($reportData) && isset($reportData['totalTripTypePie']) && count($reportData['totalTripTypePie']))
													<br>
													<div class="pie-chart-wrap">
														<h6 class="text-center title">
															{{ $getCurrentTranslation['trip_type_based_pie_chart'] ?? 'trip_type_based_pie_chart' }}
														</h6>
														<br>
														<canvas id="tripTypePieChart" width="390" height="390"></canvas>
													</div>
												@else
													<div class="pie-chart-wrap">
														<h6 class="text-center title" style="position: unset; transform: unset;">{{ $getCurrentTranslation['trip_type_based_pie_chart'] ?? 'trip_type_based_pie_chart' }}</h6>
														<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_pie_chart_data_found_for_selected_filter'] ?? 'no_pie_chart_data_found_for_selected_filter' }}</div>
														<hr>
													</div>
												@endif
											</div>
										</div>
									</div>
								@endif

								@if(hasPermission('paymentMethodBasedPieChart'))
									<div class="col-md-6">
										<div class="card rounded border mt-5 bg-white" style="display: none">
											<div class="card-header">
												<h3 class="card-title">
													{{ $getCurrentTranslation['payment_method_based_pie_chart'] ?? 'payment_method_based_pie_chart' }} 
													(Filtered By: Invoice Date {{$selectedDateRange}})
												</h3>
												<div class="card-toolbar"></div>
											</div>
											<div class="card-body">
												@php
													//$reportData = reportData(request()->date_range ?? null);
													//dd($reportData);
												@endphp
												@if(isset($reportData) && isset($reportData['totalPaymentMethodPie']) && count($reportData['totalPaymentMethodPie']))
													<br>
													<div class="pie-chart-wrap">
														<h6 class="text-center title">
															{{ $getCurrentTranslation['payment_method_based_pie_chart'] ?? 'payment_method_based_pie_chart' }}
														</h6>
														<br>
														<canvas id="paymentMethodPieChart" width="390" height="390"></canvas>
													</div>
												@else
													<div class="pie-chart-wrap">
														<h6 class="text-center title" style="position: unset; transform: unset;">{{ $getCurrentTranslation['payment_method_based_pie_chart'] ?? 'payment_method_based_pie_chart' }}</h6>
														<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_pie_chart_data_found_for_selected_filter'] ?? 'no_pie_chart_data_found_for_selected_filter' }}</div>
														<hr>
													</div>
												@endif
											</div>
										</div>
									</div>
								@endif

								@if(hasPermission('issuedCardTypeBasedPieChart'))
									<div class="col-md-6">
										<div class="card rounded border mt-5 bg-white" style="display: none">
											<div class="card-header">
												<h3 class="card-title">
													{{ $getCurrentTranslation['issued_card_type_based_pie_chart'] ?? 'issued_card_type_based_pie_chart' }} 
													(Filtered By: Invoice Date {{$selectedDateRange}})
												</h3>
												<div class="card-toolbar"></div>
											</div>
											<div class="card-body">
												@php
													//$reportData = reportData(request()->date_range ?? null);
													//dd($reportData);
												@endphp
												@if(isset($reportData) && isset($reportData['totalIssuedCardTypePie']) && count($reportData['totalIssuedCardTypePie']))
													<br>
													<div class="pie-chart-wrap">
														<h6 class="text-center title">
															{{ $getCurrentTranslation['issued_card_type_based_pie_chart'] ?? 'issued_card_type_based_pie_chart' }}
														</h6>
														<br>
														<canvas id="issuedCardTypePieChart" width="390" height="390"></canvas>
													</div>
												@else
													<div class="pie-chart-wrap">
														<h6 class="text-center title" style="position: unset; transform: unset;">{{ $getCurrentTranslation['issued_card_type_based_pie_chart'] ?? 'issued_card_type_based_pie_chart' }}</h6>
														<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_pie_chart_data_found_for_selected_filter'] ?? 'no_pie_chart_data_found_for_selected_filter' }}</div>
														<hr>
													</div>
												@endif
											</div>
										</div>
									</div>
								@endif

								@if(hasPermission('paymentStatusBasedPieChart'))
									<div class="col-md-6">
										<div class="card rounded border mt-5 bg-white" style="display: none">
											<div class="card-header">
												<h3 class="card-title">
													{{ $getCurrentTranslation['payment_status_based_pie_chart'] ?? 'payment_status_based_pie_chart' }} 
													(Filtered By: Invoice Date {{$selectedDateRange}})
												</h3>
												<div class="card-toolbar"></div>
											</div>
											<div class="card-body">
												@php
													//$reportData = reportData(request()->date_range ?? null);
													//dd($reportData);
												@endphp
												@if(isset($reportData) && isset($reportData['totalPaymentStatusPie']) && count($reportData['totalPaymentStatusPie']))
													<br>
													<div class="pie-chart-wrap">
														<h6 class="text-center title">
															{{ $getCurrentTranslation['payment_status_based_pie_chart'] ?? 'payment_status_based_pie_chart' }}
														</h6>
														<br>
														<canvas id="paymentStatusPieChart" width="390" height="390"></canvas>
													</div>
												@else
													<div class="pie-chart-wrap">
														<h6 class="text-center title" style="position: unset; transform: unset;">{{ $getCurrentTranslation['payment_status_based_pie_chart'] ?? 'payment_status_based_pie_chart' }}</h6>
														<div style="text-align: center; font-size: 14px; color: rgb(128, 128, 128); margin-bottom: 20px;">{{ $getCurrentTranslation['no_pie_chart_data_found_for_selected_filter'] ?? 'no_pie_chart_data_found_for_selected_filter' }}</div>
														<hr>
													</div>
												@endif
											</div>
										</div>
									</div>
								@endif
								
							</div>



						</div>
					</form>


					<div class="col-md-4 mb-6">
						<a class="card card-flush dashboard-card bg-primary bg-gradient text-white-all" @if(hasPermission('user.index')) href="{{ route('admin.user.index') }}" @endif>
							<div class="card-header py-5">
								<div class="card-title d-flex flex-column">
									<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($totalUser) }}</span>
									<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['total_users'] ?? 'total_users' }}</span>
								</div>
							</div>
						</a>
					</div>
					
					<div class="col-md-4 mb-6">
						<a class="card card-flush dashboard-card bg-success bg-gradient text-white-all" @if(hasPermission('user.index')) href="{{ route('admin.user.index') }}" @endif>
							<div class="card-header py-5">
								<div class="card-title d-flex flex-column">
									<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($totalUser->where('status', 'Active')) }}</span>
									<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['active_users'] ?? 'active_users' }}</span>
								</div>
							</div>
						</a>
					</div>
				@endif

				<div class="col-md-4 mb-6">
					<a class="card card-flush dashboard-card bg-info bg-gradient text-white-all" @if(hasPermission('airline.index')) href="{{ route('admin.airline.index') }}" @endif>
						<div class="card-header py-5">
							<div class="card-title d-flex flex-column">
								<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($totalAirline) }}</span>
								<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['total_airlines'] ?? 'total_airlines' }}</span>
							</div>
						</div>
					</a>
				</div>

				<div class="col-md-4 mb-6">
					<div class="card card-flush bg-warning bg-gradient text-white-all">
						<div class="card-header py-5">
							<div class="card-title d-flex flex-column">
								<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($allTicket) }}</span>
								<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['total_documents'] ?? 'total_documents' }}</span>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-4 mb-6">
					<a class="card card-flush dashboard-card bg-danger bg-gradient text-white-all" @if(hasPermission('ticket.index')) href="{{ route('ticket.index') }}?document_type=ticket" @endif>
						<div class="card-header py-5">
							<div class="card-title d-flex flex-column">
								<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($allTicket->where('document_type', 'ticket')) }}</span>
								<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['total_tickets'] ?? 'total_tickets' }}</span>
							</div>
						</div>
					</a>
				</div>

				<div class="col-md-4 mb-6">
					<a class="card card-flush dashboard-card bg-secondary bg-gradient text-dark-all" @if(hasPermission('ticket.index')) href="{{ route('ticket.index') }}?document_type=invoice" @endif>
						<div class="card-header py-5">
							<div class="card-title d-flex flex-column">
								<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($allTicket->where('document_type', 'invoice')) }}</span>
								<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['total_invoices'] ?? 'total_invoices' }}</span>
							</div>
						</div>
					</a>
				</div>

				<div class="col-md-4 mb-6">
					<div class="card card-flush bg-dark bg-gradient text-white-all">
						<div class="card-header py-5">
							<div class="card-title d-flex flex-column">
								<span class="fs-2hx fw-bold me-2 lh-1 ls-n2">{{ count($allPassengers) }}</span>
								<span class="pt-1 fw-semibold fs-6">{{ $getCurrentTranslation['total_passengers'] ?? 'total_passengers' }}</span>
							</div>
						</div>
					</div>
				</div>
				
			</div>

		</div>
		<!--end::Content container-->
	</div>
</div>
@endsection

@push('script')
	<script>
		var currencySymbol = "{{ Auth::user()->company_data->currency->symbol ?? '৳' }}"
        document.addEventListener('DOMContentLoaded', function () {
			
			// Get sale data from Laravel
            const saleCanvas = document.getElementById('saleGraph');
            const saleData = @json($reportData['totalSale'] ?? []);
			const saleLabels = (saleData || []).map(item => item.t_date);
			const saleAmounts = (saleData || []).map(item => item.total_amount);
			
            // Get airline based data from Laravel
			const airlineCanvas = document.getElementById('airlineGraph');
            const airlineData = @json($reportData['totalAirline'] ?? []);
			const airlineLabels = (airlineData || []).map(item => item.date_airline);
			const airlineAmounts = (airlineData || []).map(item => item.airline_count);
			
            // Get transitCity based data from Laravel
			const transitCityCanvas = document.getElementById('transitCityGraph');
            const transitCityData = @json($reportData['totalTransitCity'] ?? []);
			const transitCityLabels = (transitCityData || []).map(item => item.city_date);
			const transitCityAmounts = (transitCityData || []).map(item => item.total_transit_city);

			// Get departurCity based data from Laravel
			const departureCityCanvas = document.getElementById('departureCityGraph');
            const departureCityData = @json($reportData['totalDepartureCity'] ?? []);
			const departureCityLabels = (departureCityData || []).map(item => item.city_date);
			const departureCityAmounts = (departureCityData || []).map(item => item.total_departure_city);

			// Get return city based data from Laravel
			const returnCityCanvas = document.getElementById('returnCityGraph');
            const returnCityData = @json($reportData['totalReturnCity'] ?? []);
			const returnCityLabels = (returnCityData || []).map(item => item.city_date);
			const returnCityAmounts = (returnCityData || []).map(item => item.total_departure_city);

			// Get introductionSource based data from Laravel
			const introductionSourceCanvas = document.getElementById('introductionSourceGraph');
            const introductionSourceData = @json($reportData['totalIntroductionSource'] ?? []);
			const introductionSourceLabels = (introductionSourceData || []).map(item => item.title_date);
			const introductionSourceAmounts = (introductionSourceData || []).map(item => item.total_count);

			// Get issuedSupplier based data from Laravel
			const issuedSupplierCanvas = document.getElementById('issuedSupplierGraph');
            const issuedSupplierData = @json($reportData['totalIssuedSupplier'] ?? []);
			const issuedSupplierLabels = (issuedSupplierData || []).map(item => item.title_date);
			const issuedSupplierAmounts = (issuedSupplierData || []).map(item => item.total_count);

			// Get issuedBy based data from Laravel
			const issuedByCanvas = document.getElementById('issuedByGraph');
            const issuedByData = @json($reportData['totalIssuedBy'] ?? []);
			const issuedByLabels = (issuedByData || []).map(item => item.title_date);
			const issuedByAmounts = (issuedByData || []).map(item => item.total_count);

			// Get country based data from Laravel
			const countryCanvas = document.getElementById('countryGraph');
            const countryData = @json($reportData['totalCountry'] ?? []);
			const countryLabels = (countryData || []).map(item => item.title_date);
			const countryAmounts = (countryData || []).map(item => item.total_count);

			// Get transferTo based data from Laravel
			const transferToCanvas = document.getElementById('transferToGraph');
            const transferToData = @json($reportData['totalTransferTo'] ?? []);
			const transferToLabels = (transferToData || []).map(item => item.title_date);
			const transferToAmounts = (transferToData || []).map(item => item.total_count);

			// Get paymentMethod based data from Laravel
			const paymentMethodCanvas = document.getElementById('paymentMethodGraph');
            const paymentMethodData = @json($reportData['totalPaymentMethod'] ?? []);
			const paymentMethodLabels = (paymentMethodData || []).map(item => item.title_date);
			const paymentMethodAmounts = (paymentMethodData || []).map(item => item.total_count);

			// Get cardType based data from Laravel
			const cardTypeCanvas = document.getElementById('cardTypeGraph');
            const cardTypeData = @json($reportData['totalCardType'] ?? []);
			const cardTypeLabels = (cardTypeData || []).map(item => item.title_date);
			const cardTypeAmounts = (cardTypeData || []).map(item => item.total_count);

			// Get cardOwner based data from Laravel
			const cardOwnerCanvas = document.getElementById('cardOwnerGraph');
            const cardOwnerData = @json($reportData['totalCardOwner'] ?? []);
			const cardOwnerLabels = (cardOwnerData || []).map(item => item.title_date);
			const cardOwnerAmounts = (cardOwnerData || []).map(item => item.total_count);

            // Common Chart Options (with Datalabels)
            const chartCurrencyOptions = {
                responsive: true,
                plugins: {
                    legend: { display: false }, // Hide legend for clarity
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#333',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: value => `${currencySymbol} ${value.toLocaleString()}` // Show amount with currency symbol
                    }
                },
                scales: {
                    x: { type: 'category' },
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: value => `${currencySymbol} ${value.toLocaleString()}` // Format Y-axis labels
                        }
                    }
                }
            };

			const chartNumericOptions = {
                responsive: true,
                plugins: {
                    legend: { display: false }, // Hide legend for clarity
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#333',
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: value => `${value.toLocaleString()}` // Show amount with currency symbol
                    }
                },
                scales: {
                    x: { type: 'category' },
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: value => `${value.toLocaleString()}` // Format Y-axis labels
                        }
                    }
                }
            };

            @if(isset($reportData) && isset($reportData['totalSale']) && count($reportData['totalSale']))
                // Initialize Chart
                new Chart(saleCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: saleLabels,
                        datasets: [{
                            label: `Sale ${currencySymbol}`,
                            data: saleAmounts,
                            borderColor: 'rgb(89, 179, 53)',
                            backgroundColor: 'rgb(89, 179, 53)',
                            borderWidth: 1
                        }]
                    },
                    options: chartCurrencyOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalAirline']) && count($reportData['totalAirline']))
                // Initialize Chart
                new Chart(airlineCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: airlineLabels,
                        datasets: [{
                            label: `Airline`,
                            data: airlineAmounts,
                            borderColor: 'rgb(54, 162, 235)',
							backgroundColor: 'rgb(54, 162, 235)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif


			@if(isset($reportData) && isset($reportData['totalTransitCity']) && count($reportData['totalTransitCity']))
                // Initialize Chart
                new Chart(transitCityCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: transitCityLabels,
                        datasets: [{
                            label: `Trasnsit City`,
                            data: transitCityAmounts,
                            borderColor: 'rgb(255, 159, 64)',
							backgroundColor: 'rgb(255, 159, 64)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif


			@if(isset($reportData) && isset($reportData['totalDepartureCity']) && count($reportData['totalDepartureCity']))
                // Initialize Chart
                new Chart(departureCityCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: departureCityLabels,
                        datasets: [{
                            label: `Departure City`,
                            data: departureCityAmounts,
                            borderColor: 'rgb(75, 192, 192)',
							backgroundColor: 'rgb(75, 192, 192)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalReturnCity']) && count($reportData['totalReturnCity']))
                // Initialize Chart
                new Chart(returnCityCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: returnCityLabels,
                        datasets: [{
                            label: `Return City`,
                            data: returnCityAmounts,
                            borderColor: 'rgb(153, 102, 255)',
							backgroundColor: 'rgb(153, 102, 255)',	
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalIntroductionSource']) && count($reportData['totalIntroductionSource']))
                // Initialize Chart
                new Chart(introductionSourceCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: introductionSourceLabels,
                        datasets: [{
                            label: `Introduction Source`,
                            data: introductionSourceAmounts,
                            borderColor: 'rgb(255, 99, 132)',
							backgroundColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalIssuedSupplier']) && count($reportData['totalIssuedSupplier']))
                // Initialize Chart
                new Chart(issuedSupplierCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: issuedSupplierLabels,
                        datasets: [{
                            label: `Issued Supplier`,
                            data: issuedSupplierAmounts,
                            borderColor: 'rgb(75, 192, 192)',
							backgroundColor: 'rgb(75, 192, 192)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalIssuedBy']) && count($reportData['totalIssuedBy']))
                // Initialize Chart
                new Chart(issuedByCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: issuedByLabels,
                        datasets: [{
                            label: `Issued By`,
                            data: issuedByAmounts,
                            borderColor: 'rgb(255, 99, 132)',
							backgroundColor: 'rgb(255, 99, 132)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalCountry']) && count($reportData['totalCountry']))
                // Initialize Chart
                new Chart(countryCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: countryLabels,
                        datasets: [{
                            label: `Country`,
                            data: countryAmounts,
                            borderColor: 'rgb(255, 205, 86)',
							backgroundColor: 'rgb(255, 205, 86)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalTransferTo']) && count($reportData['totalTransferTo']))
                // Initialize Chart
                new Chart(transferToCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: transferToLabels,
                        datasets: [{
                            label: `Transfer To`,
                            data: transferToAmounts,
                            borderColor: 'rgb(104, 132, 245)',
							backgroundColor: 'rgb(104, 132, 245)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif


			@if(isset($reportData) && isset($reportData['totalPaymentMethod']) && count($reportData['totalPaymentMethod']))
                // Initialize Chart
                new Chart(paymentMethodCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: paymentMethodLabels,
                        datasets: [{
                            label: `Payment Method`,
                            data: paymentMethodAmounts,
                            borderColor: 'rgb(60, 179, 113)',
							backgroundColor: 'rgb(60, 179, 113)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalCardType']) && count($reportData['totalCardType']))
                // Initialize Chart
                new Chart(cardTypeCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: cardTypeLabels,
                        datasets: [{
                            label: `Card Type`,
                            data: cardTypeAmounts,
                            borderColor: 'rgb(255, 127, 80)',
							backgroundColor: 'rgb(255, 127, 80)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif

			@if(isset($reportData) && isset($reportData['totalCardOwner']) && count($reportData['totalCardOwner']))
                // Initialize Chart
                new Chart(cardOwnerCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: cardOwnerLabels,
                        datasets: [{
                            label: `Card Owner`,
                            data: cardOwnerAmounts,
                            borderColor: 'rgb(135, 206, 250)',
							backgroundColor: 'rgb(135, 206, 250)',
                            borderWidth: 1
                        }]
                    },
                    options: chartNumericOptions,
                    //plugins: [ChartDataLabels]
                });
            @endif
			
        });
    </script>


	<script>
		// document.addEventListener('DOMContentLoaded', function () {

		// 	// PaymentMethodPie Chart Data
        //     const paymentMethodPieData = @json($reportData['totalPaymentMethodPie'] ?? []);
        //     const paymentMethodPieLabels = (paymentMethodPieData || []).map(item => item.title);
        //     const paymentMethodPieCount = (paymentMethodPieData || []).map(item => item.count);

        //     // Predefined colors for specific categories
        //     const paymentMethodPiePredefinedColors = {
        //         "label1": "#ca1f50",
        //         "label2": "#ed7d21",
        //         "label3": "#823684"
        //     };

        //     // Function to generate random colors
        //     const paymentMethodPieGenerateRandomColor = () => `hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`;

        //     // Assign colors based on category name
        //     const paymentMethodPieBackgroundColors = paymentMethodPieLabels.map(label => {
        //         const lowerLabel = label.toLowerCase(); // Convert to lowercase
        //         return paymentMethodPiePredefinedColors[lowerLabel] || paymentMethodPieGenerateRandomColor();
        //     });

        //     @if(isset($reportData) && isset($reportData['totalPaymentMethodPie']) && count($reportData['totalPaymentMethodPie']))
        //         // Initialize Chart.js Pie Chart
        //         const ctx = document.getElementById('paymentMethodPieChart').getContext('2d');
        //         new Chart(ctx, {
        //             type: 'pie',
        //             data: {
        //                 labels: paymentMethodPieLabels,
        //                 datasets: [{
        //                     label: 'Trip Type',
        //                     data: paymentMethodPieCount,
        //                     backgroundColor: paymentMethodPieBackgroundColors,
        //                     borderWidth: 1
        //                 }]
        //             },
        //             options: {
        //                 responsive: true,
        //                 plugins: {
        //                     legend: {
        //                         position: 'right'
        //                     },
        //                     datalabels: {
        //                         color: '#fff',
        //                         font: {
        //                             weight: 'bold',
        //                             size: 14
        //                         },
        //                         formatter: (value, context) => {
        //                             return value.toLocaleString(); // Show amount with currency
        //                         }
        //                     }
        //                 }
        //             },
        //             plugins: [ChartDataLabels] // Enable datalabels plugin
        //         });
        //     @endif
        // });



		document.addEventListener('DOMContentLoaded', function () {

			function renderPieChart({ elementId, chartData, predefinedColors, label }) {
				if (!chartData || !chartData.length) return;

				const labels = chartData.map(item => item.title);
				const counts = chartData.map(item => item.count);

				const generateRandomColor = () => `hsl(${Math.floor(Math.random() * 360)}, 70%, 60%)`;

				const backgroundColors = labels.map(lbl => {
					const matchKey = Object.keys(predefinedColors).find(key => key.toLowerCase() === lbl.toLowerCase());
					return predefinedColors[matchKey] || generateRandomColor();
				});

				const ctx = document.getElementById(elementId)?.getContext('2d');
				if (!ctx) return;

				new Chart(ctx, {
					type: 'pie',
					data: {
						labels,
						datasets: [{
							label,
							data: counts,
							backgroundColor: backgroundColors,
							borderWidth: 1
						}]
					},
					options: {
						responsive: true,
						plugins: {
							legend: { position: 'right' },
							datalabels: {
								color: '#fff',
								font: { weight: 'bold', size: 14 },
								formatter: value => value.toLocaleString()
							}
						}
					},
					plugins: [ChartDataLabels]
				});
			}

			const predefinedColors = {
				// Trip Type Colors
				"One Way": "#ca1f50",
				"Round Trip": "#ed7d21",
				"Multi City": "#823684",

				// Payment Status Colors
				"Unpaid": "#d9534f",   // red
				"Paid": "#5cb85c",     // green
				"Partial": "#f0ad4e",  // orange
				"Unknown": "#6c757d"   // gray
			};

			renderPieChart({
				elementId: 'tripTypePieChart',
				chartData: @json($reportData['totalTripTypePie'] ?? []),
				predefinedColors,
				label: 'Trip Type'
			});

			renderPieChart({
				elementId: 'paymentMethodPieChart',
				chartData: @json($reportData['totalPaymentMethodPie'] ?? []),
				predefinedColors,
				label: 'Payment Method'
			});

			renderPieChart({
				elementId: 'issuedCardTypePieChart',
				chartData: @json($reportData['totalIssuedCardTypePie'] ?? []),
				predefinedColors,
				label: 'Issued Card Type'
			});

			renderPieChart({
				elementId: 'paymentStatusPieChart',
				chartData: @json($reportData['totalPaymentStatusPie'] ?? []),
				predefinedColors,
				label: 'Payment Status'
			});
		});


    </script>
@endpush