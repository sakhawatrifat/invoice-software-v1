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
                                {{ $getCurrentTranslation['lead_source_list'] ?? 'lead_source_list' }}
                            </a> &nbsp; -
                        </li>
                    @endif
                    @if(isset($editData))
                        <li class="breadcrumb-item">
                            {{ $getCurrentTranslation['edit_lead_source'] ?? 'edit_lead_source' }}
                        </li>
                    @else
                        <li class="breadcrumb-item">
                            {{ $getCurrentTranslation['create_lead_source'] ?? 'create_lead_source' }}
                        </li>
                    @endif
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
                @if(isset($editData))
                    @method('put')
                @endif

                <div class="col-md-8 col-lg-6 m-auto">
                    <div class="card rounded border mt-5 bg-white">
                        <div class="card-header">
                            <h3 class="card-title">
                                @if(isset($editData))
                                    {{ $getCurrentTranslation['edit_lead_source'] ?? 'edit_lead_source' }}
                                @else
                                    {{ $getCurrentTranslation['create_lead_source'] ?? 'create_lead_source' }}
                                @endif
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        <label class="form-label">{{ $getCurrentTranslation['name'] ?? 'name' }}:</label>
                                        <input type="text"
                                               class="form-control"
                                               name="name"
                                               placeholder="{{ $getCurrentTranslation['enter_name'] ?? 'enter_name' }}"
                                               value="{{ old('name', $editData->name ?? '') }}"
                                               required>
                                        @error('name')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-item mb-5">
                                        @php
                                            $options = [
                                                0 => 'Inactive',
                                                1 => 'Active',
                                            ];
                                            $selected = old('status', $editData->status ?? 1);
                                        @endphp
                                        <label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}:</label>
                                        <select name="status"
                                                class="form-select"
                                                data-control="select2"
                                                data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
                                            <option value="">{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}</option>
                                            @foreach(array_reverse($options, true) as $value => $label)
                                                <option value="{{ $value }}" {{ (string) $value === (string) $selected ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('status')
                                        <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end my-10">
                        <button type="submit" class="btn btn-primary form-submit-btn ajax-submit">
                            @if(isset($editData))
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

