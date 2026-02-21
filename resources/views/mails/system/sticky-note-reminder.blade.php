@component('mail::message')
# {{ __('Sticky Note Reminder') }}

@if($recipientName)
{{ __('Hello') }}, {{ $recipientName }}
@endif

{{ __('A sticky note requires your action.') }}

**{{ __('Note') }}:** {{ $note->note_title }}

@if($note->note_description)
{{ Str::limit($note->note_description, 200) }}
@endif

@if($note->reminder_datetime)
**{{ __('Reminder time') }}:** {{ $note->reminder_datetime->format('Y-m-d H:i') }}
@endif

@if($note->deadline)
**{{ __('Deadline') }}:** {{ $note->deadline->format('Y-m-d H:i') }}
@endif

@component('mail::button', ['url' => $noteUrl])
{{ __('View note') }}
@endcomponent

@endcomponent
