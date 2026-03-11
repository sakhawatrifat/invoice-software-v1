@php
    $getCurrentTranslation = getCurrentTranslation();
@endphp

<form method="post" action="{{ $saveRoute }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="is_ajax_modal" value="1">
    <input type="hidden" name="ajax_context" value="sticky_note_drawer">

    <div class="row">
        <div class="col-md-12">
            <div class="form-item mb-5">
                <label class="form-label">{{ $getCurrentTranslation['note_title'] ?? 'note_title' }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="note_title" ip-required
                       placeholder="{{ $getCurrentTranslation['note_title_placeholder'] ?? 'note_title_placeholder' }}"
                       value="{{ old('note_title') ?? '' }}"/>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-item mb-5">
                <label class="form-label">{{ $getCurrentTranslation['note_description'] ?? 'note_description' }}</label>
                <textarea class="form-control" name="note_description" rows="4"
                          placeholder="{{ $getCurrentTranslation['note_description_placeholder'] ?? 'note_description_placeholder' }}">{{ old('note_description') ?? '' }}</textarea>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-item mb-5">
                <label class="form-label">{{ $getCurrentTranslation['deadline'] ?? 'deadline' }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control datetimepicker" name="deadline" id="sticky_note_deadline_ajax" readonly ip-required
                       placeholder="{{ $getCurrentTranslation['deadline_placeholder'] ?? 'deadline_placeholder' }}"
                       value="{{ old('deadline') ?? '' }}"/>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-item mb-5">
                <label class="form-label">{{ $getCurrentTranslation['reminder_datetime'] ?? 'reminder_datetime' }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control datetimepicker" name="reminder_datetime" id="sticky_note_reminder_datetime_ajax" readonly ip-required
                       placeholder="{{ $getCurrentTranslation['reminder_datetime_placeholder'] ?? 'reminder_datetime_placeholder' }}"
                       value="{{ old('reminder_datetime') ?? '' }}"/>
                <small class="text-muted">{{ $getCurrentTranslation['reminder_before_deadline_help'] ?? 'Reminder must be on or before the deadline.' }}</small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-item mb-5">
                @php
                    $priorityOptions = ['Highest', 'Medium', 'Lower', 'Optional'];
                    $prioritySelected = old('priority') ?? 'Medium';
                @endphp
                <label class="form-label">{{ $getCurrentTranslation['priority'] ?? 'priority' }}:</label>
                <select class="form-select" name="priority" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_priority'] ?? 'select_priority' }}">
                    @foreach($priorityOptions as $opt)
                        <option value="{{ $opt }}" {{ (string)$opt === (string)$prioritySelected ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-item mb-5">
                @php
                    $statusOptions = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
                    $statusSelected = old('status') ?? 'Pending';
                @endphp
                <label class="form-label">{{ $getCurrentTranslation['status'] ?? 'status' }}:</label>
                <select class="form-select" name="status" data-control="select2" data-placeholder="{{ $getCurrentTranslation['select_an_option'] ?? 'select_an_option' }}">
                    @foreach($statusOptions as $opt)
                        <option value="{{ $opt }}" {{ (string)$opt === (string)$statusSelected ? 'selected' : '' }}>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-item mb-5">
                <label class="form-label">{{ $getCurrentTranslation['assign_to_users'] ?? 'assign_to_users' }}</label>
                <select class="form-select" name="assigned_user_ids[]" multiple data-control="select2"
                        data-placeholder="{{ $getCurrentTranslation['select_users'] ?? 'select_users' }}">
                    @foreach(($assignableUsers ?? collect()) as $u)
                        <option value="{{ $u->id }}" {{ in_array((string)$u->id, (array) old('assigned_user_ids', []), true) ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->email ?? '' }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">{{ $getCurrentTranslation['assign_to_users_help'] ?? 'Assign to one or multiple users. They can view and manage this note.' }}</small>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end my-10">
        <button type="submit" class="btn btn-primary ajax-modal-form-submit-btn">
            <span class="indicator-label">{{ $getCurrentTranslation['save_data'] ?? 'save_data' }}</span>
        </button>
    </div>
</form>

