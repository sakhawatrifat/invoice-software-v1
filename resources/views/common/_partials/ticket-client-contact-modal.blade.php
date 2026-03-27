@php
    $t = $getCurrentTranslation ?? getCurrentTranslation();
    $clientContactPostUrl = route('ticket.clientContact');
    $csrfFallback = csrf_token();
@endphp

<div class="modal fade" id="ticket_client_contact_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $t['client_contact_modal_title'] ?? ($t['contacted_with_client_label'] ?? 'Client contact') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ticket_client_contact_ticket_id" value="" />
                <div class="mb-3">
                    <label class="form-label d-block">{{ $t['contact_method_label'] ?? 'How did you contact the client?' }} <span class="text-danger">*</span></label>
                    <div class="form-check form-check-custom form-check-solid mb-2">
                        <input class="form-check-input" type="radio" name="ticket_client_contact_type_modal" id="ticket_client_contact_not_yet" value="Not Yet" />
                        <label class="ps-1 form-check-label" for="ticket_client_contact_not_yet">{{ $t['contact_status_not_yet'] ?? 'Not Yet' }}</label>
                    </div>
                    <div class="form-check form-check-custom form-check-solid mb-2">
                        <input class="form-check-input" type="radio" name="ticket_client_contact_type_modal" id="ticket_client_contact_call" value="Call" />
                        <label class="ps-1 form-check-label" for="ticket_client_contact_call">{{ $t['contact_call'] ?? 'Call' }}</label>
                    </div>
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="radio" name="ticket_client_contact_type_modal" id="ticket_client_contact_message" value="Message" />
                        <label class="ps-1 form-check-label" for="ticket_client_contact_message">{{ $t['contact_message'] ?? 'Message' }}</label>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="ticket_client_contact_note_modal">{{ $t['client_contact_note_label'] ?? 'Note' }} <span class="text-muted">({{ $t['optional'] ?? 'optional' }})</span></label>
                    <textarea class="form-control" id="ticket_client_contact_note_modal" rows="4" placeholder="{{ $t['client_contact_note_placeholder'] ?? '' }}"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ $t['cancel'] ?? 'Cancel' }}</button>
                <button type="button" class="btn btn-primary" id="ticket_client_contact_save_btn">{{ $t['save'] ?? 'Save' }}</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.__ticketClientContactModalBound) return;
    window.__ticketClientContactModalBound = true;

    var postUrl = @json($clientContactPostUrl);
    var csrfFallback = @json($csrfFallback);

    function getCsrf() {
        if (typeof jQuery !== 'undefined') {
            var m = jQuery('meta[name="csrf-token"]').attr('content');
            if (m) return m;
        }
        return csrfFallback;
    }

    function openClientContactModal(ticketId, currentContact, currentNote) {
        var modalEl = document.getElementById('ticket_client_contact_modal');
        if (!modalEl || typeof bootstrap === 'undefined') return;
        document.getElementById('ticket_client_contact_ticket_id').value = ticketId || '';
        var noteEl = document.getElementById('ticket_client_contact_note_modal');
        if (noteEl) noteEl.value = currentNote || '';
        var notYet = document.getElementById('ticket_client_contact_not_yet');
        var call = document.getElementById('ticket_client_contact_call');
        var msg = document.getElementById('ticket_client_contact_message');
        if (notYet) notYet.checked = (currentContact === 'Not Yet' || !currentContact);
        if (call) call.checked = (currentContact === 'Call');
        if (msg) msg.checked = (currentContact === 'Message');
        if (notYet && !notYet.checked && !call.checked && !msg.checked) {
            notYet.checked = true;
        }
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    function bindOpens() {
        if (typeof jQuery === 'undefined') return;
        jQuery(document).off('click.ticketClientContact', '.btn-client-contact-popup').on('click.ticketClientContact', '.btn-client-contact-popup', function(e) {
            e.preventDefault();
            var $btn = jQuery(this);
            openClientContactModal(
                $btn.data('ticket-id'),
                $btn.attr('data-contacted') || '',
                $btn.attr('data-note') || ''
            );
            window.__ticketClientContactSourceBtn = $btn[0];
        });
    }

    function bindSave() {
        if (typeof jQuery === 'undefined') return;
        jQuery(document).off('click.ticketClientContactSave', '#ticket_client_contact_save_btn').on('click.ticketClientContactSave', '#ticket_client_contact_save_btn', function() {
            var ticketId = jQuery('#ticket_client_contact_ticket_id').val();
            var type = jQuery('input[name="ticket_client_contact_type_modal"]:checked').val();
            var note = jQuery('#ticket_client_contact_note_modal').val();
            if (!ticketId || !type) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', text: {!! json_encode($t['contact_method_required'] ?? 'Please choose a contact status.') !!} });
                }
                return;
            }
            var $btn = jQuery(this);
            $btn.prop('disabled', true);
            jQuery.ajax({
                url: postUrl,
                method: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': getCsrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: {
                    _token: getCsrf(),
                    ticket_id: ticketId,
                    contacted_with_client: type,
                    client_contact_note: note
                }
            }).done(function(res) {
                if (res && res.is_success) {
                    var html = res.summary_html || '';
                    var $src = jQuery(window.__ticketClientContactSourceBtn);
                    if ($src.length) {
                        $src.closest('tr, .kt-flight-list-item').find('.kt-client-contact-summary').first().replaceWith(html);
                        $src.attr('data-contacted', res.contacted_with_client || '');
                        $src.attr('data-note', res.client_contact_note || '');
                    }
                    if (typeof window.ticketClientContactOnSaved === 'function') {
                        window.ticketClientContactOnSaved(res, ticketId);
                    }
                    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable && jQuery.fn.DataTable.isDataTable('#datatable')) {
                        jQuery('#datatable').DataTable().ajax.reload(null, false);
                    }
                    var modalEl = document.getElementById('ticket_client_contact_modal');
                    if (modalEl && typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getInstance(modalEl).hide();
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', text: res.message || 'Saved', timer: 1500, showConfirmButton: false });
                    }
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', text: (res && res.message) ? res.message : 'Error' });
                }
            }).fail(function(xhr) {
                var msg = 'Error';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', text: msg });
            }).always(function() {
                $btn.prop('disabled', false);
            });
        });
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            bindOpens();
            bindSave();
        });
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            bindOpens();
            bindSave();
        });
    }

    window.openTicketClientContactModal = openClientContactModal;
})();
</script>
