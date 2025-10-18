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
					
					<li class="breadcrumb-item">{{ $getCurrentTranslation['profit_loss_report'] ?? 'profit_loss_report' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				
			</div>
		</div>
	</div>

	<style>
		.table.report-table th,
		.table.report-table td{
			padding-left: 15px;
			padding-right: 15px;
		}
	</style>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<!--begin::Accordion-->
			<div class="card rounded border mt-5 p-0 bg-white">
				<div class="accordion" id="kt_accordion_1">
					<div class="accordion-item">
						<h2 class="accordion-header" id="kt_accordion_1_header_1">
							<button class="accordion-button fs-4 fw-semibold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_1_body_1" aria-expanded="true" aria-controls="kt_accordion_1_body_1">
								<i class="fa fa-filter" aria-hidden="true"></i> &nbsp;
								{{ $getCurrentTranslation['filter'] ?? 'filter' }}
							</button>
						</h2>
						<div id="kt_accordion_1_body_1" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_1_header_1" data-bs-parent="#kt_accordion_1">
							<div class="accordion-body">
								<form class="filter-data-form" method="get">
									<div class="row">
										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$selected = request()->search ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['search_label'] ?? 'search_label' }}:</label>
												<input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['search_placeholder'] ?? 'search_placeholder' }}" name="search" value="{{ $selected }}"/>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item">
												@php
													$options = getWhereInModelData('IntroductionSource', 'status', [1]);
													$selected = request()->introduction_source_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['introduction_source_label'] ?? 'introduction_source_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['introduction_source_placeholder'] ?? 'introduction_source_placeholder' }}" name="introduction_source_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = getAllModelData('Country');

													$selected = request()->customer_country_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['customer_country_label'] ?? 'customer_country_label' }}:</label>
												<select class="form-select select2-with-images" data-class="flag" data-placeholder="{{ $getCurrentTranslation['customer_country_placeholder'] ?? 'customer_country_placeholder' }}" name="customer_country_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" data-image="{{ getStaticFile('flags', strtolower($option->short_name))}}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item">
												@php
													// Fetch active suppliers
													$options = getWhereInModelData('IssuedSupplier', 'status', [1]);

													// Ensure $selected is an array (from editData)
													$selected = request()->issued_supplier_ids ?? [];
													if (!is_array($selected)) {
														$selected = json_decode($selected, true) ?? [];
													}
												@endphp

												<label class="form-label">
													{{ $getCurrentTranslation['issued_supplier_label'] ?? 'issued_supplier_label' }}:
												</label>

												<select class="form-select" 
														data-control="select2" 
														data-placeholder="{{ $getCurrentTranslation['issued_supplier_placeholder'] ?? 'issued_supplier_placeholder' }}" 
														name="issued_supplier_ids[]" 
														multiple>
													@foreach($options as $option)
														<option value="{{ $option->id }}" 
															{{ in_array($option->id, $selected) ? 'selected' : '' }}>
															{{ $option->name }}
														</option>
													@endforeach
												</select>
											</div>
										</div>


										<div class="col-md-3">
											<div class="input-item">
												@php
													$options = getWhereInModelData('IssuedBy', 'status', [1]);
													$selected = request()->issued_by_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['issued_by_label'] ?? 'issued_by_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['issued_by_placeholder'] ?? 'issued_by_placeholder' }}" name="issued_by_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = ['One Way', 'Round Trip', 'Multi City'];

													$selected = request()->trip_type ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['trip_type_label'] ?? 'trip_type_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['trip_type_placeholder'] ?? 'trip_type_placeholder' }}" name="trip_type">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('trip_type')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = getCities();

													$selected = request()->departure ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['departure_city_label'] ?? 'departure_city_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['departure_placeholder'] ?? 'departure_placeholder' }}" name="departure">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('departure')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = getCities();

													$selected = request()->destination ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['destination_city_label'] ?? 'destination_city_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['destination_placeholder'] ?? 'destination_placeholder' }}" name="destination">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('destination')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item mb-5">
												@php
													$options = getWhereInModelData('Airline', 'status', [1]);
													$selected = request()->airline_id ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['airline_label'] ?? 'airline_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['airline_placeholder'] ?? 'airline_placeholder' }}" name="airline_id" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item-wrap mb-5">
												<label class="form-label">{{ $getCurrentTranslation['flight_date_range_label'] ?? 'flight_date_range_label' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														//$defaultStart = \Carbon\Carbon::today()->subDays(6)->format('Y/m/d'); // 6 days ago
														//$defaultEnd = \Carbon\Carbon::today()->format('Y/m/d'); // today
														//$selectedDateRange = request()->flight_date_range ?? "$defaultStart-$defaultEnd";

														$selectedDateRange = request()->flight_date_range ?? null;
													@endphp
													<div class="cursor-pointer dateRangePicker future-date {{$selectedDateRange ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="flight_date_range" data-value="{{$selectedDateRange ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('flight_date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>


										<div class="col-md-3">
											<div class="input-item-wrap mb-5">
												<label class="form-label">{{ $getCurrentTranslation['invoice_date_range_label'] ?? 'invoice_date_range_label' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														// $defaultStart = \Carbon\Carbon::today()->subDays(6)->format('Y/m/d'); // 6 days ago
														// $defaultEnd = \Carbon\Carbon::today()->format('Y/m/d'); // today
														// $selectedDateRange = request()->invoice_date_range ?? "$defaultStart-$defaultEnd";

														$selectedDateRange = request()->invoice_date_range ?? '';
													@endphp
													<div class="cursor-pointer dateRangePicker {{$selectedDateRange ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="invoice_date_range" data-value="{{$selectedDateRange ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('invoice_date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item mb-5">
												@php
													$options = getWhereInModelData('TransferTo', 'status', [1]);
													$selected = request()->transfer_to ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['transfer_to_label'] ?? 'transfer_to_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['transfer_to_placeholder'] ?? 'transfer_to_placeholder' }}" name="transfer_to" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item mb-5">
												@php
													$options = getWhereInModelData('PaymentMethod', 'status', [1]);
													$selected = request()->payment_method ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['payment_method_label'] ?? 'payment_method_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['payment_method_placeholder'] ?? 'payment_method_placeholder' }}" name="payment_method" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item mb-5">
												@php
													$options = getWhereInModelData('IssuedCardType', 'status', [1]);
													$selected = request()->issued_card_type ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['issued_card_type_label'] ?? 'issued_card_type_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['issued_card_type_placeholder'] ?? 'issued_card_type_placeholder' }}" name="issued_card_type" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="input-item mb-5">
												@php
													$options = getWhereInModelData('CardOwner', 'status', [1]);
													$selected = request()->card_owner ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['card_owner_label'] ?? 'card_owner_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['card_owner_placeholder'] ?? 'card_owner_placeholder' }}" name="card_owner" >
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option->id }}" {{ $option->id == $selected ? 'selected' : '' }}>{{ $option->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="col-md-3">
											<div class="form-item mb-5">
												@php
													$options = ['Unpaid', 'Paid', 'Partial', 'Unknown'];

													$selected = request()->payment_status ?? '';
												@endphp
												<label class="form-label">{{ $getCurrentTranslation['payment_status_label'] ?? 'payment_status_label' }}:</label>
												<select class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['payment_status_placeholder'] ?? 'payment_status_placeholder' }}" name="payment_status">
													<option value="0">----</option>
													@foreach($options as $option)
														<option value="{{ $option }}" {{ $option == $selected ? 'selected' : '' }}>{{ $option }}</option>
													@endforeach
												</select>
												@error('payment_status')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

										{{-- <div class="col-md-3">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['payment_date_range_label'] ?? 'payment_date_range_label' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													<div class="cursor-pointer dateRangePicker {{request()->payment_date_range ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="payment_date_range" data-value="{{request()->payment_date_range ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('payment_date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div> --}}

										{{-- <div class="col-md-3">
											<div class="input-item-wrap">
												<label class="form-label">{{ $getCurrentTranslation['next_payment_date_range_label'] ?? 'next_payment_date_range_label' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													<div class="cursor-pointer dateRangePicker future-date {{request()->next_payment_date_range ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="next_payment_date_range" data-value="{{request()->next_payment_date_range ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('next_payment_date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div> --}}

										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-0">
												<a class="btn btn-secondary btn-sm me-3" href="{{ route('admin.profitLossReport') }}?invoice_date_range={{ getDateRange(6, 'Previous') }}">
													{{ $getCurrentTranslation['reset'] ?? 'reset' }}
												</a>
												<button type="type" class="btn btn-primary btn-sm filter-data-btn">
													{{ $getCurrentTranslation['filter'] ?? 'filter' }}
												</button>
											</div>
										</div>

										


									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!--end::Accordion-->

			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						{{ $getCurrentTranslation['profit_loss_report'] ?? 'profit_loss_report' }}
					</h3>
				</div>
				<div class="card-body">
					@php
						// --- PREPROCESS: CALCULATE PAID & DUE AMOUNTS ---
						$profitLossData = $profitLossData->map(function ($item) {
							if (is_string($item->paymentData)) {
								$payments = json_decode($item->paymentData, true);
								if (json_last_error() !== JSON_ERROR_NONE) {
									$payments = [];
								}
							} elseif (is_array($item->paymentData)) {
								$payments = $item->paymentData;
							} else {
								$payments = [];
							}

							$totalPaid = is_array($payments)
								? collect($payments)->sum('paid_amount')
								: 0;

							$dueAmount = $item->total_selling_price - $totalPaid;

							$item->total_paid = $totalPaid;
							$item->due_amount = $dueAmount;

							return $item;
						});

						// --- SUMMARY CALCULATIONS ---
						$total_airline = $profitLossData->groupBy('airline_id')->count();
						$total_introduction_source = $profitLossData->groupBy('introduction_source_id')->count();
						$total_country = $profitLossData->groupBy('customer_country_id')->count();
						$total_issued_suppliers = $profitLossData->pluck('issued_supplier_ids')
							->map(function ($ids) {
								if (is_string($ids)) {
									return collect(json_decode($ids, true) ?? []);
								} elseif (is_array($ids)) {
									return collect($ids);
								} else {
									return collect([]);
								}
							})
							->flatten()
							->unique()
							->count();
						$total_issued_by = $profitLossData->groupBy('issued_by_id')->count();
						$total_trip_type = $profitLossData->groupBy('trip_type')->count();
						$total_seat_confirmation = $profitLossData->groupBy('seat_confirmation')->count();
						$total_mobility_assistance = $profitLossData->groupBy('mobility_assistance')->count();
						$total_transit_visa_application = $profitLossData->groupBy('transit_visa_application')->count();
						$total_halal_meal_request = $profitLossData->groupBy('halal_meal_request')->count();
						$total_transit_hotel = $profitLossData->groupBy('transit_hotel')->count();
						$total_transfer_to = $profitLossData->groupBy('transfer_to_id')->count();
						$total_payment_method = $profitLossData->groupBy('payment_method_id')->count();
						$total_issued_card_type = $profitLossData->groupBy('issued_card_type_id')->count();
						$total_card_owner = $profitLossData->groupBy('card_owner_id')->count();

						$total_purchase_amount = $profitLossData->sum('total_purchase_price');
						$total_selling_amount = $profitLossData->sum('total_selling_price');
						$total_profit = $profitLossData->sum('total_selling_price') - $profitLossData->sum('total_purchase_price');
						// $total_refund_amount = $profitLossData
						// 	->where('is_refund', 1)
						// 	->sum(function ($item) {
						// 		return ($item->total_selling_price ?? 0) - ($item->cancellation_fee ?? 0);
						// 	});

						$total_cancellation_fee = $profitLossData->where('is_refund', 1)->sum('cancellation_fee');
						$total_profit_after_refund = $total_profit - $total_cancellation_fee;
						$total_paid_amount = $profitLossData->sum('total_paid');
						$total_due_amount = $profitLossData->sum('due_amount');

						// --- PAYMENT STATUS SUMMARY ---
						$paymentStatusSummary = $profitLossData
							->groupBy(fn($item) => $item->payment_status ?: 'Unknown')
							->map(function ($group) {
								return [
									'count' => $group->count(),
									'total_purchase_amount' => $group->sum('total_purchase_price'),
									'total_selling_amount' => $group->sum('total_selling_price'),
									'total_cancellation_fee' => $group->sum('cancellation_fee'),
									'total_profit' => $group->sum('total_selling_price') - $group->sum('total_purchase_price'),
									'total_paid_amount' => $group->sum('total_paid'),
									'total_due_amount' => $group->sum('due_amount'),
								];
							});
					@endphp

					{{-- ================= MAIN SUMMARY TABLE ================= --}}
					<div class="row">
						{{-- ================= PROFIT / LOSS SUMMARY ================= --}}
						<div class="col-md-12">
							<div class="card shadow-sm mb-4">
								<div class="card-header bg-success text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['profit_loss_summary'] ?? 'profit_loss_summary' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center mb-0">
										<tbody>
											<tr>
												<th>{{ $getCurrentTranslation['total_purchase'] ?? 'total_purchase' }}</th>
												<td>
													{{ number_format($total_purchase_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											<tr>
												<th>{{ $getCurrentTranslation['total_selling'] ?? 'total_selling' }}</th>
												<td>
													{{ number_format($total_selling_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											{{-- ✅ Dynamic Profit/Loss Row with Minus for Loss --}}
											@php
												$isProfit = $total_profit >= 0;
												$profitLossLabel = $isProfit
													? ($getCurrentTranslation['total_profit'] ?? 'total_profit')
													: ($getCurrentTranslation['total_loss'] ?? 'total_loss');
												$profitLossClass = $isProfit ? 'table-success text-success' : 'table-danger text-danger';
												$profitLossValue = $isProfit
													? number_format($total_profit, 2)
													: '-' . number_format(abs($total_profit), 2);
											@endphp

											<tr class="fw-bold {{ $profitLossClass }}">
												<th>{{ $profitLossLabel }}</th>
												<td>
													{{ $profitLossValue }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											{{-- <tr>
												<th>{{ $getCurrentTranslation['total_refund'] ?? 'total_refund' }}</th>
												<td>
													{{ number_format($total_refund_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr> --}}

											<tr class="table-danger">
												<th class="fw-semibold">{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'total_cancellation_fee' }}</th>
												<td class="fw-semibold">
													{{ number_format($total_cancellation_fee, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											{{-- ✅ Dynamic Profit/Loss After Refund Row with Minus for Loss --}}
											@php
												$isProfitAfterRefund = $total_profit_after_refund >= 0;
												$profitLossAfterRefundLabel = $isProfitAfterRefund
													? ($getCurrentTranslation['total_profit_after_refund'] ?? 'total_profit_after_refund')
													: ($getCurrentTranslation['total_loss_after_refund'] ?? 'total_loss_after_refund');
												$profitLossAfterRefundClass = $isProfitAfterRefund ? 'table-success text-success' : 'table-danger text-danger';
												$profitLossAfterRefundValue = $isProfitAfterRefund
													? number_format($total_profit_after_refund, 2)
													: '-' . number_format(abs($total_profit_after_refund), 2);
											@endphp

											<tr class="fw-bold {{ $profitLossAfterRefundClass }}">
												<th>{{ $profitLossAfterRefundLabel }}</th>
												<td>
													{{ $profitLossAfterRefundValue }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>



											{{-- ✅ Paid & Due BELOW Profit/Loss --}}
											<tr>
												<th class="table-primary fw-semibold">{{ $getCurrentTranslation['total_paid'] ?? 'total_paid' }}</th>
												<td class="table-primary fw-semibold">
													{{ number_format($total_paid_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											<tr>
												<th class="table-warning fw-semibold">{{ $getCurrentTranslation['total_due'] ?? 'total_due' }}</th>
												<td class="table-warning fw-semibold">
													{{ number_format($total_due_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						{{-- ================= PAYMENT STATUS ================= --}}
						<div class="col-12">
							<div class="card shadow-sm mb-4">
								<div class="card-header bg-info text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['summary_by_payment_status'] ?? 'summary_by_payment_status' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center align-middle mb-0">
										<thead>
											<tr>
												<th class="bg-light">{{ $getCurrentTranslation['payment_status'] ?? 'payment_status' }}</th>
												<th class="bg-light">{{ $getCurrentTranslation['total_count'] ?? 'total_count' }}</th>
												<th class="bg-light">{{ $getCurrentTranslation['total_purchase_amount'] ?? 'total_purchase_amount' }}</th>
												<th class="bg-light">{{ $getCurrentTranslation['total_selling_amount'] ?? 'total_selling_amount' }}</th>
												<th class="table-danger">{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'total_cancellation_fee' }}</th>
												<th class="table-success table-danger-text">{{ $getCurrentTranslation['total_profit_loss'] ?? 'total_profit_loss' }}</th>
												<th class="table-primary">{{ $getCurrentTranslation['total_paid'] ?? 'total_paid' }}</th>
												<th class="table-warning">{{ $getCurrentTranslation['total_due'] ?? 'total_due' }}</th>
											</tr>
										</thead>
										<tbody>
											@if(count($paymentStatusSummary))
												@foreach ($paymentStatusSummary as $status => $data)
													@php
														// Calculate net profit after subtracting cancellation fee
														$netProfit = $data['total_profit'] - ($data['total_cancellation_fee'] ?? 0);

														$isProfit = $netProfit >= 0;
														$profitLossClass = $isProfit ? 'table-success' : 'table-danger';
														$profitLossValue = $isProfit
															? number_format($netProfit, 2)
															: '-' . number_format(abs($netProfit), 2);
													@endphp

													<tr>
														<td>{{ $status }}</td>
														<td>{{ $data['count'] }}</td>
														<td>
															{{ number_format($data['total_purchase_amount'], 2) }}
															{{ Auth::user()->company_data->currency->short_name ?? '' }}
														</td>
														<td>
															{{ number_format($data['total_selling_amount'], 2) }}
															{{ Auth::user()->company_data->currency->short_name ?? '' }}
														</td>

														<td class="table-danger">
															{{ number_format($data['total_cancellation_fee'], 2) }}
															{{ Auth::user()->company_data->currency->short_name ?? '' }}
														</td>

														<td class="fw-semibold {{ $profitLossClass }}">
															{{ $profitLossValue }}
															{{ Auth::user()->company_data->currency->short_name ?? '' }}
														</td>

														<td class="table-primary fw-semibold">
															{{ number_format($data['total_paid_amount'], 2) }}
															{{ Auth::user()->company_data->currency->short_name ?? '' }}
														</td>

														<td class="table-warning fw-semibold">
															{{ number_format($data['total_due_amount'], 2) }}
															{{ Auth::user()->company_data->currency->short_name ?? '' }}
														</td>
													</tr>
												@endforeach
											@else
												<tr>
													<td colspan="7" class="p-10">
														{{ $getCurrentTranslation['no_data_found_for_selected_filter'] ?? 'no_data_found_for_selected_filter' }}
													</td>
												</tr>
											@endif
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= OTHER SUMMARY ================= --}}
					<div class="row">
						<div class="col-md-12">
							<div class="card shadow-sm mb-4">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">{{ $getCurrentTranslation['summary_overview'] ?? 'summary_overview' }}</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped table-hover align-middle mb-0">
										<tbody>
											<tr><th>{{ $getCurrentTranslation['total_airline'] ?? 'total_airline' }}</th><td>{{ $total_airline }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_introduction_source'] ?? 'total_introduction_source' }}</th><td>{{ $total_introduction_source }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_country'] ?? 'total_country' }}</th><td>{{ $total_country }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_issued_suppliers'] ?? 'total_issued_suppliers' }}</th><td>{{ $total_issued_suppliers }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_issued_by'] ?? 'total_issued_by' }}</th><td>{{ $total_issued_by }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_trip_type'] ?? 'total_trip_type' }}</th><td>{{ $total_trip_type }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_seat_confirmation'] ?? 'total_seat_confirmation' }}</th><td>{{ $total_seat_confirmation }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_mobility_assistance'] ?? 'total_mobility_assistance' }}</th><td>{{ $total_mobility_assistance }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_transit_visa_application'] ?? 'total_transit_visa_application' }}</th><td>{{ $total_transit_visa_application }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_halal_meal_request'] ?? 'total_halal_meal_request' }}</th><td>{{ $total_halal_meal_request }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_transit_hotel'] ?? 'total_transit_hotel' }}</th><td>{{ $total_transit_hotel }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_transfer_to'] ?? 'total_transfer_to' }}</th><td>{{ $total_transfer_to }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_payment_method'] ?? 'total_payment_method' }}</th><td>{{ $total_payment_method }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_issued_card_type'] ?? 'total_issued_card_type' }}</th><td>{{ $total_issued_card_type }}</td></tr>
											<tr><th>{{ $getCurrentTranslation['total_card_owner'] ?? 'total_card_owner' }}</th><td>{{ $total_card_owner }}</td></tr>
										</tbody>
									</table>
								</div>
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

</script>
@endpush