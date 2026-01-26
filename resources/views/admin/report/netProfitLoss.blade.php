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
					
					<li class="breadcrumb-item">{{ $getCurrentTranslation['net_profit_loss_report'] ?? 'net_profit_loss_report' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				@php
					$exportUrl = route('admin.netProfitLossReport.exportPdf', request()->all());
				@endphp
				<a href="{{ $exportUrl }}" class="btn btn-sm fw-bold btn-danger" target="_blank">
					<i class="fas fa-file-pdf"></i> {{ $getCurrentTranslation['export_pdf'] ?? 'Export PDF' }}
				</a>
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
									<div class="row align-items-center">
										<div class="col-md-3">
											<div class="input-item-wrap mb-0">
												{{-- <label class="form-label">{{ $getCurrentTranslation['date_range_label'] ?? 'date_range_label' }}:</label> --}}
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														$selectedDateRange = request()->date_range ?? '';
													@endphp
													<div class="cursor-pointer dateRangePicker {{$selectedDateRange ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>

														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="date_range" data-value="{{$selectedDateRange ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
												@error('date_range')
													<span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
												@enderror
											</div>
										</div>

                                        <div class="col-md-3">
                                            <div class="d-flex justify-content-start mt-0">
                                                <a class="btn btn-secondary btn-sm me-3" href="{{ route('admin.netProfitLossReport') }}">
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
						{{ $getCurrentTranslation['net_profit_loss_report'] ?? 'net_profit_loss_report' }}
					</h3>
				</div>
				<div class="card-body px-0">
					@php
						// --- PREPROCESS: CALCULATE PAID & DUE AMOUNTS FOR PAYMENTS ---
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

						// --- GROSS PROFIT CALCULATIONS FROM PAYMENTS ---
						$total_purchase_amount = $profitLossData->sum('total_purchase_price');
						$total_selling_amount = $profitLossData->sum('total_selling_price');
						$total_profit = $profitLossData->sum('total_selling_price') - $profitLossData->sum('total_purchase_price');
						$total_cancellation_fee = $profitLossData->where('is_refund', 1)->sum('cancellation_fee');
						$total_profit_after_refund = $total_profit - $total_cancellation_fee;
						$total_paid_amount = $profitLossData->sum('total_paid');
						$total_due_amount = $profitLossData->sum('due_amount');
						$total_due_data = $profitLossData->where('due_amount', '>', 0);

						// --- SALARY CALCULATIONS ---
						$total_salary_amount = $salaryData->sum('net_salary');
						$total_salary_count = $salaryData->count();
						// Total paid salary = sum of paid_amount (for Paid and Partial statuses)
						$total_paid_salary = $salaryData->sum('paid_amount');
						// Total unpaid salary = sum of (net_salary - paid_amount) for all records
						$total_unpaid_salary = $salaryData->sum(function($salary) {
							return $salary->net_salary - ($salary->paid_amount ?? 0);
						});
						// Total partial amount (remaining unpaid for partial payments)
						$total_partial_salary = $salaryData->where('payment_status', 'Partial')->sum(function($salary) {
							return $salary->net_salary - ($salary->paid_amount ?? 0);
						});

						// --- EXPENSE CALCULATIONS ---
						$total_expense_amount = $expenseData->sum('amount');
						$total_expense_count = $expenseData->count();
						$total_paid_expense = $expenseData->where('payment_status', 'Paid')->sum('amount');
						$total_unpaid_expense = $expenseData->where('payment_status', 'Unpaid')->sum('amount');

						// --- NET PROFIT/LOSS CALCULATION ---
						// Use paid_amount for actual cash flow calculation
						$net_profit_loss = $total_profit_after_refund - $total_paid_salary - $total_paid_expense;
					@endphp

					{{-- ================= GROSS PROFIT LOSS SUMMARY ================= --}}
					<div class="row">
						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-success text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['gross_profit_loss_summary'] ?? 'gross_profit_loss_summary' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center mb-0">
										<tbody>
											<tr>
												<th class="fw-semibold">{{ $getCurrentTranslation['total_purchase'] ?? 'total_purchase' }}</th>
												<td>
													{{ number_format($total_purchase_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											<tr>
												<th class="fw-semibold">{{ $getCurrentTranslation['total_selling'] ?? 'total_selling' }}</th>
												<td>
													{{ number_format($total_selling_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

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
												<th class="fw-semibold">{{ $profitLossLabel }}</th>
												<td>
													{{ $profitLossValue }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

											<tr class="table-warning">
												<th class="fw-semibold">{{ $getCurrentTranslation['total_cancellation_fee'] ?? 'total_cancellation_fee' }}</th>
												<td class="fw-semibold">
													-{{ number_format($total_cancellation_fee, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>

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
												<th class="fw-semibold">{{ $profitLossAfterRefundLabel }}</th>
												<td>
													{{ $profitLossAfterRefundValue }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['paid_and_due_summary'] ?? 'paid_and_due_summary' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center mb-0">
										<tbody>
											<tr>
												<th class="table-primary fw-semibold">{{ $getCurrentTranslation['total_paid'] ?? 'total_paid' }}</th>
												<td class="table-primary fw-semibold">
													{{ number_format($total_paid_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
												<td class="table-primary fw-semibold">
												</td>
											</tr>

											<tr>
											    <th class="table-warning fw-semibold">
											        {{ $getCurrentTranslation['total_due'] ?? 'total_due' }}
											    </th>
											    <td class="table-warning fw-semibold">
											        {{ number_format($total_due_amount, 2) }}
											        {{ Auth::user()->company_data->currency->short_name ?? '' }}
											    </td>
											    <td class="table-warning fw-semibold">
											        <!-- 🔗 Link to open modal -->
											        <a href="#" data-bs-toggle="modal" data-bs-target="#dueListModal" class="ms-2 text-decoration-underline text-primary">
											            {{ $getCurrentTranslation['view_details'] ?? 'view_details' }} ({{count($total_due_data)}})
											        </a>
											    </td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= SALARY SUMMARY ================= --}}
					<div class="row">
						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-danger text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['salary_summary'] ?? 'salary_summary' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center mb-0">
										<tbody>
											<tr>
												<th class="fw-semibold">{{ $getCurrentTranslation['total_salary_count'] ?? 'total_salary_count' }}</th>
												<td>{{ $total_salary_count }}</td>
											</tr>
											<tr>
												<th class="fw-semibold table-danger">{{ $getCurrentTranslation['total_salary_amount'] ?? 'total_salary_amount' }}</th>
												<td class="table-danger fw-semibold">
													-{{ number_format($total_salary_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-warning">{{ $getCurrentTranslation['total_partial_salary'] ?? 'total_partial_salary' }}</th>
												<td class="table-warning fw-semibold">
													-{{ number_format($total_partial_salary, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-success">{{ $getCurrentTranslation['total_paid_salary'] ?? 'total_paid_salary' }}</th>
												<td class="table-success fw-semibold">
													-{{ number_format($total_paid_salary, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-danger">{{ $getCurrentTranslation['total_unpaid_salary'] ?? 'total_unpaid_salary' }}</th>
												<td class="table-danger fw-semibold">
													-{{ number_format($total_unpaid_salary, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						{{-- ================= EXPENSE SUMMARY ================= --}}
						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-warning text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['expense_summary'] ?? 'expense_summary' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center mb-0">
										<tbody>
											<tr>
												<th class="fw-semibold">{{ $getCurrentTranslation['total_expense_count'] ?? 'total_expense_count' }}</th>
												<td>{{ $total_expense_count }}</td>
											</tr>
											<tr>
												<th class="fw-semibold table-warning">{{ $getCurrentTranslation['total_expense_amount'] ?? 'total_expense_amount' }}</th>
												<td class="table-warning fw-semibold">
													-{{ number_format($total_expense_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-success">{{ $getCurrentTranslation['total_paid_expense'] ?? 'total_paid_expense' }}</th>
												<td class="table-success fw-semibold">
													-{{ number_format($total_paid_expense, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-danger">{{ $getCurrentTranslation['total_unpaid_expense'] ?? 'total_unpaid_expense' }}</th>
												<td class="table-danger fw-semibold">
													-{{ number_format($total_unpaid_expense, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					{{-- ================= NET PROFIT LOSS SUMMARY ================= --}}
					<div class="row">
						<div class="col-md-12 mb-4">
							<div class="card shadow-sm">
								<div class="card-header bg-info text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['net_profit_loss_summary'] ?? 'net_profit_loss_summary' }}
									</h5>
								</div>
								<div class="card-body">
									<table class="report-table table table-bordered table-striped text-center mb-0">
										<thead>
											<tr>
												<th class="fw-semibold bg-light"><b>{{ $getCurrentTranslation['title'] ?? 'title' }}</b></th>
												<th class="fw-semibold bg-light"><b>{{ $getCurrentTranslation['amount'] ?? 'amount' }}</b></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<th class="fw-semibold table-success">{{ $getCurrentTranslation['gross_profit_after_refund'] ?? 'gross_profit_after_refund' }}</th>
												<td class="table-success fw-semibold">
													{{ number_format($total_profit_after_refund, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-danger">{{ $getCurrentTranslation['total_salary'] ?? 'total_salary' }}</th>
												<td class="table-danger fw-semibold">
													-{{ number_format($total_salary_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											<tr>
												<th class="fw-semibold table-warning">{{ $getCurrentTranslation['total_expense'] ?? 'total_expense' }}</th>
												<td class="table-warning fw-semibold">
													-{{ number_format($total_expense_amount, 2) }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
											@php
												$isNetProfit = $net_profit_loss >= 0;
												$netProfitLossLabel = $isNetProfit
													? ($getCurrentTranslation['net_profit'] ?? 'net_profit')
													: ($getCurrentTranslation['net_loss'] ?? 'net_loss');
												$netProfitLossClass = $isNetProfit ? 'table-success text-success' : 'table-danger text-danger';
												$netProfitLossValue = $isNetProfit
													? number_format($net_profit_loss, 2)
													: '-' . number_format(abs($net_profit_loss), 2);
											@endphp
											<tr class="fw-bold {{ $netProfitLossClass }}" style="font-size: 1.2em;">
												<th class="fw-semibold">{{ $netProfitLossLabel }}</th>
												<td>
													{{ $netProfitLossValue }}
													{{ Auth::user()->company_data->currency->short_name ?? '' }}
												</td>
											</tr>
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



<!-- 💬 Modal for showing due data list -->
<div class="modal fade" id="dueListModal" tabindex="-1" aria-labelledby="dueListModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold" id="dueListModalLabel">
                    {{ $getCurrentTranslation['remaining_due'] ?? 'remaining_due' }} ({{count($total_due_data)}})
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                @if(!empty($total_due_data) && count($total_due_data) > 0)
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-semibold">#</th>
                                <th class="fw-semibold">{{ $getCurrentTranslation['client_info'] ?? 'client_info' }}</th>
                                <th class="fw-semibold">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</th>
                                <th class="fw-semibold">{{ $getCurrentTranslation['due'] ?? 'due' }}</th>
                                <th class="fw-semibold">{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($total_due_data as $index => $dueData)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                    	<b>{{ $getCurrentTranslation['name'] ?? 'name' }}:</b> {{ $dueData->client_name ?? 'N/A' }}
                                    	@if($dueData->client_email)
                                    		<br>
                                    		<b>{{ $getCurrentTranslation['email'] ?? 'email' }}:</b> {{ $dueData->client_email ?? 'N/A' }}
                                    	@endif
                                    	@if($dueData->client_phone)
                                    		<br>
                                    		<b>{{ $getCurrentTranslation['phone'] ?? 'phone' }}:</b> {{ $dueData->client_phone ?? 'N/A' }}
                                    	@endif
                                    </td>
                                    <td>
                                    	<b>{{$getCurrentTranslation['payment_invoice_id_label'] ?? 'payment_invoice_id_label'}}:</b>
                                    	{{ $dueData->payment_invoice_id ?? 'N/A' }}

                                    	<br>
                                    	<b>{{ $getCurrentTranslation['ticket_invoice_id_label'] ?? 'ticket_invoice_id_label' }}:</b>
                                    	{{ $dueData->ticket->invoice_id ?? 'N/A'; }}

                                    	<br>
                                    	<b>{{ $getCurrentTranslation['reservation_number_label'] ?? 'reservation_number_label' }}:</b>
                                    	{{ $dueData->ticket->reservation_number ?? 'N/A'; }}
                                    	
                                    	<br>
                                    	<b>{{ $getCurrentTranslation['invoice_date_label'] ?? 'invoice_date_label' }}:</b>
                                    	{{ $dueData->invoice_date ? date('Y-m-d', strtotime($dueData->invoice_date)) : 'N/A'; }}
                                    </td>
                                    <td>
                                        {{ number_format($dueData->due_amount ?? 0, 2) }}
                                        {{ Auth::user()->company_data->currency->short_name ?? '' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('payment.show', $dueData->id) }}" 
                                           class="btn btn-sm btn-primary">
                                           View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted mb-0 text-center">No due data found.</p>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>

</script>
@endpush
