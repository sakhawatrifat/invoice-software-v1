@php
   $getCurrentTranslation = getCurrentTranslation();
@endphp

@if(!isset($view))
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>{{ $getCurrentTranslation['ticket'] ?? 'ticket' }}</title>
      <link rel="icon" href="{{ $globalData->company_data->dark_icon_url ?? '' }}" />
   </head>
   <body style="color: #5d5e63; font-size: 13px; margin: 0; padding: 0; background: #fff;">
@endif

<div class="inv-content-wrapper" style="color: #32323b; background: #fff;">
   <style>
      .inv-content-wrapper *{
         margin: 0;
         padding: 0;
      }
      .inv-footer-description,
      .inv-footer-description *{
         margin: 0!important;
         padding: 0!important;
      }
      /* Zebra striping for table rows */
      .inv-section tr:nth-child(even) th, 
      .inv-section tr:nth-child(even) td {
          background-color: #f9f9f9;
      }
      /* Table header styles */
      .inv-section thead th {
          background-color: #f4f4f4;
          font-weight: bold;
          text-align: left;
      }
   </style>
   <div class="inv-header" style="width: 100%; margin-bottom: 30px; ">
      <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
         <tr>
            <!-- Left Side: Logo beside Company Info -->
            <td style="width: 78%; vertical-align: top; color: #32323b; padding-right: 0%;">
               <table style="width: 100%; table-layout: fixed;">
                  <tr>
                     <!-- Logo -->
                     <td style="width: 230px; vertical-align: top;">
                        @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                           <img src="{{ $editData->user->company->dark_logo_url ?? '' }}" alt="{{ $editData->user->company->company_name ?? 'N/A' }} Logo" style="max-width: 150px; height: auto;">
                        @else
                           <h4 style="text-align: center; margin: 0;">Company Logo Here</h4>
                        @endif

                        <strong style="font-family: {{ language_font(strip_tags($editData->user->company->company_name ?? '')) }}; font-size: 16px; color: #32323b">
                           <br>
                           {{ $editData->user->company->company_name ?? 'N/A' }}
                        </strong><br>
                        @if($editData->user->company && $editData->user->company->tagline)
                           <span style="font-family: {{ language_font(strip_tags($editData->user->company->tagline ?? '')) }};">{{ $editData->user->company->tagline ?? '' }}</span>
                        @endif
                     </td>

                     <!-- Company Info -->
                     {{-- <td style="vertical-align: top; font-size: 11px; padding-left: 10px; color: #3c3c3c;">
                        <div>
                           <div style="font-family: {{ language_font(strip_tags($editData->user->company->address ?? '')) }};">
                              {!! $editData->user->company->address ?? '' !!}
                           </div>
                           
                           @php
                              $email = $editData->user->company->email_1 ?? '';
                              $phone = $editData->user->company->phone_1 ?? '';
                           @endphp

                           @if($email || $phone)
                              @if($email) <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email_label'] ?? 'email_label')) }};">{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</span> {{ $email }} @endif
                              @if($email && $phone) <br> @endif
                              @if($phone) <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone_label'] ?? 'phone_label')) }};">{{ $getCurrentTranslation['phone_label'] ?? 'phone_label' }}:</span> {{ $phone }} @endif
                           @endif
                        </div>
                     </td> --}}
                  </tr>
               </table>
            </td>


            <!-- Right Side: e-Ticket Info -->
            <td style="width: 22%; vertical-align: top; text-align: left; color: #32323b;">
               @php
                  $bookingType = $editData->booking_type;
                  if($editData->booking_type == 'e-Ticket'){
                     $bookingType = $getCurrentTranslation['e_ticket'] ?? 'e_ticket';
                  }
                  if($editData->booking_type == 'e-Booking'){
                     $bookingType = $getCurrentTranslation['e_booking'] ?? 'e_booking';
                  }
               @endphp
               <div style="font-family: {{ language_font(strip_tags($bookingType)) }}; font-size: 22px; font-weight: 500; margin-bottom: 4px;">{{ $bookingType ?? 'N/A' }}</div>
               <div style="font-size: 12px; line-height: 1.5;">
                  <strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['booking_id'] ?? 'booking_id')) }}; color: #6c6c6c; font-weight: normal;">{{ $getCurrentTranslation['booking_id'] ?? 'booking_id' }}:</strong><br>
                  <span style="font-family: {{ language_font(strip_tags($editData->reservation_number)) }}; ">{{ $editData->reservation_number ?? 'N/A' }}</span><br>
                  {{-- <strong style="color: #6c6c6c; font-weight: normal;">{{ $getCurrentTranslation['issue_date_label'] ?? 'issue_date_label' }}:</strong> {{ date('d M Y', strtotime($editData->invoice_date)) }} --}}
               </div>
            </td>
         </tr>
      </table>
   </div>

   
   <div class="inv-section" style="margin-top: 20px;">
      <table style="width: 100%; border-collapse: collapse; vertical-align: middle; background-color: #f4f4f4">
         <tr>
            <td style="font-family: {{ language_font(strip_tags($getCurrentTranslation['booking_status'] ?? 'booking_status')) }}; font-size: 15px; color: #32323b; font-weight: bold;padding: 5px 10px;">
               {{ $getCurrentTranslation['booking_status'] ?? 'booking_status' }}:
            </td>
            <td style="text-align: right;padding: 5px 10px;">
               @php
                  $status = $editData->booking_status ?? '';
                  switch ($status) {
                  case 'On Hold':
                     $statusText  = $getCurrentTranslation['on_hold'] ?? 'on_hold';
                     $color = '#FFFFFF';
                     $bg    = '#FF8C00';
                     break;

                  case 'Processing':
                     $statusText  = $getCurrentTranslation['processing'] ?? 'processing';
                     $color = '#FFFFFF';
                     $bg    = '#0056b3';
                     break;

                  case 'Confirmed':
                     $statusText  = $getCurrentTranslation['confirmed'] ?? 'confirmed';
                     $color = '#FFFFFF';
                     $bg    = '#218838';
                     break;

                  case 'Cancelled':
                     $statusText  = $getCurrentTranslation['cancelled'] ?? 'cancelled';
                     $color = '#FFFFFF';
                     $bg    = '#C82333';
                     break;

                  default:
                     $statusText  = $getCurrentTranslation['default'] ?? 'default';
                     $color = '#FFFFFF';
                     $bg    = '#6C757D';
                     break;
               }

               @endphp

               <table style="border-collapse: collapse; display: inline-block; vertical-align: middle;">
                  <tr>
                     <td style="font-family: {{ language_font(strip_tags($statusText)) }}; background-color: {{ $bg }}; color: {{ $color }}; border-radius: 5px!important; font-weight: 600; font-size: 13px; padding: 5px 12px;">
                        {{ $statusText }}
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
      </table>
   </div>


   <div class="inv-section" style="margin-top: 20px;">
      {{-- <h3 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['flight_itineraries'] ?? 'flight_itineraries')) }}; background-color: #e7e7e7; padding: 10px 10px; margin: 0; font-size: 15px; color: #32323b;">
         {{ $getCurrentTranslation['flight_itineraries'] ?? 'flight_itineraries' }}
      </h3> --}}
      <table style="width: 100%; border-collapse: collapse;">
         <thead>
            <tr>
               <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['flight_itineraries'] ?? 'flight_itineraries')) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $getCurrentTranslation['flight_itineraries'] ?? 'flight_itineraries' }}</th>
            </tr>
         </thead>
      </table>
      @foreach($editData->flights as $flight)
         <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
             <table style="width:100%; border:1px solid #e5e5e5; font-size:12px; padding:20px; border-collapse:collapse; margin-bottom:{{ count($flight->transits) ? '0px': '0px'}};">
                 <!-- Airline and Flight Info -->
                 <tr>
                     <td style="padding:20px; {{ isset($view) && $view == 1 ? '' : 'padding-top: 0px!important;' }}">
                         <table>
                             {{-- <tr>
                                 <td colspan="3" style="text-align: center; padding-bottom: 15px;">
                                    <div style="text-align: center;">
                                          @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                             <img src="{{ $flight->airline->logo_url ?? '' }}" alt="{{ $flight->airline->name ?? '' }}" style="width: auto; max-width: 200px; vertical-align:middle; margin-right:10px;">
                                          @else
                                             <h4 style="text-align: center; margin: 0; color: #32323b;">Airlines Logo Here</h4>
                                          @endif
                                     </div>
                                 </td>
                              </tr> --}}
                              <tr>
                                 <!-- Departure -->
                                 <td style="text-align:center; width:150px; background: transparent; color: #32323b;">
                                     <div style="font-family: {{ language_font(strip_tags($flight->leaving_from)) }}; font-size:20px; font-weight:bold;">{{ extractPrimaryCity($flight->leaving_from)  }}</div>
                                     <div style="font-size:16px;">{{ \Carbon\Carbon::parse($flight->departure_date_time)->format('H:i') }}</div>
                                     <div style="font-size:15px; color:#555;">{{ \Carbon\Carbon::parse($flight->departure_date_time)->format('d M, Y') }}</div>
                                 </td>

                                 <td style="text-align:center;width:100px;background: transparent;color: #32323b;position: relative;">
                                    @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                       <img src="{{ asset('assets/images/flight-transit-img.png') }}" style="width: 130px;">
                                    @else
                                       <h4 style="text-align: center; margin: 0; color: #32323b;">Flight image here</h4>
                                    @endif
                                     <div style="font-family: {{ language_font(strip_tags($flight->total_fly_time)) }}; font-size:13px;position: absolute;left: 50%;transform: translateX(-50%);bottom: 10px;">{{ $flight->total_fly_time }}
                                     </div>
                                 </td>

                                 <!-- Arrival -->
                                 <td style="text-align:center; width:150px; background: transparent;">
                                    <div style="font-family: {{ language_font(strip_tags($flight->going_to)) }}; font-size:20px; font-weight:bold;">{{ extractPrimaryCity($flight->going_to) }}</div>
                                    <div style="font-size:16px;">{{ \Carbon\Carbon::parse($flight->arrival_date_time)->format('H:i') }}</div>
                                    <div style="font-size:15px; color:#555;">{{ \Carbon\Carbon::parse($flight->arrival_date_time)->format('d M, Y') }}</div>
                                 </td>
                             </tr>
                         </table>
                     </td>
                     <td style="background: transparent; color: #32323b; width: 55%; vertical-align: bottom; padding: 20px;">
                        <center>
                           <div style="font-size:13px; line-height:1.6;display: inline-block; text-align: left;">
                              <div style="text-align: left;">
                                 @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                    <img src="{{ $flight->airline->logo_url ?? '' }}" alt="{{ $flight->airline->name ?? '' }}" style="width: auto; max-width: 200px; vertical-align:middle; margin-right:10px;">
                                 @else
                                    <h4 style="text-align: center; margin: 0; color: #32323b;">Airlines Logo Here</h4>
                                 @endif
                              </div>
                              <div><strong style="font-family: {{ language_font(strip_tags($flight->flight_number)) }}; font-size:13px;">{{ $flight->airline->name ?? 'N/A' }} • {{ $flight->flight_number }}</strong></div>
                             <div><span style="color:#585858;"></div>
                             <div><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['departs'] ?? 'departs')) }}; ">{{ $getCurrentTranslation['departs'] ?? 'departs' }}</strong>: <span style="font-family: {{ language_font(strip_tags($flight->leaving_from)) }};">{{ $flight->leaving_from }}</span></div>
                             <div><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['lands_in'] ?? 'lands_in')) }}; ">{{ $getCurrentTranslation['lands_in'] ?? 'lands_in' }}</strong>: <span style="font-family: {{ language_font(strip_tags($flight->going_to)) }};">{{ $flight->going_to }}</span></div>
                             {{-- <div><strong style="font-family: {{ language_font(strip_tags('Eng')) }}; ">A-PNR</strong>: {{ $editData->airlines_pnr }}</div> --}}
                             {{-- <div><strong style="font-family: {{ language_font(strip_tags('Eng')) }}; ">{{ $getCurrentTranslation['ticket_type_label'] ?? 'ticket_type_label' }}</strong>: {{ $editData->ticket_type }}</div> --}}
                             {{-- <div style="color: #008000; text-align: center">{{ $editData->ticket_type }}</div> --}}
                           </div>
                        </center>
                     </td>
                 </tr>
             </table>

            @foreach($flight->transits as $transit)
               <div style="text-align: center; padding: 5px; font-size: 13px; font-weight: 600; width: 300px; margin: 0 auto;">
                   <hr style="margin: 0">
                   <div style="font-family: {{ language_font(strip_tags($getCurrentTranslation['transit_time'] ?? 'transit_time')) }}; ">{{ $getCurrentTranslation['transit_time'] ?? 'transit_time' }} <span style="font-family: {{ language_font(strip_tags($transit->total_transit_time)) }};">{{ $transit->total_transit_time }}</span></div>
                   <hr style="margin: 0">
               </div>

               <table style="width:100%; border:1px solid #e5e5e5; font-size:12px; padding:20px; border-collapse:collapse; margin-bottom: 0px;">
                 <!-- Airline and Transit Info -->
                 <tr>
                     <td style="padding:20px; {{ isset($view) && $view == 1 ? '' : 'padding-top: 0px!important;' }}">
                         <table>
                             {{-- <tr>
                                 <td colspan="3" style="text-align: center; padding-bottom: 15px;">
                                    <div style="text-align: center;">
                                          @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                             <img src="{{ $transit->airline->logo_url ?? '' }}" alt="{{ $transit->airline->name ?? '' }}" style="width: auto; max-width: 200px; vertical-align:middle; margin-right:10px;">
                                          @else
                                             <h4 style="text-align: center; margin: 0; color: #32323b;">Airlines Logo Here</h4>
                                          @endif
                                     </div>
                                 </td>
                              </tr> --}}
                              <tr>
                                 <!-- Departure -->
                                 <td style="text-align:center; width:150px; background: transparent; color: #32323b;">
                                     <div style="font-family: {{ language_font(strip_tags($transit->leaving_from)) }}; font-size:20px; font-weight:bold;">{{ extractPrimaryCity($transit->leaving_from)  }}</div>
                                     <div style="font-size:16px;">{{ \Carbon\Carbon::parse($transit->departure_date_time)->format('H:i') }}</div>
                                     <div style="font-size:15px; color:#555;">{{ \Carbon\Carbon::parse($transit->departure_date_time)->format('d M, Y') }}</div>
                                 </td>

                                 <td style="text-align:center;width:100px;background: transparent;color: #32323b;position: relative;">
                                       @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                          <img src="{{ asset('assets/images/flight-transit-img.png') }}" style="width: 130px;">
                                       @else
                                          <h4 style="text-align: center; margin: 0; color: #32323b;">Flight image here</h4>
                                       @endif
                                     <div style="font-family: {{ language_font(strip_tags($transit->total_fly_time)) }}; font-size:13px;position: absolute;left: 50%;transform: translateX(-50%);bottom: 10px;">{{ $transit->total_fly_time }}
                                     </div>
                                 </td>

                                 <!-- Arrival -->
                                 <td style="text-align:center; width:150px; background: transparent;">
                                     <div style="font-family: {{ language_font(strip_tags($transit->going_to)) }}; font-size:20px; font-weight:bold;">{{ extractPrimaryCity($transit->going_to)  }}</div>
                                     <div style="font-size:16px;">{{ \Carbon\Carbon::parse($transit->arrival_date_time)->format('H:i') }}</div>
                                     <div style="font-size:15px; color:#555;">{{ \Carbon\Carbon::parse($transit->arrival_date_time)->format('d M, Y') }}</div>
                                 </td>
                             </tr>
                         </table>
                     </td>
                     <td style="background: transparent; color: #32323b; width: 55%; vertical-align: bottom; padding: 20px;">
                        <center>
                           <div style="font-size:13px; line-height:1.6;display: inline-block; text-align: left;">
                              <div style="text-align: left;">
                                 @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                    <img src="{{ $transit->airline->logo_url ?? '' }}" alt="{{ $transit->airline->name ?? '' }}" style="width: auto; max-width: 200px; vertical-align:middle; margin-right:10px;">
                                 @else
                                    <h4 style="text-align: center; margin: 0; color: #32323b;">Airlines Logo Here</h4>
                                 @endif
                              </div>
                              <div><strong style="font-family: {{ language_font(strip_tags($transit->airline->name ?? 'N/A')) }}; font-size:13px;">{{ $transit->airline->name ?? 'N/A' }} • {{ $transit->flight_number }}</strong></div>
                             <div><span style="color:#585858;"></div>
                             <div><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['departs'] ?? 'departs')) }}; ">{{ $getCurrentTranslation['departs'] ?? 'departs' }}</strong>: <span style="font-family: {{ language_font(strip_tags($transit->leaving_from)) }};">{{ $transit->leaving_from }}</span></div>
                             <div><strong style="font-family: {{ language_font(strip_tags($getCurrentTranslation['lands_in'] ?? 'lands_in')) }}; ">{{ $getCurrentTranslation['lands_in'] ?? 'lands_in' }}</strong>: <span style="font-family: {{ language_font(strip_tags($transit->going_to)) }};">{{ $transit->going_to }}</span></div>
                             {{-- <div><strong style="font-family: {{ language_font(strip_tags('Eng')) }}; ">A-PNR</strong>: {{ $editData->airlines_pnr }}</div> --}}
                           </div>
                        </center>
                     </td>
                 </tr>
             </table>
            @endforeach
         </div>
      @endforeach
   </div>

   @if(isset($passenger) && !empty($passenger))
      @php
         $editData->passengers = collect([$passenger]);
      @endphp
   @endif

   @if(isset($ticket_passengers) && !empty($ticket_passengers))
      @php
         $editData->passengers = $ticket_passengers;
      @endphp
   @endif
   
   @if(isset($editData->passengers) && count($editData->passengers))
      <div style="page-break-inside: always;">
         <div class="inv-section" style="margin-top: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
                  <thead>
                     <tr>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['passenger_details'] ?? 'passenger_details')) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $getCurrentTranslation['passenger_details'] ?? 'passenger_details' }} ({{ count($editData->passengers) }})</th>
                     </tr>
                  </thead>
            </table>
            {{-- <h3 style="font-family: {{ language_font(strip_tags('Eng')) }}; background-color: #e7e7e7; padding: 10px 10px; margin: 0; font-size: 15px; color: #32323b;">
               {{ $getCurrentTranslation['passenger_details'] ?? 'passenger_details' }} ({{ count($editData->passengers) }})
            </h3> --}}
            <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
               <table style="width: 100%; border-collapse: collapse;">
                  <thead>
                     <tr>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['passenger_name_label'] ?? 'passenger_name_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['passenger_name_label'] ?? 'passenger_name_label' }}</th>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['ticket_info'] ?? 'ticket_info')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['ticket_info'] ?? 'ticket_info' }}</th>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['baggage_allowance_label'] ?? 'baggage_allowance_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['baggage_allowance_label'] ?? 'baggage_allowance_label' }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     @foreach($editData->passengers as $key => $passenger)
                        <tr>
                           <td style="font-family: {{ language_font(strip_tags($passenger->name)) }}; color: #474751; padding: 10px; font-size: 14px;">
                              {{ $passenger->name }}{{ $passenger->gender ? ' (' . $passenger->gender . ')' : '' }}{{ $passenger->pax_type ? ', '.$passenger->pax_type : '' }} <br>
                              {{ $editData->ticket_type }}
                           </td>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              @if(isset($passenger->flights) && count($passenger->flights))
                                 @foreach($passenger->flights as $pKey => $pFlight)
                                    @if(count($passenger->flights) > 1 && $pKey != 0)
                                       <hr style="margin: 3px">
                                    @endif
                                    <div><b style="font-family: {{ language_font(strip_tags($getCurrentTranslation['a_pnr'] ?? 'a_pnr')) }}; ">{{ $getCurrentTranslation['a_pnr'] ?? 'a_pnr' }}:</b> <span style="font-family: {{ language_font(strip_tags($pFlight->airlines_pnr)) }};">{{ $pFlight->airlines_pnr ?? 'N/A' }}</span></div>
                                    {{-- <div><b>{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</b> {{ $pFlight->flight_number ?? 'N/A' }}</div> --}}
                                    @if($pFlight->ticket_number)
                                       <div><b style="font-family: {{ language_font(strip_tags($getCurrentTranslation['ticket_number'] ?? 'ticket_number')) }}; ">{{ $getCurrentTranslation['ticket_number'] ?? 'ticket_number' }}:</b> <span style="font-family: {{ language_font(strip_tags($pFlight->ticket_number)) }};">{{ $pFlight->ticket_number ?? 'N/A' }}</span></div>
                                    @endif
                                 @endforeach
                              @endif
                           </td>
                           <td style="font-family: {{ language_font(strip_tags($passenger->baggage_allowance)) }}; color: #474751; padding: 10px; font-size: 14px;">{!! nl2br(e($passenger->baggage_allowance ?? 'N/A')) !!}</td>
                        </tr>
                     @endforeach
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   @endif

   @if(isset($withPrice) && $withPrice == 1)
      <div style="page-break-inside: avoid;">
         <div class="inv-section" style="margin-top: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
               <thead>
                  <tr>
                     <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['invoice_summary'] ?? 'invoice_summary')) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $getCurrentTranslation['invoice_summary'] ?? 'invoice_summary' }}</th>
                  </tr>
               </thead>
            </table>
            {{-- <h3 style="font-family: {{ language_font(strip_tags('Eng')) }}; background-color: #e7e7e7; padding: 5px 10px; margin: 0; font-size: 14px; color: #333238;">Invoice Summary</h3> --}}
            <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
               <table style="width: 100%; border-collapse: collapse;">
                  <thead>
                     <tr>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['pax_type_label'] ?? 'pax_type_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['pax_type_label'] ?? 'pax_type_label' }}</th>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['base_fare'] ?? 'base_fare')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['base_fare'] ?? 'base_fare' }}</th>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['pax_count_label'] ?? 'pax_count_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['pax_count_label'] ?? 'pax_count_label' }}</th>
                        <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['amount'] ?? 'amount')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['amount'] ?? 'amount' }}</th>
                     </tr>
                  </thead>
                  <tbody>
                     @foreach($editData->fareSummary as $key => $fare)
                        @if(isset($ticket_passengers) && !empty($ticket_passengers) && count($ticket_passengers) == 1 && !empty($fare->pax_type) && $fare->pax_type == $ticket_passengers[0]->pax_type)
                           <tr>
                              <td style="font-family: {{ language_font(strip_tags($fare->pax_type ?? '')) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_type ?? $ticket_passengers[0]->pax_type }}</td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">
                                 {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($fare->unit_price, 2) ?? 'N/A' }}
                              </td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">{{ 1 }}</td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">
                                 {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($fare->unit_price, 2) ?? 'N/A' }}
                              </td>
                           </tr>

                           @php
                              $subtotal = $fare->unit_price;
                              $discount = ($editData->fareSummary[0]->discount/$editData->fareSummary->sum('pax_count'));
                              $grandtotal = ($subtotal-$discount);
                           @endphp
                           @break
                        @elseif(isset($ticket_passengers) && !empty($ticket_passengers) && count($ticket_passengers) == 1 && $fare->pax_count > 1)
                           <tr>
                              <td style="font-family: {{ language_font(strip_tags($fare->pax_type ?? '')) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_type ?? $ticket_passengers[0]->pax_type }}</td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">
                                 {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($fare->unit_price, 2) ?? 'N/A' }}
                              </td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">{{ 1 }}</td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">
                                 {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($fare->unit_price, 2) ?? 'N/A' }}
                              </td>
                           </tr>

                           @php
                              $subtotal = $fare->unit_price;
                              $discount = ($editData->fareSummary[0]->discount/$editData->fareSummary->sum('pax_count'));
                              $grandtotal = ($subtotal-$discount);
                           @endphp
                           @break
                        @else
                           <tr>
                              <td style="font-family: {{ language_font(strip_tags($fare->pax_type ?? '')) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_type ?? 'N/A' }}</td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">
                                 {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $fare->unit_price ?? 'N/A' }}
                              </td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_count ?? 'N/A' }}</td>
                              <td style="color: #474751; padding: 10px; font-size: 14px;">
                                 {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $fare->total ?? 'N/A' }}
                              </td>
                           </tr>
                        @endif
                     @endforeach
                        
                     @if(isset($subtotal) && isset($discount) && isset($grandtotal))
                           <tr>
                           <th class="bg-transparent" colspan="2" rowspan="3" style="background: transparent !important; border: none;"></th>
                           <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['subtotal_label'] ?? 'subtotal_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['subtotal_label'] ?? 'subtotal_label' }}</th>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($subtotal, 2) }}
                           </td>
                        </tr>
                        <tr>
                           <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['discount_label'] ?? 'discount_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['discount_label'] ?? 'discount_label' }}(-)</th>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($discount, 2) }}
                           </td>
                        </tr>
                        <tr>
                           <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label' }}</th>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ number_format($grandtotal, 2) }}
                           </td>
                        </tr>
                     @else
                        <tr>
                           <th class="bg-transparent" colspan="2" rowspan="3" style="background: transparent !important; border: none;"></th>
                           <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['subtotal_label'] ?? 'subtotal_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['subtotal_label'] ?? 'subtotal_label' }}</th>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $editData->fareSummary[0]->subtotal ?? '0.00' }}
                           </td>
                        </tr>
                        <tr>
                           <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['discount_label'] ?? 'discount_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['discount_label'] ?? 'discount_label' }}(-)</th>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $editData->fareSummary[0]->discount ?? '0.00' }}
                           </td>
                        </tr>
                        <tr>
                           <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">{{ $getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label' }}</th>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $editData->fareSummary[0]->grandtotal ?? '0.00' }}
                           </td>
                        </tr>
                     @endif
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   @endif

   @if(!empty($editData->footer_title) || !empty(strip_tags($editData->footer_text)))
      <div style="page-break-inside: avoid;">
         <div class="inv-section" style="margin-top: 20px;">
            {{-- <h3 style="font-family: {{ language_font(strip_tags($editData->footer_title)) }}; background-color: #e7e7e7; padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">
               {{ $editData->footer_title }}
            </h3> --}}
            <table style="width: 100%; border-collapse: collapse;">
               <thead>
                  <tr>
                     <th style="font-family: {{ language_font(strip_tags($editData->footer_title)) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $editData->footer_title }}</th>
                  </tr>
               </thead>
            </table>
            <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
               <div class="inv-footer-description" style="font-family: {{ language_font(strip_tags($editData->footer_text)) }}; font-size: 13px; color: #5d5e63; margin-bottom: 10px;">
                  {!! $editData->footer_text !!}
               </div>
            </div>
         </div>
      </div>
   @endif

   @php
      $email = $editData->user->company->email_1 ?? '';
      $phone = $editData->user->company->phone_1 ?? '';
   @endphp

   <br>
   <div style="">
      @if($email || $phone)
         @if($email) <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email_label'] ?? 'email_label')) }};">{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</span> {{ $email }} @endif
         @if($email && $phone) <br> @endif
         @if($phone) <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone_label'] ?? 'phone_label')) }};">{{ $getCurrentTranslation['phone_label'] ?? 'phone_label' }}:</span> {{ $phone }} @endif
      @endif
   </div>

   @if(config('app.enable_ticket_qr_code') && !empty($editData->ticket_uid))
      <div style="margin-top: 16px; text-align: left; page-break-inside: avoid;">
         <p style="font-size: 12px; color: #6c6c6c; margin-bottom: 6px; font-family: {{ language_font(strip_tags($getCurrentTranslation['scan_to_view_online'] ?? 'scan_to_view_online')) }};">{{ $getCurrentTranslation['scan_to_view_online'] ?? 'Scan to view online' }}</p>
         <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode(route('ticket.public.show', ['ticket_uid' => $editData->ticket_uid])) }}" alt="QR Code" style="width: 120px; height: 120px;">
      </div>
   @endif

   {{-- @if(!empty(strip_tags($editData->bank_details)))
      <div style="page-break-inside: avoid;">
         <div class="inv-section" style="margin-top: 20px;">
            <h3 style="font-family: {{ language_font(strip_tags($editData->bank_details)) }}; background-color: #e7e7e7; padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $getCurrentTranslation['bank_details_label'] ?? 'bank_details_label' }}</h3>
            <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
               <div class="inv-footer-description" style="font-size: 13px; color: #5d5e63; margin-bottom: 10px;">
                  {!! $editData->bank_details !!}
               </div>
            </div>
         </div>
      </div>
   @endif --}}

</div>

@if(!isset($view))
   </body>
</html>
@endif
