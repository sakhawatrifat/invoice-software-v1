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
					<li class="breadcrumb-item">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
                @if (hasPermission('hotel.invoice.mail'))
                    <a href="{{ route('hotel.invoice.mail', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-envelope"></i>
                        {{ $getCurrentTranslation['mail'] ?? 'mail' }} ({{ $editData->mail_sent_count ?? 0 }})
                    </a>
                @endif
                @if (hasPermission('hotel.invoice.edit'))
                    <a href="{{ route('hotel.invoice.edit', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
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

            <!-- Invoice Information -->
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">
                        {{ $getCurrentTranslation['invoice'] ?? 'invoice' }} &nbsp;
                        @if($editData->invoice_status == 'Final')
                            <span class="badge badge-primary">{{ $getCurrentTranslation['final'] ?? 'final' }}</span>
                        @else
                            <span class="badge badge-secondary">{{ $getCurrentTranslation['draft'] ?? 'draft' }}</span>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                    	<a href="{{ route('hotel.invoice.downloadPdf', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">{{ $getCurrentTranslation['download'] ?? 'download' }}</a>
                    </div>
                </div>
                <!-- Profile Information -->
                <div class="card-body">
                    @php
                        $passenger = null;
                    @endphp
                    @include('common.hotel-invoice.includes.invoice',[
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
	//
</script>
@endpush