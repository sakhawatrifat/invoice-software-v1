@component('mail::message')
<style type="text/css">
    *:not(a) { color: #333333 !important; }
    p { font-size: 14px !important; }
    table { border-collapse: collapse; width: 100%; margin: 1em 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #f5f5f5; }
</style>

# {{ __('flight_schedule_update') ?? 'Flight Schedule Update' }}

{{ __('flight_schedule_updated_body') ?? 'Your flight schedule has been updated. Please find the updated details below.' }}

**{{ __('payment_invoice_id_label') ?? 'Invoice ID' }}:** {{ $payment->payment_invoice_id ?? 'N/A' }}

@if(!empty($livePayload))
## {{ __('updated_flight_times') ?? 'Updated flight times' }}

@foreach($livePayload as $segment)
@if(!empty($segment['departure']))
- **{{ __('departure_label') ?? 'Departure' }}:** {{ $segment['departure']['airport'] ?? '' }} ({{ $segment['departure']['airportCode'] ?? '' }}) – {{ $segment['departure']['departureDateTime'] ?? $segment['departure']['scheduledTime'] ?? 'N/A' }}
@endif
@if(!empty($segment['arrival']))
- **{{ __('arrival_label') ?? 'Arrival' }}:** {{ $segment['arrival']['airport'] ?? '' }} ({{ $segment['arrival']['airportCode'] ?? '' }}) – {{ $segment['arrival']['arrivalDateTime'] ?? $segment['arrival']['scheduledTime'] ?? 'N/A' }}
@endif
@endforeach
@endif

{{ __('contact_us_for_questions') ?? 'If you have any questions, please contact us.' }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
