@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
	$getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
	<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
		<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
			<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item">{{ $getCurrentTranslation['flight_api_credit_usage'] ?? 'Flight API Credit Usage' }}</li>
				</ul>
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

	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="card rounded border mt-5 p-0 bg-white">
				<div class="accordion" id="kt_accordion_flight_api_credit">
					<div class="accordion-item">
						<h2 class="accordion-header" id="kt_accordion_flight_api_credit_header">
							<button class="accordion-button fs-4 fw-semibold bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#kt_accordion_flight_api_credit_body" aria-expanded="true" aria-controls="kt_accordion_flight_api_credit_body">
								<i class="fa fa-filter" aria-hidden="true"></i> &nbsp;
								{{ $getCurrentTranslation['filter'] ?? 'filter' }}
							</button>
						</h2>
						<div id="kt_accordion_flight_api_credit_body" class="accordion-collapse collapse show" aria-labelledby="kt_accordion_flight_api_credit_header" data-bs-parent="#kt_accordion_flight_api_credit">
							<div class="accordion-body">
								<form class="filter-data-form" method="get">
									<div class="row">
										<div class="col-md-4 mb-3">
											<div class="input-item-wrap mb-5">
												<label class="form-label">{{ $getCurrentTranslation['date_range'] ?? 'date_range' }}:</label>
												<div class="daterange-picker-wrap form-control d-flex justify-content-between align-items-center">
													@php
														$selectedDateRange = $defaultDateRange ?? '';
													@endphp
													<div class="cursor-pointer dateRangePicker {{$selectedDateRange ? 'filled' : 'empty'}}">
														<i class="fa fa-calendar"></i>&nbsp;
														<span></span> <i class="fa fa-caret-down"></i>
														<input autocomplete="off" class="col-sm-12 form-control dateRangeInput" name="date_range" data-value="{{$selectedDateRange ?? ''}}" style="position:absolute;top:0;left:0;width:100%;z-index:-999999;opacity:0;" />
													</div>
													<span class="clear-date-range"><i class="fa fa-times"></i></span>
												</div>
											</div>
										</div>
										<div class="col-md-4 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['used_for'] ?? 'Used for' }}:</label>
												<select class="form-select" name="used_for" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
													<option value="all" {{ (isset($usedFor) && ($usedFor === 'all' || $usedFor === '' || $usedFor === null)) ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all'] ?? 'All' }}
													</option>
													@foreach($usedForOptions as $opt)
														<option value="{{ $opt }}" {{ (isset($usedFor) && (string) $usedFor === $opt) ? 'selected' : '' }}>
															@if($opt === \App\Models\FlightApiCreditUsage::USED_FOR_TICKET_SEARCH)
																{{ $getCurrentTranslation['flight_api_used_for_flight_ticket'] ?? 'Flight Ticket' }}
															@else
																{{ $getCurrentTranslation['flight_api_used_for_flight_status'] ?? 'Flight Status' }}
															@endif
														</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-4 mb-3">
											<div class="input-item">
												<label class="form-label">{{ $getCurrentTranslation['user'] ?? 'User' }}:</label>
												<select class="form-select" name="credit_used_by" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
													<option value="all" {{ (isset($creditUserId) && ($creditUserId === 'all' || $creditUserId === '' || $creditUserId === null)) ? 'selected' : '' }}>
														{{ $getCurrentTranslation['all'] ?? 'All' }}
													</option>
													@foreach($users as $u)
														<option value="{{ $u->id }}" {{ (isset($creditUserId) && (string) $creditUserId === (string) $u->id) ? 'selected' : '' }}>
															{{ $u->name }}
															({{ $u->designation?->name ?? 'N/A' }})
														</option>
													@endforeach
												</select>
											</div>
										</div>
										<div class="col-md-12">
											<div class="d-flex justify-content-end mt-0">
												<a class="btn btn-secondary btn-sm me-3" href="{{ route('admin.flight_api_credit_usage.index') }}">
													{{ $getCurrentTranslation['reset'] ?? 'reset' }}
												</a>
												<button type="submit" class="btn btn-primary btn-sm">
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

			<div class="card rounded border mt-5 p-10 bg-white">
				<div class="card-header p-0" style="min-height: unset">
					<h3 class="card-title mb-3 mt-0">
						{{ $getCurrentTranslation['flight_api_credit_usage'] ?? 'Flight API Credit Usage' }}
					</h3>
				</div>
				<div class="card-body px-0">
					<div class="row">
						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-primary text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_records'] ?? 'Total records' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0">{{ $totalRows }}</h2>
								</div>
							</div>
						</div>
						<div class="col-md-6 mb-4">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-danger text-white fw-bold align-items-center">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['total_credits_used'] ?? 'Total credits used' }}
									</h5>
								</div>
								<div class="card-body text-center">
									<h2 class="mb-0 text-danger">{{ $totalCredits }}</h2>
								</div>
							</div>
						</div>

						<div class="col-md-12">
							<div class="card shadow-sm h-100">
								<div class="card-header bg-info text-white fw-bold align-items-center min-h-auto py-4">
									<h5 class="mb-0 text-white">
										{{ $getCurrentTranslation['note'] ?? 'note' }}
									</h5>
								</div>
								<div class="card-body text-center py-4">
									<div class="mt-0 text-start small text-muted">
										<ul class="mb-0 ps-5">
											<li>{{ $getCurrentTranslation['flight_api_credit_note'] ?? 'Ticket search: 2 credits per request. Flight status: 1 credit per segment check.' }}</li>
											<li>{{ $getCurrentTranslation['flight_api_credit_usage_hint'] ?? 'Only FlightAPI (flightapi.io) usage is recorded when the API key is configured.' }}</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="table-responsive mt-5">
						<table class="table table-bordered table-striped table-hover align-middle mb-0 report-table">
							<thead class="table-secondary">
								<tr>
									<th class="fw-semibold ps-3">#</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['credit_amount'] ?? 'Credits' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['used_for'] ?? 'Used for' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['user'] ?? 'User' }}</th>
									<th class="fw-semibold">{{ $getCurrentTranslation['usage_date_time'] ?? 'Usage date & time' }}</th>
								</tr>
							</thead>
							<tbody>
								@forelse($rows as $row)
									<tr>
										<td class="ps-3">{{ $rows->firstItem() + $loop->index }}</td>
										<td class="fw-bold">{{ $row->credit_amount }}</td>
										<td>
											@if($row->used_for === \App\Models\FlightApiCreditUsage::USED_FOR_TICKET_SEARCH)
												{{ $getCurrentTranslation['flight_api_used_for_flight_ticket'] ?? 'Flight Ticket' }}
											@else
												{{ $getCurrentTranslation['flight_api_used_for_flight_status'] ?? 'Flight Status' }}
											@endif
										</td>
										<td>
											@if($row->user)
												{{ $row->user->name }}
											@else
												<span class="text-muted">—</span>
											@endif
										</td>
										<td>{{ $row->usage_date_time ? $row->usage_date_time->format('Y-m-d H:i:s') : 'N/A' }}</td>
									</tr>
								@empty
									<tr>
										<td colspan="5" class="p-10 text-center">
											{{ $getCurrentTranslation['no_data_found'] ?? 'No data found' }}
										</td>
									</tr>
								@endforelse
							</tbody>
							@if($rows->count() > 0)
							<tfoot class="table-secondary">
								<tr>
									<td colspan="1" class="fw-bold text-end ps-3">
										{{ $getCurrentTranslation['total'] ?? 'Total' }}:
									</td>
									<td class="fw-bold">
										<strong class="text-danger">{{ $totalCredits }}</strong>
									</td>
									<td colspan="3"></td>
								</tr>
							</tfoot>
							@endif
						</table>
					</div>
					@if($rows->hasPages())
						<div class="mt-5 d-flex justify-content-end">
							{{ $rows->links() }}
						</div>
					@endif
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.dateRangeScripts')
@endpush
