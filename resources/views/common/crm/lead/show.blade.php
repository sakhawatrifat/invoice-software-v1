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
                        <a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}"
                           class="text-muted text-hover-primary">
                            {{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}
                        </a> &nbsp; -
                    </li>
                    @if(isset($listRoute) && !empty($listRoute))
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ $listRoute }}" class="text-muted text-hover-primary">
                                {{ $getCurrentTranslation['lead_list'] ?? 'lead_list' }}
                            </a> &nbsp; -
                        </li>
                    @endif
                    <li class="breadcrumb-item">
                        {{ $getCurrentTranslation['details'] ?? 'details' }}
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @if(isset($customerHistoryUrl) && $customerHistoryUrl)
                    <a href="{{ $customerHistoryUrl }}" class="btn btn-sm fw-bold btn-info">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        {{ $getCurrentTranslation['check_customer_history'] ?? 'Check Customer History' }}
                    </a>
                @endif
                @if(isset($editRoute) && !empty($editRoute))
                    <a href="{{ $editRoute }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-pen-to-square"></i>
                        {{ $getCurrentTranslation['edit_lead'] ?? 'edit_lead' }}
                    </a>
                @endif
                @if(isset($listRoute) && !empty($listRoute))
                    <a href="{{ $listRoute }}" class="btn btn-sm fw-bold btn-light">
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ $getCurrentTranslation['back_to_list'] ?? 'back_to_list' }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="col-xl-10 col-lg-12 m-auto">
                <div class="card rounded border mt-5 bg-white">
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $lead->customer_full_name }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['customer_full_name_label'] ?? 'customer_full_name_label' }}</div>
                                <div class="text-muted">{{ $lead->customer_full_name }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_email_label'] ?? 'lead_email_label' }}</div>
                                <div class="text-muted">{{ $lead->email ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_phone_label'] ?? 'lead_phone_label' }}</div>
                                <div class="text-muted">{{ $lead->phone ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['company_name_label'] ?? 'company_name_label' }}</div>
                                <div class="text-muted">{{ $lead->company_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['website_label'] ?? 'website_label' }}</div>
                                <div class="text-muted">
                                    @if($lead->website)
                                        <a href="{{ $lead->website }}" target="_blank">{{ $lead->website }}</a>
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_source_label'] ?? 'lead_source_label' }}</div>
                                <div class="text-muted">{{ optional($lead->source)->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_status_label'] ?? 'lead_status_label' }}</div>
                                <div class="text-muted">{{ $lead->status ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['lead_priority_label'] ?? 'lead_priority_label' }}</div>
                                <div class="text-muted">{{ $lead->priority ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['assigned_to_label'] ?? 'assigned_to_label' }}</div>
                                <div class="text-muted">{{ optional($lead->assignedUser)->name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['last_contacted_at_label'] ?? 'last_contacted_at_label' }}</div>
                                <div class="text-muted">
                                    {{ $lead->last_contacted_at ? $lead->last_contacted_at->format('Y-m-d H:i') : 'N/A' }}
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['converted_customer_at_label'] ?? 'converted_customer_at_label' }}</div>
                                <div class="text-muted">
                                    {{ $lead->converted_customer_at ? $lead->converted_customer_at->format('Y-m-d H:i') : 'N/A' }}
                                </div>
                            </div>
                            <div class="col-md-12 mb-4">
                                <div class="fw-bold mb-1">{{ $getCurrentTranslation['notes_label'] ?? 'notes_label' }}</div>
                                <div class="text-muted">{!! nl2br(e($lead->notes ?? 'N/A')) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

