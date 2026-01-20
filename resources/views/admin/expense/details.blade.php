@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')

<style>
    .editor-address * {
        margin: 0;
    }
</style>

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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['expense_list'] ?? 'expense_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['expense_details'] ?? 'expense_details' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
                @if (hasPermission('expense.edit') && isset($editRoute) && !empty($editRoute))
                    <a href="{{ $editRoute }}" class="btn btn-sm fw-bold btn-primary">
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
            <!-- Expense Information -->
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">
                        {{ $getCurrentTranslation['expense_informations'] ?? 'expense_informations' }}
                    </h5>
                </div>
                <div class="card-body">
                   <div class="container-fluid py-5">
                        <div class="row">
                            <div class="col-12">
                                <!-- Basic Information -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-primary text-white">
                                        <h5 class="mb-0 text-light">{{ $getCurrentTranslation['basic_informations'] ?? 'basic_informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['expense_category'] ?? 'expense_category' }}:</strong>
                                                <p>{{ $editData->category?->name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['for'] ?? 'for' }}:</strong>
                                                <p>
                                                    @if($editData->forUser)
                                                        {{ $editData->forUser->name }}
                                                        @if($editData->forUser->designation)
                                                            ({{ $editData->forUser->designation->name }})
                                                        @endif
                                                        @if($editData->forUser->is_staff == 0)
                                                            - <span class="badge bg-warning text-dark">{{ $getCurrentTranslation['non_staff'] ?? 'Non Staff' }}</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary">{{ $getCurrentTranslation['none'] ?? 'None' }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['title'] ?? 'title' }}:</strong>
                                                <p>{{ $editData->title ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['amount'] ?? 'amount' }}:</strong>
                                                <p>
                                                    {{ number_format($editData->amount ?? 0, 2) }}
                                                    {{ Auth::user()->company_data->currency->short_name ?? '' }}
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['expense_date'] ?? 'expense_date' }}:</strong>
                                                <p>{{ $editData->expense_date ? \Carbon\Carbon::parse($editData->expense_date)->format('Y-m-d') : 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['payment_status'] ?? 'payment_status' }}:</strong>
                                                <p>
                                                    <span class="badge {{ $editData->payment_status == 'Paid' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $editData->payment_status ?? 'N/A' }}
                                                    </span>
                                                </p>
                                            </div>
                                            @if($editData->payment_method)
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['payment_method'] ?? 'payment_method' }}:</strong>
                                                <p>{{ $editData->payment_method }}</p>
                                            </div>
                                            @endif
                                            @if($editData->reference_number)
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['reference_number'] ?? 'reference_number' }}:</strong>
                                                <p>{{ $editData->reference_number }}</p>
                                            </div>
                                            @endif
                                            @if($editData->description)
                                            <div class="col-md-12 mb-3">
                                                <strong>{{ $getCurrentTranslation['description'] ?? 'description' }}:</strong>
                                                <div class="editor-address">{!! $editData->description ?? 'N/A' !!}</div>
                                            </div>
                                            @endif
                                            @if($editData->notes)
                                            <div class="col-md-12 mb-3">
                                                <strong>{{ $getCurrentTranslation['notes'] ?? 'notes' }}:</strong>
                                                <div class="editor-address">{!! $editData->notes ?? 'N/A' !!}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Expense Documents -->
                                @if($editData->documents && $editData->documents->count())
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-warning text-dark">
                                        <h5 class="mb-0">{{ $getCurrentTranslation['expense_documents'] ?? 'expense_documents' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach($editData->documents as $doc)
                                                <div class="col-md-4 mb-4">
                                                    <div class="card border h-100">
                                                        <div class="card-body">
                                                            <h6 class="card-title">{{ $doc->document_name ?? ($getCurrentTranslation['document'] ?? 'document') }}</h6>
                                                            @if($doc->description)
                                                                <p class="text-muted small">{{ $doc->description }}</p>
                                                            @endif
                                                            @php
                                                                $fileUrl = $doc->document_file_url ?? '';
                                                                $extension = strtolower($doc->document_type ?? '');
                                                                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                                                $isImage = in_array($extension, $imageExtensions);
                                                                $isPdf = $extension === 'pdf';
                                                            @endphp
                                                            @if($fileUrl)
                                                                @if($isImage)
                                                                    <div class="mt-2">
                                                                        <div class="append-prev mf-prev hover-effect m-0" data-src="{{ $fileUrl }}">
                                                                            <img src="{{ $fileUrl }}" alt="Document" style="max-height:150px; max-width:100%; object-fit:contain; border-radius: 4px;">
                                                                        </div>
                                                                    </div>
                                                                @elseif($isPdf)
                                                                    <div class="mt-2 text-center">
                                                                        <div class="append-prev mf-prev hover-effect m-0" data-src="{{ $fileUrl }}">
                                                                            <a href="javascript:void(0);" class="btn btn-sm btn-danger">
                                                                                <i class="fas fa-file-pdf fa-2x"></i>
                                                                                <br>
                                                                                <small>{{ $getCurrentTranslation['view_pdf'] ?? 'view_pdf' }}</small>
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    <div class="mt-2 text-center">
                                                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-secondary">
                                                                            <i class="fas fa-file-alt fa-2x"></i>
                                                                            <br>
                                                                            <small>{{ $getCurrentTranslation['view_file'] ?? 'view_file' }}</small>
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Audit Information -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-dark">
                                        <h5 class="mb-0 text-light">{{ $getCurrentTranslation['audit_informations'] ?? 'audit_informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['created_at'] ?? 'created_at' }}:</strong>
                                                <p>{{ $editData->created_at ? \Carbon\Carbon::parse($editData->created_at)->format('Y-m-d H:i:s') : 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['updated_at'] ?? 'updated_at' }}:</strong>
                                                <p>{{ $editData->updated_at ? \Carbon\Carbon::parse($editData->updated_at)->format('Y-m-d H:i:s') : 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['created_by'] ?? 'created_by' }}:</strong>
                                                <p>{{ $editData->creator?->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
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
@include('common._partials.appendJs')
@include('common._partials.formScripts')
@endpush
