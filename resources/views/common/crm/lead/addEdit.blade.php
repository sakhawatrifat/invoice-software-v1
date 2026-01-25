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
                        @if(isset($lead))
                            {{ $getCurrentTranslation['edit_lead'] ?? 'edit_lead' }}
                        @else
                            {{ $getCurrentTranslation['create_lead'] ?? 'create_lead' }}
                        @endif
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @if(isset($listRoute) && !empty($listRoute))
                    <a href="{{ $listRoute }}" class="btn btn-sm fw-bold btn-primary">
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ $getCurrentTranslation['back_to_list'] ?? 'back_to_list' }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-fluid">
            <form method="post" action="{{ $saveRoute }}">
                @csrf
                @if(isset($lead))
                    @method('put')
                @endif

                <div class="col-md-10 m-auto">
                    <div class="card rounded border mt-5 bg-white">
                        <div class="card-header">
                            <h3 class="card-title">
                                @if(isset($lead))
                                    {{ $getCurrentTranslation['edit_lead'] ?? 'edit_lead' }}
                                @else
                                    {{ $getCurrentTranslation['create_lead'] ?? 'create_lead' }}
                                @endif
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['customer_full_name_label'] ?? 'customer_full_name_label' }}:</label>
                                        <input type="text"
                                               class="form-control"
                                               name="customer_full_name"
                                               placeholder="{{ $getCurrentTranslation['customer_full_name_placeholder'] ?? 'customer_full_name_placeholder' }}"
                                               value="{{ old('customer_full_name', $lead->customer_full_name ?? '') }}"
                                               required>
                                        @error('customer_full_name')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['lead_email_label'] ?? 'lead_email_label' }}:</label>
                                        <input type="email"
                                               class="form-control"
                                               name="email"
                                               placeholder="{{ $getCurrentTranslation['lead_email_placeholder'] ?? 'lead_email_placeholder' }}"
                                               value="{{ old('email', $lead->email ?? '') }}">
                                        @error('email')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['lead_phone_label'] ?? 'lead_phone_label' }}:</label>
                                        <input type="text"
                                               class="form-control"
                                               name="phone"
                                               placeholder="{{ $getCurrentTranslation['lead_phone_placeholder'] ?? 'lead_phone_placeholder' }}"
                                               value="{{ old('phone', $lead->phone ?? '') }}">
                                        @error('phone')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['company_name_label'] ?? 'company_name_label' }}:</label>
                                        <input type="text"
                                               class="form-control"
                                               name="company_name"
                                               placeholder="{{ $getCurrentTranslation['company_name_placeholder'] ?? 'company_name_placeholder' }}"
                                               value="{{ old('company_name', $lead->company_name ?? '') }}">
                                        @error('company_name')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['website_label'] ?? 'website_label' }}:</label>
                                        <input type="text"
                                               class="form-control"
                                               name="website"
                                               placeholder="{{ $getCurrentTranslation['website_placeholder'] ?? 'website_placeholder' }}"
                                               value="{{ old('website', $lead->website ?? '') }}">
                                        @error('website')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['lead_source_label'] ?? 'lead_source_label' }}:</label>
                                        <select name="source_id"
                                                class="form-select"
                                                data-control="select2"
                                                data-placeholder="{{ $getCurrentTranslation['lead_source_placeholder'] ?? 'lead_source_placeholder' }}">
                                            <option value="">{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}</option>
                                            @foreach($leadSources as $source)
                                                <option value="{{ $source->id }}"
                                                    {{ (string) $source->id === (string) old('source_id', $lead->source_id ?? '') ? 'selected' : '' }}>
                                                    {{ $source->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('source_id')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        @php
                                            $statusOptions = ['New', 'Contacted', 'Qualified', 'Lost', 'Converted To Customer'];
                                            $selectedStatus = old('status', $lead->status ?? '');
                                        @endphp
                                        <label class="form-label">{{ $getCurrentTranslation['lead_status_label'] ?? 'lead_status_label' }}:</label>
                                        <select name="status"
                                                class="form-select"
                                                data-control="select2"
                                                data-placeholder="{{ $getCurrentTranslation['lead_status_placeholder'] ?? 'lead_status_placeholder' }}">
                                            <option value="">{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}</option>
                                            @foreach($statusOptions as $option)
                                                <option value="{{ $option }}" {{ $option == $selectedStatus ? 'selected' : '' }}>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        @php
                                            $priorityOptions = ['Low', 'Medium', 'High'];
                                            $selectedPriority = old('priority', $lead->priority ?? '');
                                        @endphp
                                        <label class="form-label">{{ $getCurrentTranslation['lead_priority_label'] ?? 'lead_priority_label' }}:</label>
                                        <select name="priority"
                                                class="form-select"
                                                data-control="select2"
                                                data-placeholder="{{ $getCurrentTranslation['lead_priority_placeholder'] ?? 'lead_priority_placeholder' }}">
                                            <option value="">{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}</option>
                                            @foreach($priorityOptions as $option)
                                                <option value="{{ $option }}" {{ $option == $selectedPriority ? 'selected' : '' }}>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('priority')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['assigned_to_label'] ?? 'assigned_to_label' }}:</label>
                                        <select name="assigned_to"
                                                class="form-select"
                                                data-control="select2"
                                                data-placeholder="{{ $getCurrentTranslation['assigned_to_placeholder'] ?? 'assigned_to_placeholder' }}">
                                            <option value="">{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}</option>
                                            @foreach($users as $u)
                                                <option value="{{ $u->id }}" {{ (string) $u->id === (string) old('assigned_to', $lead->assigned_to ?? '') ? 'selected' : '' }}>
                                                    {{ $u->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('assigned_to')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['last_contacted_at_label'] ?? 'last_contacted_at_label' }}:</label>
                                        <input type="text"
                                               class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour"
                                               name="last_contacted_at"
                                               placeholder="{{ $getCurrentTranslation['last_contacted_at_placeholder'] ?? 'Select Date & Time' }}"
                                               value="{{ old('last_contacted_at', (isset($lead) && $lead->last_contacted_at) ? $lead->last_contacted_at->format('Y-m-d H:i') : '') }}"/>
                                        @error('last_contacted_at')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['converted_customer_at_label'] ?? 'converted_customer_at_label' }}:</label>
                                        <input type="text"
                                               class="form-control mb-2 append-datepicker flatpickr-input datetime 12-hour"
                                               name="converted_customer_at"
                                               placeholder="{{ $getCurrentTranslation['converted_customer_at_placeholder'] ?? 'Select Date & Time' }}"
                                               value="{{ old('converted_customer_at', (isset($lead) && $lead->converted_customer_at) ? $lead->converted_customer_at->format('Y-m-d H:i') : '') }}"/>
                                        @error('converted_customer_at')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['notes_label'] ?? 'notes_label' }}:</label>
                                        <textarea name="notes"
                                                  class="form-control"
                                                  rows="3"
                                                  placeholder="{{ $getCurrentTranslation['notes_placeholder'] ?? 'notes_placeholder' }}">{{ old('notes', $lead->notes ?? '') }}</textarea>
                                        @error('notes')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end my-10">
                        <button type="submit" class="btn btn-primary form-submit-btn ajax-submit">
                            @if(isset($lead))
                                <span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
                            @else
                                <span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
                            @endif
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
@endpush

