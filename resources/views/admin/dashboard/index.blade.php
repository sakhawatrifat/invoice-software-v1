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