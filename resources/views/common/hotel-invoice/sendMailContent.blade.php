@if(count($guests))

<p>Dear <b>{guest_automatic_name_here}</b>,</p>

<p>Greetings from <b>{{ $globalData->company_data->company_name ?? 'Your Company Name' }}</b>!</p>

<p>Your booking has been successfully confirmed. Please find your invoice and hotel information below:</p>

<br>
<h3>🧾 Booking Information</h3>
<p>
<b>Booking ID:</b> {{ $mailData->booking_number }}<br>
<b>Booking Status:</b> {{ $mailData->invoice_status == 'Final' ? '✅ Confirmed' : 'ⓘ '.$mailData->invoice_status }}<br>
<b>Payment Status:</b> {{ $mailData->payment_status ?? 'Unpaid' }}<br>
<b>Total Price:</b> {{ $globalData->company->currency->short_name ?? 'N/A' }} {{ $mailData->total_price }}<br>
</p>

<br>
<h3>🏨 Hotel Information</h3>
<p>
<b>Hotel Name:</b> {{ $mailData->hotel_name ?? '' }}<br>
<b>Address:</b> {!! nl2br($mailData->hotel_address) !!}
</p>

<br>
<h3>📅 Stay Details</h3>
<p>
<b>Check-in Date:</b> {{ date('D, M d, Y', strtotime($mailData->check_in_date)) }} After {{ date('H:i', strtotime($mailData->check_in_time)) }}<br>
<b>Check-out Date:</b> {{ date('D, M d, Y', strtotime($mailData->check_in_date)) }} Before {{ date('H:i', strtotime($mailData->check_in_time)) }}<br>
<b>Total Nights:</b> {{ $mailData->total_night }}<br>
<b>Total Rooms:</b> {{ $mailData->total_room }}<br>
<b>Room Type:</b> {{ $mailData->room_type }}<br>
@if(!empty($mailData->room_info))
<b>Room Info:</b> {!! nl2br($mailData->room_info) !!}<br>
@endif
@if(!empty($mailData->occupancy_info))
<b>Occupancy Info:</b> {!! nl2br($mailData->occupancy_info) !!}<br>
@endif
</p>

<br>
<h3>👤 Guest Details</h3>
@if(isset($guests) && isset($attachGuest) && $attachGuest == 1)
@foreach($guests as $key => $guest)
<p>
<b>{{ $loop->iteration }}️⃣ {{ $guest['name'] ?? '' }}</b><br>
@if(isset($guest['passport_number']) && !empty($guest['passport_number']))
<b>Passport Number:</b> {{ $guest['passport_number'] }}<br>
@endif
@if(isset($guest['email']) && !empty($guest['email']))
<b>Email:</b> {{ $guest['email'] }}<br>
@endif
@if(isset($guest['phone']) && !empty($guest['phone']))
<b>Phone:</b> {{ $guest['phone'] }}<br>
@endif
</p>
@endforeach
@else
<p>{guest_automatic_data_here}</p>
@endif

<br>
<h3>🍽️ Meal & Amenities</h3>
<p>
<b>Meal Info:</b> {{ $mailData->meal_info ?? '' }}<br>
<b>Room Amenities:</b> {{ $mailData->room_amenities ?? '' }}
</p>

<br>
<h3>📌 Cancellation Policy</h3>
@if(isset($mailData) && !empty($mailData->cancellationPolicy) && is_array($mailData->cancellationPolicy))
<table style="width: 100%; margin-bottom: 5px; border-collapse: collapse;">
<tr>
<th style="text-align: center; border: 1px solid #dddddd; padding: 5px; box-sizing: border-box;">
Hotel's Local Time
</th>
<th style="text-align: center; border: 1px solid #dddddd; padding: 5px; box-sizing: border-box;">
Fee
</th>
</tr>
@foreach($mailData->cancellationPolicy as $item)
<tr>
<td style="text-align: center; border: 1px solid #dddddd; padding: 5px; border-spacing: 0px">
{{ $item['date_time']  ?? ''}}
</td>
<td style="text-align: center; border: 1px solid #dddddd; padding: 5px; border-spacing: 0px">
{{ $item['fee']  ?? ''}}
</td>
</tr>
@endforeach
</table>
@endif
@if(!empty($mailData->policy_note))
Note: {!! nl2br($mailData->policy_note) !!}
@endif

<br>
<br>
<p style="color:#ff9900"> 
Please check if your booking is cancelled or the date is correct before visit, or confirm with us — we won’t be responsible otherwise. Bring all your travel papers.<br>
If you have questions, feel free to contact us. Have a safe and pleasant trip!
</p>

<br>
<p>
Best regards,<br>
{{ Auth::user()->name }} <br>
{{ Auth::user()->designation?->name ?? 'N/A' }} <br>
@if(!empty($globalData->company_data->company_name))
<b>{{ $globalData->company_data->company_name }}</b><br>
@endif
@if(!empty($globalData->company_data->email_1))
📧 <b>Email:</b> {{ $globalData->company_data->email_1 }}<br>
@endif
@if(!empty($globalData->company_data->website_url))
🌐 <b>Website:</b> {{ $globalData->company_data->website_url }}
@endif
</p>

@endif
