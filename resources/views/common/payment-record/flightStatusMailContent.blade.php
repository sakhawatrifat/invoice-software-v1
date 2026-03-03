@php
    $liveSegments = $liveData ?? [];
    $segIdx = 0;
    $globalData = globalData();
@endphp

<p>Dear <b>{passenger_automatic_name_here}</b>,</p>

<p>Greetings from <b>{{ $globalData->company_data->company_name ?? 'nakamura-tour.com' }}</b>!</p>

<p>{{ $getCurrentTranslation['flight_date_updated_message'] ?? 'Your flight date/time has been updated by the airline. Please find your updated travel itinerary below:' }}</p>

<hr>

<h2>🧾 Booking Information</h2>
<p>
<b>Booking IDs:</b><br>
• {{ $payment->payment_invoice_id ?? 'N/A' }}<br>
<b>Booking Status:</b> ⓘ {{ $getCurrentTranslation['flight_status'] ?? 'Flight Status' }} – {{ $getCurrentTranslation['updated'] ?? 'Updated' }}
</p>

<hr>

<h2>✈️ Flight Itinerary – One Way</h2>


@if(!empty($systemSegments))
@php
    $firstSeg = $systemSegments[0];
    $lastSeg  = $systemSegments[count($systemSegments)-1];
    $firstDep = $firstSeg['departure_date_time'] ?? null;
    $lastArr  = $lastSeg['arrival_date_time'] ?? null;
    if (!empty($liveSegments[0]['departure'])) {
        $fd = $liveSegments[0]['departure']['departureDateTime'] ?? $liveSegments[0]['departure']['scheduledTime'] ?? null;
        if ($fd) $firstDep = $fd;
    }
    $lastIdx = count($systemSegments) - 1;
    while ($lastIdx >= 0 && !($liveSegments[$lastIdx]['arrival'] ?? null)) { $lastIdx--; }
    if ($lastIdx >= 0 && !empty($liveSegments[$lastIdx]['arrival'])) {
        $la = $liveSegments[$lastIdx]['arrival']['arrivalDateTime'] ?? $liveSegments[$lastIdx]['arrival']['scheduledTime'] ?? null;
        if ($la) $lastArr = $la;
    }
@endphp
<p>
<b>📅 Date:</b>
{{ $firstDep ? \Carbon\Carbon::parse($firstDep)->format('d F Y') : 'N/A' }}
@if($lastArr && $lastArr !== $firstDep)
 – {{ \Carbon\Carbon::parse($lastArr)->format('d F Y') }}
@endif
</p>

@foreach($systemSegments as $idx => $seg)
@php
    $liveDep = $liveSegments[$segIdx]['departure'] ?? null;
    $liveArr = $liveSegments[$segIdx]['arrival'] ?? null;
    $depTimeRaw = $seg['departure_date_time'] ?? null;
    $arrTimeRaw = $seg['arrival_date_time'] ?? null;
    $depTime = $depTimeRaw;
    $arrTime = $arrTimeRaw;
    if ($liveDep) {
        $liveDepStr = $liveDep['departureDateTime'] ?? $liveDep['scheduledTime'] ?? null;
        if ($liveDepStr) $depTime = $liveDepStr;
    }
    if ($liveArr) {
        $liveArrStr = $liveArr['arrivalDateTime'] ?? $liveArr['scheduledTime'] ?? null;
        if ($liveArrStr) $arrTime = $liveArrStr;
    }
    $depStr = $depTime ? \Carbon\Carbon::parse($depTime)->format('H:i, d F Y') : 'N/A';
    $arrStr = $arrTime ? \Carbon\Carbon::parse($arrTime)->format('H:i, d F Y') : 'N/A';
    $fromCity = function_exists('extractPrimaryCity') ? extractPrimaryCity($seg['leaving_from'] ?? '') : ($seg['leaving_from'] ?? 'N/A');
    $toCity   = function_exists('extractPrimaryCity') ? extractPrimaryCity($seg['going_to'] ?? '') : ($seg['going_to'] ?? 'N/A');
    $duration = 'N/A';
    if ($depTime && $arrTime) {
        try {
            $mins = \Carbon\Carbon::parse($depTime)->diffInMinutes(\Carbon\Carbon::parse($arrTime));
            $duration = floor($mins / 60) . 'h ' . ($mins % 60) . 'm';
        } catch (\Exception $e) {}
    }
    $nextSeg = $systemSegments[$idx + 1] ?? null;
    $nextDepTime = $nextSeg['departure_date_time'] ?? null;
    if ($nextSeg && isset($liveSegments[$segIdx + 1]['departure'])) {
        $nd = $liveSegments[$segIdx + 1]['departure'];
        $nextDepTime = $nd['departureDateTime'] ?? $nd['scheduledTime'] ?? $nextDepTime;
    }
    $transitTime = '';
    if ($nextSeg && $arrTime && $nextDepTime) {
        try {
            $tMins = \Carbon\Carbon::parse($arrTime)->diffInMinutes(\Carbon\Carbon::parse($nextDepTime));
            $transitTime = floor($tMins / 60) . 'h ' . ($tMins % 60) . 'm';
        } catch (\Exception $e) {}
    }
@endphp
<p><b>{{ $idx + 1 }}️⃣ {{ $fromCity }} ➝ {{ $toCity }}
</b><br>
• <b>Flight:</b> {{ $seg['airline'] ?? 'N/A' }} • {{ $seg['flight_number'] ?? 'N/A' }}<br>
• <b>Departure:</b> 
{{ $depStr }} 
({{ $seg['leaving_from'] ?? 'N/A' }})<br>
• <b>Arrival:</b> 
{{ $arrStr }} 
({{ $seg['going_to'] ?? 'N/A' }})<br>
• <b>Duration:</b> {{ $duration }}
@if($transitTime)
<br>🕓 <b>Transit in {{ $toCity }}:</b> 
{{ $transitTime }}
@endif
</p>

@if($liveDep || $liveArr)
    @php $segIdx++; @endphp
@endif
@endforeach
@else
<p>
<b>📅 Date:</b> N/A
</p>
<p>{{ $getCurrentTranslation['no_flight_data'] ?? 'No flight data in system.' }}</p>
@endif



<hr>

<h2>👤 Passenger Details</h2>
{passenger_automatic_data_here}
<hr>

<p><font style="color:#ff9900"> 
Please check if your flight is cancelled or the date is correct before travel, or confirm with us — we won't be responsible otherwise.</font> Arrive at the airport at least 3 hours early. Bring your passport, ticket, and all travel papers.<br>
If you have questions, feel free to contact us. Have a safe and pleasant trip!
</p>

<p>
Best regards,<br>
{{ Auth::user()->name ?? 'Admin' }} <br>
{{ Auth::user()->designation?->name ?? 'N/A' }} <br>
<b>{{ $globalData->company_data->company_name ?? 'nakamura-tour.com' }}</b><br>
@if(!empty($globalData->company_data->email_1))
📧 <b>Email:</b> {{ $globalData->company_data->email_1 }}<br>
@endif
@if(!empty($globalData->company_data->website_url))
🌐 <b>Website:</b> {{ $globalData->company_data->website_url }}
@endif
</p>
