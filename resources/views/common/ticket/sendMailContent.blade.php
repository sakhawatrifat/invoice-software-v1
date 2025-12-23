@if(count($passengers))

<p>Dear <b>{passenger_automatic_name_here}</b>,</p>

<p>Greetings from <b>{{ $globalData->company_data->company_name ?? 'Your Company Name' }}</b>!</p>

<p>Your booking has been successfully confirmed. Please find your e-ticket and complete travel itinerary below:</p>

<hr>

<h2>🧾 Booking Information</h2>
<p>
<b>Booking IDs:</b><br>
• {{ $mailData->reservation_number }}<br>
<b>Booking Status:</b> {{ $mailData->booking_status == 'Confirmed' ? '✅ Confirmed' : 'ⓘ '.$mailData->booking_status }}
</p>

<hr>
{{-- One Way Trip Start --}}
@if ($mailData->trip_type == 'One Way')
@foreach ($mailData->flights as $flightIndex => $flight)
<h2>✈️ Flight Itinerary – One Way</h2>

@php
$nextTransit = null;

if ($flight && $flight->transits->isNotEmpty()) {
$nextTransit = $flight->transits->first();
} elseif (isset($mailData->flights[$flightIndex + 1])) {
$nextTransit = $mailData->flights[$flightIndex + 1];
}
@endphp

<p>
<b>📅 Date:</b>
{{ $flight && !empty($flight->departure_date_time) 
? date('d F Y', strtotime($flight->departure_date_time)) 
: 'N/A' }}
{{ $nextTransit && !empty($nextTransit->departure_date_time) 
? ' – ' . date('d F Y', strtotime($nextTransit->departure_date_time)) 
: '' }}
</p>

