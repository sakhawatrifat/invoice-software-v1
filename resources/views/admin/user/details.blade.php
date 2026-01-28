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
							<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['user_list'] ?? 'user_list' }}</a> &nbsp; - 
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['user_details'] ?? 'user_details' }}</li>
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
                @if (isset($editRoute) && !empty($editRoute))
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
            <!-- User Information -->
            <div class="card rounded border mb-4 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">
                        {{ $getCurrentTranslation['user_informations'] ?? 'user_informations' }}
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
                                                <strong>{{ $getCurrentTranslation['user_full_name_label'] ?? 'user_full_name_label' }}:</strong>
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
                                                <strong>{{ $getCurrentTranslation['designation_label'] ?? 'designation_label' }}:</strong>
                                                <p>{{ $editData->designation?->name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['department_label'] ?? 'department_label' }}:</strong>
                                                <p>{{ $editData->department?->name ?? 'N/A' }}</p>
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
                                                        <img src="{{ $editData->image_url }}" alt="User Image" style="max-height:150px; max-width:150px; object-fit:contain; border-radius: 8px;">
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Company Information -->
                                @if($editData->company)
                                <div class="card mb-4">
                                    <div class="card-header align-items-center bg-info text-white">
                                        <h5 class="mb-0 text-light">{{ $getCurrentTranslation['company'] ?? 'company' }} {{ $getCurrentTranslation['informations'] ?? 'informations' }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['company_name_label'] ?? 'company_name_label' }}:</strong>
                                                <p>{{ $editData->company->company_name ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['tagline_label'] ?? 'tagline_label' }}:</strong>
                                                <p>{{ $editData->company->tagline ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['invoice_prefix_label'] ?? 'invoice_prefix_label' }}:</strong>
                                                <p>{{ $editData->company->invoice_prefix ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['company_invoice_id_label'] ?? 'company_invoice_id_label' }}:</strong>
                                                <p>{{ $editData->company->company_invoice_id ?? 'N/A' }}</p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['website_url_label'] ?? 'website_url_label' }}:</strong>
                                                <p>
                                                    @if($editData->company->website_url)
                                                        <a href="{{ $editData->company->website_url }}" target="_blank">{{ $editData->company->website_url }}</a>
                                                    @else
                                                        N/A
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['address_label'] ?? 'address_label' }}:</strong>
                                                <div class="editor-address">{!! $editData->company->address ?? 'N/A' !!}</div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['phone_label'] ?? 'phone_label' }}:</strong>
                                                <p>
                                                    @if($editData->company->phone_1)
                                                        {{ $editData->company->phone_1 }}
                                                        @if($editData->company->phone_2)
                                                            , {{ $editData->company->phone_2 }}
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</strong>
                                                <p>
                                                    @if($editData->company->email_1)
                                                        {{ $editData->company->email_1 }}
                                                        @if($editData->company->email_2)
                                                            , {{ $editData->company->email_2 }}
                                                        @endif
                                                    @else
                                                        N/A
                                                    @endif
                                                </p>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <strong>{{ $getCurrentTranslation['currency_label'] ?? 'currency_label' }}:</strong>
                                                <p>{{ $editData->company->currency?->currency_name ?? 'N/A' }}</p>
                                            </div>
                                            @if($editData->company->light_logo_url || $editData->company->dark_logo_url)
                                            <div class="col-md-6 mb-3">
                                                <strong>{{ $getCurrentTranslation['logo_label'] ?? 'logo_label' }}:</strong>
                                                <div class="mt-2 d-flex gap-3">
                                                    @if($editData->company->light_logo_url)
                                                        <div>
                                                            <small>Light Logo:</small><br>
                                                            <img src="{{ $editData->company->light_logo_url }}" alt="Light Logo" style="max-height:100px; max-width:100px; object-fit:contain; border-radius: 4px;">
                                                        </div>
                                                    @endif
                                                    @if($editData->company->dark_logo_url)
                                                        <div>
                                                            <small>Dark Logo:</small><br>
                                                            <img src="{{ $editData->company->dark_logo_url }}" alt="Dark Logo" style="max-height:100px; max-width:100px; object-fit:contain; border-radius: 4px;">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
                                            @if($editData->company->light_icon_url || $editData->company->dark_icon_url)
                                            <div class="col-md-6 mb-3">
                                                <strong>{{ $getCurrentTranslation['icon_label'] ?? 'icon_label' }}:</strong>
                                                <div class="mt-2 d-flex gap-3">
                                                    @if($editData->company->light_icon_url)
                                                        <div>
                                                            <small>Light Icon:</small><br>
                                                            <img src="{{ $editData->company->light_icon_url }}" alt="Light Icon" style="max-height:100px; max-width:100px; object-fit:contain; border-radius: 4px;">
                                                        </div>
                                                    @endif
                                                    @if($editData->company->dark_icon_url)
                                                        <div>
                                                            <small>Dark Icon:</small><br>
                                                            <img src="{{ $editData->company->dark_icon_url }}" alt="Dark Icon" style="max-height:100px; max-width:100px; object-fit:contain; border-radius: 4px;">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
                                            @if($editData->company->light_seal_url || $editData->company->dark_seal_url)
                                            <div class="col-md-6 mb-3">
                                                <strong>{{ $getCurrentTranslation['seal_label'] ?? 'seal_label' }}:</strong>
                                                <div class="mt-2 d-flex gap-3">
                                                    @if($editData->company->light_seal_url)
                                                        <div>
                                                            <small>Light Seal:</small><br>
                                                            <img src="{{ $editData->company->light_seal_url }}" alt="Light Seal" style="max-height:100px; max-width:100px; object-fit:contain; border-radius: 4px;">
                                                        </div>
                                                    @endif
                                                    @if($editData->company->dark_seal_url)
                                                        <div>
                                                            <small>Dark Seal:</small><br>
                                                            <img src="{{ $editData->company->dark_seal_url }}" alt="Dark Seal" style="max-height:100px; max-width:100px; object-fit:contain; border-radius: 4px;">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
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
