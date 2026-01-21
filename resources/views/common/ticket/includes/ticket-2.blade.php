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

      .flight-itineraries-table td{
         vertical-align: top;
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
                           <td style="font-family: {{ language_font(strip_tags($passenger->name)) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $passenger->name }}{{ $passenger->gender ? ' (' . $passenger->gender . ')' : '' }}{{ $passenger->pax_type ? ', '.$passenger->pax_type : '' }}</td>
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

   <div class="inv-section flight-itineraries-table" style="margin-top: 20px;">
      <table style="width: 100%; border-collapse: collapse; vertical-align: middle; background-color: transparent">
         <tr>
            <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['flight_itineraries'] ?? 'flight_itineraries')) }}; text-align: left; font-size: 18px; color: #32323b; font-weight: bold;padding: 10px 0px; background-color: #ffffff;">
              {{ $getCurrentTranslation['flight_itineraries'] ?? 'flight_itineraries' }}
            </th>
         </tr>
      </table>
      @foreach($editData->flights as $key => $flight)
         <table style="page-break-inside: avoid; width: 100%; border-collapse: collapse; vertical-align: middle; background-color: #e4e4e4; border: 1px solid #e4e4e4; margin-bottom: {{ $loop->last ? '5px' : '20px' }}">
            <tr>
               {{-- <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['flight'] ?? 'flight')) }}; width: 230px; text-align: left; font-size: 15px; color: #32323b; font-weight: bold;padding: 15px 10px;">
                  {{ $getCurrentTranslation['flight'] ?? 'flight' }} {{ $key+1 }}
               </th> --}}
               <th style="width: 230px; text-align: left; font-size: 15px; color: #32323b; font-weight: bold;padding: 15px 10px;">
                  Flight {{ $key+1 }}
               </th>
               <th style="text-align: left; font-size: 15px; color: #32323b; font-weight: bold;padding: 15px 10px;">
                  {{ \Carbon\Carbon::parse($flight->departure_date_time)->format('d M, Y') }}
               </th>
               <th style="text-align: right; font-size: 15px; color: #008000; font-weight: bold;padding: 15px 10px;">
                  {{ $editData->ticket_type }}
               </th>
            </tr>

            <tr>
               <td colspan="3" style="padding: 30px 20px 30px 20px; background-color: #ffffff; border: 1px solid #e4e4e4">
                  <table style="width:100%; border-collapse: collapse; vertical-align: top; background-color: #ffffff" >
                     <tr>
                        <td style="width: 30px; padding: 0px 10px; background-color: #ffffff">
                           <center>
                              <!-- Plane SVG -->
                              <svg fill="#000000" height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                                 viewBox="0 0 128 128" xml:space="preserve">
                              <g>
                                 <path fill="#74747c" d="M119.7,18.2c7.8-7.8-3-17.9-10.7-10.3L80.7,36.3L15.8,19.2L5,30l53.5,28.2L36.8,79.8L20,77.7l-8.6,8.6l19.1,10l10,19.1
                                    l8.6-8.6l-2-16.7l21.6-21.6l27.6,53.2l10.8-10.8L90.8,47.2L119.7,18.2z"/>
                              </g>
                              </svg>
                           </center>
                        </td>
                        <td style="width: 175px; background-color: #ffffff">
                           {{ \Carbon\Carbon::parse($flight->departure_date_time)->format('H:i') }}
                        </td>
                        <td style="padding: 0px 10px; background-color: #ffffff">
                           {{-- {{ extractPrimaryCity($flight->leaving_from) }} --}}
                           <span style="font-family: {{ language_font(strip_tags($flight->leaving_from)) }};">{{ $flight->leaving_from }}</span>
                        </td>
                        <td style="text-align: center; background-color: #ffffff; vertical-align:middle;" rowspan="4">
                           @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                              <img src="{{ $flight->airline->logo_url ?? '' }}" alt="{{ $flight->airline->name ?? '' }}" style="width: auto; max-width: 150px; vertical-align:middle; margin-right:10px;"><br>
                           @else
                              <h4 style="text-align: center; margin: 0; color: #32323b;">Airlines Logo Here</h4>
                           @endif
                           {{ $flight->airline->name ?? '' }} • {{ $flight->flight_number }}
                        </td>
                        {{-- <td style="background-color: #ffffff"></td> --}}
                     </tr>

                     <tr>
                        <td style="width: 30px; background-color: #ffffff">
                           <center>
                              <table>
                                 <tr><td style="height: 50px; border-left: 3px dotted #74747c"></td></tr>
                              </table>
                           </center>
                        </td>
                        <td style="background-color: #ffffff; vertical-align: middle; font-family: {{ language_font(strip_tags($getCurrentTranslation['fly_time'] ?? 'fly_time')) }};">
                           {{ $getCurrentTranslation['fly_time'] ?? 'fly_time' }}: {{ $flight->total_fly_time }}
                        </td>
                        <td style="background-color: #ffffff"></td>
                        {{-- <td style="background-color: #ffffff"></td> --}}
                     </tr>

                     <tr>
                        <td style="width: 30px; background-color: #ffffff;">
                           <center>
                              <!-- Location SVG -->
                              <svg width="20px" height="20px" viewBox="-4 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                                 <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                    <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-106.000000, -413.000000)" fill="#000000">
                                       <path fill="#74747c" d="M118,422 C116.343,422 115,423.343 115,425 C115,426.657 116.343,428 118,428 C119.657,428 121,426.657 121,425 C121,423.343 119.657,422 118,422 L118,422 Z M118,430 C115.239,430 113,427.762 113,425 C113,422.238 115.239,420 118,420 C120.761,420 123,422.238 123,425 C123,427.762 120.761,430 118,430 L118,430 Z M118,413 C111.373,413 106,418.373 106,425 C106,430.018 116.005,445.011 118,445 C119.964,445.011 130,429.95 130,425 C130,418.373 124.627,413 118,413 L118,413 Z" id="location" sketch:type="MSShapeGroup">
                                       </path>
                                    </g>
                                 </g>
                              </svg>
                           </center>
                        </td>
                        <td style="width: 150px; background-color: #ffffff">
                           {{ \Carbon\Carbon::parse($flight->arrival_date_time)->format('H:i') }}
                        </td>
                        <td style="padding: 0px 10px; background-color: #ffffff">
                           @php
                              $departureDate = \Carbon\Carbon::parse($flight->departure_date_time)->format('Y-m-d');
                              $arrivalDate = \Carbon\Carbon::parse($flight->arrival_date_time)->format('Y-m-d');
                           @endphp
                           @if($departureDate != $arrivalDate)
                              <span style="color: red">{{ \Carbon\Carbon::parse($flight->arrival_date_time)->format('d M, Y') }} <br></span>
                           @endif
                           {{-- {{ extractPrimaryCity($flight->going_to) }} --}}
                           <span style="font-family: {{ language_font(strip_tags($flight->going_to)) }};">{{ $flight->going_to }}</span>
                        </td>
                        
                     </tr>
                  </table>
                  <table style="width: 100%; vertical-align: middle;">
                     <tr>
                        <td style="background-color: #ffffff; padding: 20px 10px 0px 10px; width: 30px">
                           <svg fill="#000000" height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                              viewBox="0 0 300 300" xml:space="preserve">
                              <g>
                                 <g>
                                    <path transform="rotate(45 150 150)" fill="#ff0749" d="M149.997,0C67.158,0,0.003,67.161,0.003,149.997S67.158,300,149.997,300s150-67.163,150-150.003S232.837,0,149.997,0z
                                       M222.978,99.461l-32.435,30.711l20.562,82.844l-12.27,12.27l-39.262-64.028l-0.905-0.905l-40.385,38.24
                                       c-0.228,0.231-0.485,0.405-0.718,0.622l-1.297,29.481l-44.965-44.962l29.471-1.294c0.218-0.239,0.394-0.493,0.625-0.724
                                       l38.24-40.387L139.314,141l-64.601-39.832l12.27-12.27l82.471,20.946l31.079-32.827c6.201-6.201,16.251-6.199,22.447,0
                                       C229.177,83.215,229.177,93.263,222.978,99.461z"/>
                                 </g>
                              </g>
                           </svg>
                        </td>
                        <td style="background-color: #ffffff; padding: 20px 10px 0px 10px; font-family: {{ language_font(strip_tags($flight->flight_number)) }};">
                           {{-- {{  $flight->airline->name }} --}}
                           {{ $flight->flight_number }}
                        </td>
                        <td style="background-color: #ffffff; padding: 20px 0px 0px 10px; text-align: right;">
                           @if($departureDate != $arrivalDate)
                              <span style="color: red; font-family: {{ language_font(strip_tags($getCurrentTranslation['arrives_next_day'] ?? 'arrives_next_day')) }};">{{ $getCurrentTranslation['arrives_next_day'] ?? 'arrives_next_day' }}</span>
                           @endif
                        </td>
                     </tr>
                  </table>
               </td>
            </tr>

            @foreach($flight->transits as $tKey => $transit)
               <tr>
                  <th style="width: 230px; text-align: left; font-size: 15px; color: #008000; background: #f4f4f4; border-left: 1px solid #e4e4e4; font-weight: bold;padding: 15px 10px; font-family: {{ language_font(strip_tags($transit->leaving_from)) }};">
                     Layover in {{ extractPrimaryCity($transit->leaving_from) }}
                  </th>
                  <th style="text-align: left; font-size: 15px; color: #008000; background: #f4f4f4; font-weight: bold;padding: 15px 10px;">
                     {{ \Carbon\Carbon::parse($transit->departure_date_time)->format('d M, Y') }}
                  </th>
                  <th style="text-align: right; font-size: 15px; color: #008000; background: #f4f4f4; border-right: 1px solid #e4e4e4; font-weight: bold;padding: 15px 10px; font-family: {{ language_font(strip_tags($transit->total_transit_time)) }};">
                     {{ $transit->total_transit_time }}
                  </th>
               </tr>

               <tr>
                  <td colspan="3" style="padding: 30px 20px 30px 20px; background-color: #ffffff; border: 1px solid #e4e4e4">
                     <table style="width:100%; border-collapse: collapse; vertical-align: top; background-color: #ffffff" >
                        <tr>
                           <td style="width: 30px; padding: 0px 10px; background-color: #ffffff">
                              <center>
                                 <!-- Plane SVG -->
                                 <svg fill="#000000" height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                                    viewBox="0 0 128 128" xml:space="preserve">
                                 <g>
                                    <path fill="#74747c" d="M119.7,18.2c7.8-7.8-3-17.9-10.7-10.3L80.7,36.3L15.8,19.2L5,30l53.5,28.2L36.8,79.8L20,77.7l-8.6,8.6l19.1,10l10,19.1
                                       l8.6-8.6l-2-16.7l21.6-21.6l27.6,53.2l10.8-10.8L90.8,47.2L119.7,18.2z"/>
                                 </g>
                                 </svg>
                              </center>
                           </td>
                           <td style="width: 175px; background-color: #ffffff">
                              {{ \Carbon\Carbon::parse($transit->departure_date_time)->format('H:i') }}
                           </td>
                           <td style="padding: 0px 10px; background-color: #ffffff; font-family: {{ language_font(strip_tags($transit->leaving_from)) }};">
                              {{-- {{ extractPrimaryCity($transit->leaving_from) }} --}}
                              {{ $transit->leaving_from }}
                           </td>
                           {{-- <td style="background-color: #ffffff"></td> --}}
                           <td style="text-align: center; background-color: #ffffff; vertical-align:middle;" rowspan="4">
                              @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                 <img src="{{ $transit->airline->logo_url ?? '' }}" alt="{{ $transit->airline->name ?? '' }}" style="width: auto; max-width: 150px; vertical-align:middle; margin-right:10px;"><br>
                              @else
                                 <h4 style="text-align: center; margin: 0; color: #32323b;">Airlines Logo Here</h4>
                              @endif
                              {{ $transit->airline->name ?? '' }} • {{ $transit->flight_number }}
                           </td>
                        </tr>

                        <tr>
                           <td style="width: 30px;  background-color: #ffffff">
                              <center>
                                 <table>
                                    <tr><td style="height: 50px; border-left: 3px dotted #74747c"></td></tr>
                                 </table>
                              </center>
                           </td>
                           <td style="background-color: #ffffff; vertical-align: middle; font-family: {{ language_font(strip_tags($getCurrentTranslation['fly_time'] ?? 'fly_time')) }};">
                              {{ $getCurrentTranslation['fly_time'] ?? 'fly_time' }}: {{ $transit->total_fly_time }}
                           </td>
                           <td style="background-color: #ffffff"></td>
                           {{-- <td style="background-color: #ffffff"></td> --}}
                        </tr>

                        <tr>
                           <td style="width: 30px; background-color: #ffffff;">
                              <center>
                                 <!-- Location SVG -->
                                 <svg width="20px" height="20px" viewBox="-4 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage">
                                       <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-106.000000, -413.000000)" fill="#000000">
                                          <path fill="#74747c" d="M118,422 C116.343,422 115,423.343 115,425 C115,426.657 116.343,428 118,428 C119.657,428 121,426.657 121,425 C121,423.343 119.657,422 118,422 L118,422 Z M118,430 C115.239,430 113,427.762 113,425 C113,422.238 115.239,420 118,420 C120.761,420 123,422.238 123,425 C123,427.762 120.761,430 118,430 L118,430 Z M118,413 C111.373,413 106,418.373 106,425 C106,430.018 116.005,445.011 118,445 C119.964,445.011 130,429.95 130,425 C130,418.373 124.627,413 118,413 L118,413 Z" id="location" sketch:type="MSShapeGroup">
                                          </path>
                                       </g>
                                    </g>
                                 </svg>
                              </center>
                           </td>
                           <td style="width: 150px; background-color: #ffffff">
                              {{ \Carbon\Carbon::parse($transit->arrival_date_time)->format('H:i') }}
                           </td>
                           <td style="padding: 0px 10px; background-color: #ffffff">
                              @php
                                 $departureDate = \Carbon\Carbon::parse($transit->departure_date_time)->format('Y-m-d');
                                 $arrivalDate = \Carbon\Carbon::parse($transit->arrival_date_time)->format('Y-m-d');
                              @endphp
                              @if($departureDate != $arrivalDate)
                                 <span style="color: red">{{ \Carbon\Carbon::parse($transit->arrival_date_time)->format('d M, Y') }} <br></span>
                              @endif
                              {{-- {{ extractPrimaryCity($transit->going_to) }} --}}
                              <span style="font-family: {{ language_font(strip_tags($transit->going_to)) }};">{{ $transit->going_to }}</span>
                           </td>
                        </tr>
                     </table>
                     <table style="width: 100%; vertical-align: middle;">
                        <tr>
                           <td style="background-color: #ffffff; padding: 20px 10px 0px 10px; width: 30px">
                              <svg fill="#000000" height="20px" width="20px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                                 viewBox="0 0 300 300" xml:space="preserve">
                                 <g>
                                    <g>
                                       <path transform="rotate(45 150 150)" fill="#214bc4" d="M149.997,0C67.158,0,0.003,67.161,0.003,149.997S67.158,300,149.997,300s150-67.163,150-150.003S232.837,0,149.997,0z
                                          M222.978,99.461l-32.435,30.711l20.562,82.844l-12.27,12.27l-39.262-64.028l-0.905-0.905l-40.385,38.24
                                          c-0.228,0.231-0.485,0.405-0.718,0.622l-1.297,29.481l-44.965-44.962l29.471-1.294c0.218-0.239,0.394-0.493,0.625-0.724
                                          l38.24-40.387L139.314,141l-64.601-39.832l12.27-12.27l82.471,20.946l31.079-32.827c6.201-6.201,16.251-6.199,22.447,0
                                          C229.177,83.215,229.177,93.263,222.978,99.461z"/>
                                    </g>
                                 </g>
                              </svg>
                           </td>
                           <td style="background-color: #ffffff; padding: 20px 10px 0px 10px; font-family: {{ language_font(strip_tags($transit->flight_number)) }};">
                              {{-- {{  $transit->airline->name }} --}}
                              {{ $transit->flight_number }}
                           </td>
                           <td style="background-color: #ffffff; padding: 20px 0px 0px 10px; text-align: right;">
                              @if($departureDate != $arrivalDate)
                                 <span style="color: red; font-family: {{ language_font(strip_tags($getCurrentTranslation['arrives_next_day'] ?? 'arrives_next_day')) }};">{{ $getCurrentTranslation['arrives_next_day'] ?? 'arrives_next_day' }}</span>
                              @endif
                           </td>
                        </tr>
                     </table>
                  </td>
               </tr>
            @endforeach
         </table>
      @endforeach
      <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['all_times_shown_are_local_time'] ?? 'all_times_shown_are_local_time')) }};">{{ $getCurrentTranslation['all_times_shown_are_local_time'] ?? 'all_times_shown_are_local_time' }}</span>
   </div>

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