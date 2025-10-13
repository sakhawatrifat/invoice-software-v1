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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['ticket_list'] ?? 'ticket_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['edit_ticket'] ?? 'edit_ticket' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
                @if (hasPermission('ticket.mail'))
                    <a href="{{ route('ticket.mail', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-envelope"></i>
                        {{ $getCurrentTranslation['mail'] ?? 'mail' }}
                    </a>
                @endif
                @if (hasPermission('ticket.edit'))
                    <a href="{{ route('ticket.edit', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i>
                        {{ $getCurrentTranslation['edit'] ?? 'edit' }}
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
            <style>
                .inv-footer-description ul, 
                .inv-footer-description ol {
                    padding-left: 20px!important;
                }
            </style>
			<!-- Ticket Information -->
            @if($editData && $editData->document_type == 'ticket')
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h4 class="card-title">{{ $getCurrentTranslation['ticket'] ?? 'ticket' }}</h4>
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $getCurrentTranslation['download'] ?? 'download' }}
                            </button>
                            <div class="dropdown-menu p-0">
                                <a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=1&layout={{$ticketLayoutId}}" class="dropdown-item btn btn-sm fw-bold btn-success pdf-generator-btn">{{ $getCurrentTranslation['with_price'] ?? 'with_price' }}</a>
                                <a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0&layout={{$ticketLayoutId}}" class="dropdown-item btn btn-sm fw-bold btn-info pdf-generator-btn">{{ $getCurrentTranslation['without_price'] ?? 'without_price' }}</a>

                                @if(count($editData->passengers) > 0)
                                    @foreach($editData->passengers as $passenger)
                                        <a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0&passenger={{ $passenger->id }}&layout={{$ticketLayoutId}}" class="dropdown-item btn btn-sm fw-bold btn-info pdf-generator-btn">
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
                    </div>
                </div>
                <!-- Profile Information -->
                <div class="card-body">
                    @php
                        $passenger = null;
                        if(!isset($ticketLayout)){
                            $ticketLayout = 'common.ticket.includes.ticket-1';
                        }
                    @endphp
                    @include($ticketLayout, [
                        'editData' => $editData, 
                        'view' => 1
                    ])
                </div>
            </div>
            @endif


            <!-- Invoice Information -->
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</h5>
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                    	<a href="{{ route('ticket.downloadPdf', $editData->id) }}?invoice=1&withPrice=1" class="btn btn-sm fw-bold btn-primary pdf-generator-btn">{{ $getCurrentTranslation['download'] ?? 'download' }}</a>
                    </div>
                </div>
                <!-- Profile Information -->
                <div class="card-body">
                    @php
                        $passenger = null;
                    @endphp
                    @include('common.ticket.includes.invoice',[
                        'editData' => $editData, 
                        'view' => 1
                    ])
                </div>
            </div>


		</div>
		<!--end::Content container-->
	</div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
<script>
	$(document).on("click", ".pdf-generator-btn", function () {
        $('.r-preloader').show();
    });
</script>
@endpush