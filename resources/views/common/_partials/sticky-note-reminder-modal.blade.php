@php
	$getCurrentTranslation = getCurrentTranslation();
@endphp
<div class="modal fade" id="stickyNoteReminderModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark">
                    <i class="fa-solid fa-bell me-2"></i>
                    {{ $getCurrentTranslation['sticky_note_action_required'] ?? 'sticky_note_action_required' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-4">{{ $getCurrentTranslation['notes_with_reminder_due'] ?? 'notes_with_reminder_due' }}</p>
                <div class="list-group" id="sticky-note-reminder-list">
                    @foreach($reminderDueStickyNotes as $sn)
                    <div class="list-group-item d-flex justify-content-between align-items-center sticky-note-reminder-item" data-note-id="{{ $sn->id }}">
                        <div class="flex-grow-1">
                            <a href="{{ route('sticky_note.show', $sn->id) }}" class="text-dark text-hover-primary text-decoration-none">
                                <strong>{{ $sn->note_title }}</strong>
                            </a>
                            <br><small class="text-muted">{{ $getCurrentTranslation['reminder'] ?? 'reminder' }}: {{ $sn->reminder_datetime->format('Y-m-d H:i') }}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-2">
                            <a href="{{ route('sticky_note.show', $sn->id) }}" class="btn btn-sm btn-icon btn-light-primary" title="{{ $getCurrentTranslation['show'] ?? 'show' }}">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <span class="d-inline-flex align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-success btn-acknowledge-note" data-note-id="{{ $sn->id }}" data-url="{{ route('sticky_note.updateStatus', $sn->id) }}" title="{{ $getCurrentTranslation['acknowledge'] ?? 'Acknowledge' }} ({{ $getCurrentTranslation['in_progress'] ?? 'In Progress' }})">
                                    <span class="btn-label">{{ $getCurrentTranslation['acknowledge'] ?? 'Acknowledge' }}</span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                </button>
                                <small class="text-muted">({{ $getCurrentTranslation['in_progress'] ?? 'In Progress' }})</small>
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ $getCurrentTranslation['close'] ?? 'close' }}</button>
                <a href="{{ route('sticky_note.index') }}" class="btn btn-primary">{{ $getCurrentTranslation['view_all_notes'] ?? 'view_all_notes' }}</a>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('stickyNoteReminderModal');
    if (modalEl && document.getElementById('sticky-note-reminder-list') && document.querySelectorAll('.sticky-note-reminder-item').length > 0) {
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    $(document).on('click', '.btn-acknowledge-note', function() {
        var btn = $(this);
        var noteId = btn.data('note-id');
        var url = btn.data('url');
        var $item = btn.closest('.sticky-note-reminder-item');
        var $list = $('#sticky-note-reminder-list');

        if (btn.prop('disabled')) return;
        btn.prop('disabled', true);
        btn.find('.btn-label').addClass('d-none');
        btn.find('.spinner-border').removeClass('d-none');
        $('.r-preloader').show();

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'PUT',
                status: 'In Progress'
            },
            success: function(res) {
                $('.r-preloader').hide();
                if (res.is_success) {
                    $item.fadeOut(200, function() {
                        $(this).remove();
                        if ($list.find('.sticky-note-reminder-item').length === 0) {
                            var m = bootstrap.Modal.getInstance(modalEl);
                            if (m) m.hide();
                        }
                    });
                    if (typeof toastr !== 'undefined') toastr.success(res.message);
                    var $drawer = $('#kt_sticky_note');
                    var url = $drawer.data('upcoming-url');
                    if (url) {
                        $.get(url, function(data) {
                            if (data.html !== undefined) $('#kt_sticky_note_list').html(data.html);
                            if (data.count !== undefined) $('.sticky-note-count').text(data.count);
                            if (data.count_text) $('#kt_sticky_note_count_text').text(data.count_text);
                        });
                    }
                } else {
                    btn.prop('disabled', false);
                    btn.find('.btn-label').removeClass('d-none');
                    btn.find('.spinner-border').addClass('d-none');
                    if (typeof toastr !== 'undefined') toastr.error(res.message || 'Error');
                }
            },
            error: function(xhr) {
                $('.r-preloader').hide();
                btn.prop('disabled', false);
                btn.find('.btn-label').removeClass('d-none');
                btn.find('.spinner-border').addClass('d-none');
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.status === 419 ? (getCurrentTranslation && getCurrentTranslation.csrf_token_mismatch_msg) : 'Something went wrong');
                if (typeof toastr !== 'undefined') toastr.error(msg);
            }
        });
    });
});
</script>
