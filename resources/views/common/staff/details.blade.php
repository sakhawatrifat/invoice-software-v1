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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['staff_list'] ?? 'staff_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['staff_details'] ?? 'staff_details' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
                @if (hasPermission('admin.attendance.report'))
                    <a href="{{ route('admin.attendance.employeeDetails', $editData->id) }}" class="btn btn-sm fw-bold btn-info">
                        <i class="fa-solid fa-calendar-check"></i>
                        {{ $getCurrentTranslation['view_attendance_report'] ?? 'View Attendance Report' }}
                    </a>
                @endif
                @if (hasPermission('admin.salary.index'))
                    <a href="{{ route('admin.salary.staffReport', $editData->id) }}" class="btn btn-sm fw-bold btn-success">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        {{ $getCurrentTranslation['view_salary_report'] ?? 'View Salary Report' }}
                    </a>
                @endif
                @if (hasPermission('staff.edit'))
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
            <!-- Staff Information -->
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">
                        {{ $getCurrentTranslation['staff_informations'] ?? 'staff_informations' }}
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
                                                <strong>{{ $getCurrentTranslation['staff_full_name_label'] ?? 'staff_full_name_label' }}:</strong>
                                                <p>{{ $editData->name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</strong>
                                                <p>{{ $editData->email ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['phone_label'] ?? 'phone_label' }}:</strong>
                                                <p>{{ $editData->phone ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['department_name'] ?? 'department_name' }}:</strong>
                                                <p>{{ $editData->department?->name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['designation_name'] ?? 'designation_name' }}:</strong>
                                                <p>{{ $editData->designation?->name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['joining_date'] ?? 'joining_date' }}:</strong>
                                                <p>{{ $editData->joining_date ? \Carbon\Carbon::parse($editData->joining_date)->format('Y-m-d') : 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['employment_type'] ?? 'employment_type' }}:</strong>
                                                <p>
                                                    @if($editData->employment_type)
                                                        <span class="badge bg-info">{{ $editData->employment_type }}</span>
                                                    @else
                                                        N/A
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['salary_amount'] ?? 'salary_amount' }}:</strong>
                                                <p>{{ $editData->salary_amount ? number_format($editData->salary_amount, 2) : '0.00' }} ({{Auth::user()->company_data->currency->short_name ?? ''}})</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['status'] ?? 'status' }}:</strong>
                                                <p>
                                                    <span class="badge {{ $editData->status === 'Active' ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $editData->status ?? 'N/A' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['address_label'] ?? 'address_label' }}:</strong>
                                                <div class="editor-address">{!! $editData->address ?? 'N/A' !!}</div>
                                            </div>
                                            @if($editData->image_url)
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['image'] ?? 'image' }}:</strong>
                                                <div class="mt-2">
                                                    <div class="append-prev mf-prev hover-effect m-0 mx-5" data-src="{{ $editData->image_url }}">
                                                        <img src="{{ $editData->image_url }}" alt="Staff Image" style="max-height:150px; max-width:150px; object-fit:contain; border-radius: 8px;">
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Company Information -->
                                @if($editData->company_data)
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-info text-white">
                                        <h5 class="mb-0 text-light">{{ $getCurrentTranslation['company'] ?? 'company' }} {{ $getCurrentTranslation['informations'] ?? 'informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['company_name_label'] ?? 'company_name_label' }}:</strong>
                                                <p>{{ $editData->company_data->company_name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['parent_name'] ?? 'parent_name' }}:</strong>
                                                <p>{{ $editData->parent?->name ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- System Settings -->
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-secondary text-white">
                                        <h5 class="mb-0">{{ $getCurrentTranslation['system_settings'] ?? 'system_settings' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['default_language_label'] ?? 'default_language_label' }}:</strong>
                                                <p>
                                                    @if($editData->default_language)
                                                        @if(isset($languageName) && $languageName)
                                                            {{ $languageName }} ({{ $editData->default_language }})
                                                        @else
                                                            {{ $editData->default_language }}
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['user_type'] ?? 'user_type' }}:</strong>
                                                <p>
                                                    <span class="badge {{ $editData->user_type === 'admin' ? 'bg-success' : 'bg-info' }}">
                                                        {{ ucfirst($editData->user_type ?? 'N/A') }}
                                                    </span>
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['email_verified_at_label'] ?? 'email_verified_at_label' }}:</strong>
                                                <p>
                                                    @if($editData->email_verified_at)
                                                        <span class="badge bg-success">{{ \Carbon\Carbon::parse($editData->email_verified_at)->format('Y-m-d') }}</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">{{ $getCurrentTranslation['not_verified'] ?? 'Not Verified' }}</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Documents -->
                                @if($editData->documents && $editData->documents->count())
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-warning text-dark">
                                        <h5 class="mb-0">{{ $getCurrentTranslation['user_documents'] ?? 'user_documents' }}</h5>
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
