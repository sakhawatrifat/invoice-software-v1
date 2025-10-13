@php
	$getCurrentTranslation = getCurrentTranslation();
@endphp

<form method="post" action="{{ $saveRoute }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="is_ajax_modal" value="1">
    <input type="hidden" name="for_input" value="">
    <div class="row">
        <div class="col-md-12">
            <div class="form-item mb-5">
                <label class="form-label">{{ $getCurrentTranslation['name'] ?? 'name' }}:</label>
                <input type="text" class="form-control" placeholder="{{ $getCurrentTranslation['enter_name'] ?? 'enter_name' }}" name="name" ip-required value="{{ old('name') ?? $editData->name ?? '' }}"/>
                @error('name')
                    <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-12 d-none">
            <div class="mb-5">
                @php
                    $options = [
                        1 => 'Active',
                    ];
                    $selected = $editData->status ?? '';
                @endphp
                <label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}:</label>
                <select name="status" class="form-select" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}" ip-required>
                    @foreach(array_reverse($options, true) as $value => $label)
                        <option value="{{ $value }}" {{ (string)$value === (string)$selected ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end my-10">
        <button type="submit" class="btn btn-primary ajax-modal-form-submit-btn">
            @if(isset($editData))
                <span class="indicator-label">{{ $getCurrentTranslation['update_data'] ?? 'update_data' }}</span>
            @else
                <span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
            @endif
        </button>
    </div>
</form>