<p><b>1️⃣ {{ $flight ? extractPrimaryCity($flight->leaving_from) : 'N/A' }} ➝ {{ extractPrimaryCity($flight->going_to) }}
</b><br>
• <b>Flight:</b> {{ $flight->airline->name ?? 'N/A' }} • {{ $flight->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> 
{{ $flight && !empty($flight->departure_date_time) 
? date('H:i, d F Y', strtotime($flight->departure_date_time)) 
: 'N/A' }} 
({{ $flight->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> 
{{ $flight && !empty($flight->arrival_date_time) 
? date('H:i, d F Y', strtotime($flight->arrival_date_time)) 
: 'N/A' }} 
({{ $flight->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $flight->total_fly_time ?? 'N/A' }}
@if ($nextTransit && !empty($nextTransit->leaving_from) && !empty($nextTransit->total_transit_time))
<br>🕓 <b>Transit in {{ extractPrimaryCity($nextTransit->leaving_from) }}:</b> 
{{ $nextTransit->total_transit_time }}
@endif
</p>

{{-- Transit Flights for this segment --}}
@foreach ($flight->transits as $key => $transit)
@php
$nextTransit = null;

if ($flight && $flight->transits->isNotEmpty()) {
// Reindex collection like array_values()
$transits = $flight->transits->values();

// use $key from foreach, not $transitIndex
if ($transits->has($key + 1)) {
    $nextTransit = $transits[$key + 1];
}
}

// fallback if no "next transit" found
if (!$nextTransit && isset($mailData->flights[$flightIndex + 1])) {
$nextTransit = $mailData->flights[$flightIndex + 1];
}
@endphp

<p><b>{{ $loop->iteration + 1 }}️⃣ 
{{ extractPrimaryCity($transit->leaving_from) }} ➝ {{ extractPrimaryCity($transit->going_to) }}
</b><br>
• <b>Flight:</b> {{ $transit->airline->name ?? 'N/A' }} • {{ $transit->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> 
{{ !empty($transit->departure_date_time) 
? date('H:i, d F Y', strtotime($transit->departure_date_time)) 
: 'N/A' }} 
({{ $transit->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> 
{{ !empty($transit->arrival_date_time) 
? date('H:i, d F Y', strtotime($transit->arrival_date_time)) 
: 'N/A' }} 
({{ $transit->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $transit->total_fly_time ?? 'N/A' }}
@if ($nextTransit && !empty($nextTransit->leaving_from) && !empty($nextTransit->total_transit_time))
<br>🕓 <b>Transit in {{ extractPrimaryCity($nextTransit->leaving_from) }}:</b> 
{{ $nextTransit->total_transit_time }}
@endif
</p>
@endforeach
{{-- Add <hr> only if a real next flight exists --}}
@if (isset($mailData->flights[$flightIndex + 1]) && !empty($mailData->flights[$flightIndex + 1]))
<hr>
@endif
@endforeach
@endif
{{-- One Way Trip End --}}

{{-- Round Trip Start --}}
@if($mailData->trip_type == 'Round Trip')
<h2>✈️ Flight Itinerary – Outbound Journey</h2>
@php
$firstFlight = $mailData->flights[0] ?? null;
// Fix: Handle transits as Collection, not array
$firstTransit = ($firstFlight && $firstFlight->transits && $firstFlight->transits->isNotEmpty()) 
    ? $firstFlight->transits->first() 
    : null;
@endphp

<p>
<b>📅 Date:</b> {{ $firstFlight && !empty($firstFlight->departure_date_time) ? date('d F Y', strtotime($firstFlight->departure_date_time)) : 'N/A' }}{{ $firstTransit && !empty($firstTransit->departure_date_time) ? ' – ' . date('d F Y', strtotime($firstTransit->departure_date_time)) : '' }}
</p>

<p><b>1️⃣ {{ $firstFlight ? extractPrimaryCity($firstFlight->leaving_from) : 'N/A' }} ➝ {{ extractPrimaryCity($firstFlight->going_to ?? 'N/A') }}</b><br>
• <b>Flight:</b> {{ $firstFlight->airline->name ?? 'N/A' }} • {{ $firstFlight->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> {{ $firstFlight && !empty($firstFlight->departure_date_time) ? date('H:i, d F Y', strtotime($firstFlight->departure_date_time)) : 'N/A' }} ({{ $firstFlight->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> {{ $firstFlight && !empty($firstFlight->arrival_date_time) ? date('H:i, d F Y', strtotime($firstFlight->arrival_date_time)) : 'N/A' }} ({{ $firstFlight->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $firstFlight->total_fly_time ?? 'N/A' }}
@if($firstTransit && !empty($firstTransit->total_transit_time))
<br>🕓 <b>Transit Time:</b> {{ $firstTransit->total_transit_time }}
@endif
</p>

{{-- Display all transits under first flight --}}
@if($firstFlight && $firstFlight->transits && $firstFlight->transits->isNotEmpty())
@foreach($firstFlight->transits as $key => $transit)
@php
// Fix: Use Collection methods instead of array access
$nextTransit = $firstFlight->transits->get($key + 1);
@endphp
<p><b>{{ $loop->iteration + 1 }}️⃣ {{ extractPrimaryCity($transit->leaving_from) }} ➝ {{ extractPrimaryCity($transit->going_to) }}</b><br>
• <b>Flight:</b> {{ $transit->airline->name ?? 'N/A' }} • {{ $transit->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> {{ !empty($transit->departure_date_time) ? date('H:i, d F Y', strtotime($transit->departure_date_time)) : 'N/A' }} ({{ $transit->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> {{ !empty($transit->arrival_date_time) ? date('H:i, d F Y', strtotime($transit->arrival_date_time)) : 'N/A' }} ({{ $transit->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $transit->total_fly_time ?? 'N/A' }}
@if($nextTransit && !empty($nextTransit->total_transit_time))
<br>🕓 <b>Transit Time:</b> {{ $nextTransit->total_transit_time }}
@endif
</p>
@endforeach
@endif

{{-- ================= RETURN JOURNEY ================= --}}
@if(isset($mailData->flights[1]) && !empty($mailData->flights[1]))
@php
$returnFlight = $mailData->flights[1];
// Fix: Check if transits exists and is not empty before accessing
$returnFirstTransit = ($returnFlight->transits && $returnFlight->transits->isNotEmpty()) 
    ? $returnFlight->transits->first() 
    : null;
@endphp

@if(
    $returnFirstTransit || 
    (!empty($returnFlight->departure_date_time) && !empty($mailData->flights[0]->arrival_date_time))
)
<hr>
<h2>✈️ Flight Itinerary – Return Journey</h2>

<p>
<b>📅 Date:</b>
{{ !empty($returnFlight->departure_date_time) ? date('d F Y', strtotime($returnFlight->departure_date_time)) : 'N/A' }}
{{ $returnFirstTransit && !empty($returnFirstTransit->departure_date_time) ? ' – ' . date('d F Y', strtotime($returnFirstTransit->departure_date_time)) : '' }}
</p>

<p><b>1️⃣ {{ extractPrimaryCity($returnFlight->leaving_from ?? 'N/A') }} ➝ {{ extractPrimaryCity($returnFlight->going_to ?? 'N/A') }}</b><br>
• <b>Flight:</b> {{ $returnFlight->airline->name ?? 'N/A' }} • {{ $returnFlight->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> {{ !empty($returnFlight->departure_date_time) ? date('H:i, d F Y', strtotime($returnFlight->departure_date_time)) : 'N/A' }} ({{ $returnFlight->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> {{ !empty($returnFlight->arrival_date_time) ? date('H:i, d F Y', strtotime($returnFlight->arrival_date_time)) : 'N/A' }} ({{ $returnFlight->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $returnFlight->total_fly_time ?? 'N/A' }}
@if($returnFirstTransit && !empty($returnFirstTransit->total_transit_time))
<br>🕓 <b>Transit Time:</b> {{ $returnFirstTransit->total_transit_time }}
@endif
</p>

{{-- Display all transits under return flight --}}
@if($returnFlight->transits && $returnFlight->transits->isNotEmpty())
@foreach($returnFlight->transits as $key => $transit)
@php
// Fix: Use Collection get() method
$nextTransit = $returnFlight->transits->get($key + 1);
@endphp

<p><b>{{ $loop->iteration + 1 }}️⃣ {{ extractPrimaryCity($transit->leaving_from) }} ➝ {{ extractPrimaryCity($transit->going_to) }}</b><br>
• <b>Flight:</b> {{ $transit->airline->name ?? 'N/A' }} • {{ $transit->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> {{ !empty($transit->departure_date_time) ? date('H:i, d F Y', strtotime($transit->departure_date_time)) : 'N/A' }} ({{ $transit->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> {{ !empty($transit->arrival_date_time) ? date('H:i, d F Y', strtotime($transit->arrival_date_time)) : 'N/A' }} ({{ $transit->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $transit->total_fly_time ?? 'N/A' }}
@if($nextTransit && !empty($nextTransit->total_transit_time))
<br>🕓 <b>Transit Time:</b> {{ $nextTransit->total_transit_time }}
@endif
</p>
@endforeach
@endif
@endif
@endif

@endif
{{-- Round Trip End --}}

{{-- Multi City Start --}}
@if ($mailData->trip_type == 'Multi City')
@foreach ($mailData->flights as $flightIndex => $flight)
<h2>✈️ Flight Itinerary – Multi City {{ $loop->iteration }}️⃣</h2>

@php
$nextTransit = null;

if ($flight && $flight->transits && $flight->transits->isNotEmpty()) {
    $nextTransit = $flight->transits->last();
} elseif (isset($mailData->flights[$flightIndex + 1])) {
    $nextTransit = $mailData->flights[$flightIndex + 1];
}
@endphp

<p>
<b>📅 Date:</b>
{{ $flight && !empty($flight->departure_date_time) 
? date('d F Y', strtotime($flight->departure_date_time)) 
: 'N/A' }}
{{ $nextTransit && !empty($nextTransit->departure_date_time) 
? ' – ' . date('d F Y', strtotime($nextTransit->departure_date_time)) 
: '' }}
</p>

<p><b>1️⃣ {{ $flight ? extractPrimaryCity($flight->leaving_from) : 'N/A' }} ➝ {{ extractPrimaryCity($flight->going_to ?? 'N/A') }}
</b><br>
• <b>Flight:</b> {{ $flight->airline->name ?? 'N/A' }} • {{ $flight->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> 
{{ $flight && !empty($flight->departure_date_time) 
? date('H:i, d F Y', strtotime($flight->departure_date_time)) 
: 'N/A' }} 
({{ $flight->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> 
{{ $flight && !empty($flight->arrival_date_time) 
? date('H:i, d F Y', strtotime($flight->arrival_date_time)) 
: 'N/A' }} 
({{ $flight->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $flight->total_fly_time ?? 'N/A' }}
@if ($nextTransit && !empty($nextTransit->leaving_from) && !empty($nextTransit->total_transit_time))
<br>🕓 <b>Transit in {{ extractPrimaryCity($nextTransit->leaving_from) }}:</b> 
{{ $nextTransit->total_transit_time }}
@endif
</p>

{{-- Transit Flights for this segment --}}
@if($flight->transits && $flight->transits->isNotEmpty())
@foreach ($flight->transits as $key => $transit)
@php
// Fix: Use Collection get() method
$nextTransit = $flight->transits->get($key + 1);

// fallback if no "next transit" found
if (!$nextTransit && isset($mailData->flights[$flightIndex + 1])) {
    $nextTransit = $mailData->flights[$flightIndex + 1];
}
@endphp

<p><b>{{ $loop->iteration + 1 }}️⃣ {{ extractPrimaryCity($transit->leaving_from) }} ➝ {{ extractPrimaryCity($transit->going_to) }}
</b><br>
• <b>Flight:</b> {{ $transit->airline->name ?? 'N/A' }} • {{ $transit->flight_number ?? 'N/A' }}<br>
• <b>Departure:</b> 
{{ !empty($transit->departure_date_time) 
? date('H:i, d F Y', strtotime($transit->departure_date_time)) 
: 'N/A' }} 
({{ $transit->leaving_from ?? 'N/A' }})<br>
• <b>Arrival:</b> 
{{ !empty($transit->arrival_date_time) 
? date('H:i, d F Y', strtotime($transit->arrival_date_time)) 
: 'N/A' }} 
({{ $transit->going_to ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $transit->total_fly_time ?? 'N/A' }}
@if ($nextTransit && !empty($nextTransit->leaving_from) && !empty($nextTransit->total_transit_time))
<br>🕓 <b>Transit in {{ extractPrimaryCity($nextTransit->leaving_from) }}:</b> 
{{ $nextTransit->total_transit_time }}
@endif
</p>
@endforeach
@endif
{{-- Add <hr> only if a real next flight exists --}}
@if (isset($mailData->flights[$flightIndex + 1]) && !empty($mailData->flights[$flightIndex + 1]))
<hr>
@endif
@endforeach
@endif
{{-- Multi City End --}}
<hr>

<h2>👤 Passenger Details</h2>
@if(isset($attachPassengers) && $attachPassengers == 1)
@foreach($passengers as $passenger)
<p>
<b>{{ $loop->iteration }}️⃣ {{ $passenger->name }} ({{ $passenger->pax_type }})</b><br>
• <b>Airline PNRs:</b> {{ collect($passenger->flights)->pluck('airlines_pnr')->filter()->implode(' / ') }}<br>
• <b>E-Ticket Numbers:</b> {{ collect($passenger->flights)->pluck('ticket_number')->filter()->implode(' / ') }}<br>
• <b>Baggage Allowance:</b> {!! preg_replace('/(\s*<br\s*\/?>\s*|\s*[\r\n]+\s*)+/', '<br>', $passenger->baggage_allowance) !!}
</p>
@endforeach
@else
{passenger_automatic_data_here}
@endif
<hr>

<p><font style="color:#ff9900"> 
Please check if your flight is cancelled or the date is correct before travel, or confirm with us — we won’t be responsible otherwise.</font> Arrive at the airport at least 3 hours early. Bring your passport, ticket, and all travel papers.<br>
If you have questions, feel free to contact us. Have a safe and pleasant trip!
</p>

<p>
Best regards,<br>
{{ Auth::user()->name }} <br>
@if(!empty(Auth::user()->designation))
{{ Auth::user()->designation }} <br>
@endif
@if(!empty($globalData->company_data) && !empty($globalData->company_data->company_name))
<b>{{ $globalData->company_data->company_name }}</b><br>
@endif
@if(!empty($globalData->company_data) && !empty($globalData->company_data->email_1))
📧 <b>Email:</b> {{ $globalData->company_data->email_1 }}<br>
@endif
@if(!empty($globalData->company_data) && !empty($globalData->company_data->website_url))
🌐 <b>Website:</b> {{ $globalData->company_data->website_url }}
@endif
</p>

@endif