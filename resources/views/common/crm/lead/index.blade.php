@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')
<div class=" d-flex flex-column flex-column-fluid">
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
                    <li class="breadcrumb-item">
                        {{ $getCurrentTranslation['lead_list'] ?? 'lead_list' }}
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @if(isset($createRoute) && !empty($createRoute) && hasPermission('lead.create'))
                    <a href="{{ $createRoute }}" class="btn btn-sm fw-bold btn-primary">
                        {{ $getCurrentTranslation['create_lead'] ?? 'create_lead' }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <div class="card rounded border mt-5 p-10 bg-white">
                <div class="card-header p-0" style="min-height: unset">
                    <h3 class="card-title mb-3 mt-0">
                        {{ $getCurrentTranslation['lead_list'] ?? 'lead_list' }}
                    </h3>
                </div>
                <table id="datatable" class="table table-rounded table-striped border gy-7 gs-7">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ $getCurrentTranslation['customer_full_name_label'] ?? 'customer_full_name_label' }}</th>
                            <th>{{ $getCurrentTranslation['lead_email_label'] ?? 'lead_email_label' }}</th>
                            <th>{{ $getCurrentTranslation['lead_phone_label'] ?? 'lead_phone_label' }}</th>
                            <th>{{ $getCurrentTranslation['lead_source_label'] ?? 'lead_source_label' }}</th>
                            <th>{{ $getCurrentTranslation['lead_status_label'] ?? 'lead_status_label' }}</th>
                            <th>{{ $getCurrentTranslation['lead_priority_label'] ?? 'lead_priority_label' }}</th>
                            <th>{{ $getCurrentTranslation['assigned_to_label'] ?? 'assigned_to_label' }}</th>
                            <th>{{ $getCurrentTranslation['created_at'] ?? 'created_at' }}</th>
                            <th>{{ $getCurrentTranslation['action'] ?? 'action' }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
@include('common._partials.formScripts')
<script>
    var dataTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ $dataTableRoute }}',
        searching: true,
        dom: 'lfrtip',
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer_full_name', name: 'customer_full_name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'source_name', name: 'source_name', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false },
            { data: 'priority', name: 'priority' },
            { data: 'assigned_name', name: 'assigned_name', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ]
    });
</script>
@include('common._partials.tableAjaxScripts')
@endpush

