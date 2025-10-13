@php
   $getCurrentTranslation = getCurrentTranslation();
@endphp

@if(!isset($view))
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</title>
      <link rel="icon" href="{{ $globalData->company_data->dark_icon_url ?? '' }}" />
   </head>
   <body style="font-family: Arial, sans-serif; color: #5d5e63; font-size: 13px; margin: 0; padding: 0; background: #fff;">
@endif

<div class="inv-content-wrapper" style="font-family: Arial, sans-serif; color: #000; background: #fff;">
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
   <div class="inv-header" style="width: 100%;">
      <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
         <thead>
            <tr>
               <td style="color: #474751; width: 50%; padding-right: 20px; vertical-align: top;">
                  <div class="inv-logo mb-2" style="text-align: left; margin-bottom: 20px;">
                     @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                        <img src="{{ $editData->user->company->dark_logo_url ?? '' }}" alt="{{ $editData->user->company->company_name ?? 'N/A' }} Logo" style="max-width: 150px; height: auto;">
                     @else
                        <h4 style="text-align: center; margin: 0; color: #333238;">{{ $editData->user->company->company_name ?? 'N/A' }} Logo Here</h4>
                     @endif
                  </div>
               </td>
               <td style="color: #474751; width: 50%; vertical-align: top;">
                  <div class="inv-title mb-2" style="text-align: left; margin-bottom: 20px;">
                     <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['customer'] ?? 'customer')) }}; display: block; color: #333238;">{{ $getCurrentTranslation['customer'] ?? 'customer' }}</span>
                     <h2 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['invoice'] ?? 'invoice')) }}; margin: 0; font-size: 24px; color: #333238;">{{ $getCurrentTranslation['invoice'] ?? 'invoice' }}</h2>
                     <table style="width: 100%; border-collapse: collapse; margin-top: 0px;">
                        <tr>
                           <td style="color: #474751; padding: 0; vertical-align: top;">
                              <p style="margin: 0; font-size: 13px; color: #333238;">
                                 @if(!empty($editData->invoice_id))
                                    <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label')) }};">{{ $getCurrentTranslation['invoice_id_label'] ?? 'invoice_id_label' }}:</span> <strong><span style="font-family: {{ language_font(strip_tags($editData->invoice_id)) }};">{{ $editData->invoice_id ?? '' }}</span></strong><br>
                                 @endif
                                 <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['reservation_number_label'] ?? 'reservation_number_label')) }};">{{ $getCurrentTranslation['reservation_number_label'] ?? 'reservation_number_label' }}: </span>
                                 <strong><span style="font-family: {{ language_font(strip_tags($editData->reservation_number)) }};">{{ $editData->reservation_number ?? '' }}</span></strong>
                              </p>
                           </td>
                        </tr>
                     </table>
                  </div>
               </td>
            </tr>
            <tr>
               <td style="color: #474751; width: 50%; vertical-align: top; padding-right: 5%; padding-top: 20px;">
                  <div class="inv-logo" style="text-align: left; font-size: 13px; color: #333238;">
                     <h4 style="font-family: {{ language_font(strip_tags($editData->user->company->company_name ?? '')) }}; margin: 0 0 5px 0; color: #333238;">{{ $editData->user->company->company_name ?? '' }}</h4>
                     @if($editData->user->company && $editData->user->company->tagline)
                        <span style="font-family: {{ language_font(strip_tags($editData->user->company->tagline ?? '')) }};">{{ $editData->user->company->tagline ?? '' }}</span>
                     @endif
                     <div style="font-family: {{ language_font(strip_tags($editData->user->company->address ?? '')) }}; ">
                        <br>
                        {!! $editData->user->company->address ?? '' !!}
                     </div>
                     <p>
                        @php
                           $email = $editData->user->company->email_1 ?? '';
                           $phone = $editData->user->company->phone_1 ?? '';
                        @endphp

                        @if($email || $phone)
                           @if($email) <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['email_label'] ?? 'email_label')) }};">{{ $getCurrentTranslation['email_label'] ?? 'email_label' }}:</span> {{ $email }} @endif
                           @if($email && $phone) <br> @endif
                           @if($phone) <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['phone_label'] ?? 'phone_label')) }};">{{ $getCurrentTranslation['phone_label'] ?? 'phone_label' }}:</span> {{ $phone }} @endif
                        @endif
                     </p>
                  </div>
               </td>
               <td style="color: #474751; width: 50%; vertical-align: top; padding-top: 20px">
                  <div class="inv-title" style="font-family: {{ language_font(strip_tags($editData->bill_to_info ?? '')) }}; text-align: left; font-size: 13px; color: #333238;">
                     {!! $editData->bill_to_info ?? '' !!}
                  </div>
               </td>
            </tr>
            <tr>
               <td colspan="2" style="color: #474751; padding: 0; text-align: right; vertical-align: top;">
                  <p style="margin: 0; font-size: 13px; color: #333238;">
                     <span style="font-family: {{ language_font(strip_tags($getCurrentTranslation['date_label'] ?? 'date_label')) }};">{{ $getCurrentTranslation['date_label'] ?? 'date_label' }}:</span> 
                     @if(!empty($editData->invoice_date))
                        <strong>{{ date('d M Y', strtotime($editData->invoice_date)) }}</strong>
                     @else
                        N/A
                     @endif
                  </p>
               </td>
            </tr>
         </thead>
      </table>
   </div>

   @if(isset($passenger) && !empty($passenger))
      @php
         $editData->passengers = collect([$passenger]);
      @endphp
   @endif

   @if(isset($invoice_passengers) && !empty($invoice_passengers))
      @php
         $editData->passengers = $invoice_passengers;
      @endphp
   @endif
   
   @if(count($editData->passengers))
      <div class="inv-section" style="margin-top: 20px;">
         {{-- <h3 style="font-family: {{ language_font(strip_tags($getCurrentTranslation['passenger_details'] ?? 'passenger_details')) }}; background-color: #e7e7e7; padding: 5px 10px; margin: 0; font-size: 14px; color: #333238;">
            Passenger Details ({{ count($editData->passengers) }})
         </h3> --}}
         <table style="width: 100%; border-collapse: collapse;">
            <thead>
               <tr>
                  <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['passenger_details'] ?? 'passenger_details')) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $getCurrentTranslation['passenger_details'] ?? 'passenger_details' }} ({{ count($editData->passengers) }})</th>
               </tr>
            </thead>
         </table>
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
                        <td style="font-family: {{ language_font(strip_tags($passenger->name)) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $passenger->name }}{{ $passenger->pax_type ? ', '.$passenger->pax_type : '' }}</td>
                        <td style="color: #474751; padding: 10px; font-size: 14px;">
                           @if(isset($passenger->flights) && count($passenger->flights))
                              @foreach($passenger->flights as $pKey => $pFlight)
                                 @if(count($passenger->flights) > 1 && $pKey != 0)
                                    <hr style="margin: 3px">
                                 @endif
                                 <div><b style="font-family: {{ language_font(strip_tags($getCurrentTranslation['a_pnr'] ?? 'a_pnr')) }}; ">{{ $getCurrentTranslation['a_pnr'] ?? 'a_pnr' }}:</b> <span style="font-family: {{ language_font(strip_tags($pFlight->airlines_pnr)) }};">{{ $pFlight->airlines_pnr ?? 'N/A' }}</span></div>
                                 {{-- <div><b style="font-family: {{ language_font(strip_tags($getCurrentTranslation['flight_number_label'] ?? 'flight_number_label')) }}; ">{{ $getCurrentTranslation['flight_number_label'] ?? 'flight_number_label' }}:</b> {{ $pFlight->flight_number ?? 'N/A' }}</div> --}}
                                 @if($pFlight->ticket_number)
                                    <div><b style="font-family: {{ language_font(strip_tags($getCurrentTranslation['ticket_number'] ?? 'ticket_number')) }}; ">{{ $getCurrentTranslation['ticket_number'] ?? 'ticket_number' }}:</b> <span style="font-family: {{ language_font(strip_tags($pFlight->ticket_number)) }};">{{ $pFlight->ticket_number ?? 'N/A' }}</span></div>
                                 @endif
                              @endforeach
                           @else
                              <div style="text-align: left">N/A</div>
                           @endif
                        </td>
                        <td style="font-family: {{ language_font(strip_tags($passenger->baggage_allowance ?? 'N/A')) }}; color: #474751; padding: 10px; font-size: 14px;">{!! nl2br(e($passenger->baggage_allowance ?? 'N/A')) !!}</td>
                     </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </div>
   @endif

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
                     @if(isset($invoice_passengers) && !empty($invoice_passengers) && count($invoice_passengers) == 1 && !empty($fare->pax_type) && $fare->pax_type == $invoice_passengers[0]->pax_type)
                        <tr>
                           <td style="font-family: {{ language_font(strip_tags($fare->pax_type ?? '')) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_type ?? $invoice_passengers[0]->pax_type }}</td>
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
                     @elseif(isset($invoice_passengers) && !empty($invoice_passengers) && count($invoice_passengers) == 1 && $fare->pax_count > 1)
                        <tr>
                           <td style="font-family: {{ language_font(strip_tags($fare->pax_type ?? '')) }}; color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_type ?? $invoice_passengers[0]->pax_type }}</td>
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
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $fare->unit_price ? number_format($fare->unit_price, 2) : 'N/A' }}
                           </td>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">{{ $fare->pax_count ?? 'N/A' }}</td>
                           <td style="color: #474751; padding: 10px; font-size: 14px;">
                              {{ $editData->user->company_data->currency->short_name ?? 'N/A' }} {{ $fare->total ? number_format($fare->total, 2) : 'N/A' }}
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
                     @php
                         $currency = $editData->user->company_data->currency->short_name ?? 'N/A';
                         $fareSummary = collect($editData->fareSummary)->first();

                         $subtotal = optional($fareSummary)->subtotal ?? 0;
                         $discount = optional($fareSummary)->discount ?? 0;
                         $grandtotal = optional($fareSummary)->grandtotal ?? 0;
                     @endphp

                     <tr>
                         <th class="bg-transparent" colspan="2" rowspan="3" style="background: transparent !important; border: none;"></th>
                         <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['subtotal_label'] ?? 'subtotal_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">
                             {{ $getCurrentTranslation['subtotal_label'] ?? 'subtotal_label' }}
                         </th>
                         <td style="color: #474751; padding: 10px; font-size: 14px;">
                             {{ $currency }} {{ number_format($subtotal, 2) }}
                         </td>
                     </tr>

                     <tr>
                         <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['discount_label'] ?? 'discount_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">
                             {{ $getCurrentTranslation['discount_label'] ?? 'discount_label' }}(-)
                         </th>
                         <td style="color: #474751; padding: 10px; font-size: 14px;">
                             {{ $currency }} {{ number_format($discount, 2) }}
                         </td>
                     </tr>

                     <tr>
                         <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label')) }}; color: #32323b; padding: 10px; font-size: 14px; text-align: left;">
                             {{ $getCurrentTranslation['grandtotal_label'] ?? 'grandtotal_label' }}
                         </th>
                         <td style="color: #474751; padding: 10px; font-size: 14px;">
                             {{ $currency }} {{ number_format($grandtotal, 2) }}
                         </td>
                     </tr>

                  @endif
               </tbody>
            </table>
         </div>
      </div>
   </div>

   @if(!empty($editData->footer_title) || !empty(strip_tags($editData->footer_text)))
      <div style="page-break-inside: avoid;">
         <div class="inv-section" style="margin-top: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
               <thead>
                  <tr>
                     <th style="font-family: {{ language_font(strip_tags($editData->footer_title)) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $editData->footer_title }}</th>
                  </tr>
               </thead>
            </table>
            {{-- <h3 style="font-family: {{ language_font(strip_tags($editData->footer_title)) }}; background-color: #e7e7e7; padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">
               {{ $editData->footer_title }}
            </h3> --}}
            <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
               <div class="inv-footer-description" style="font-family: {{ language_font(strip_tags($editData->footer_text)) }}; font-size: 13px; color: #5d5e63; margin-bottom: 10px;">
                  {!! $editData->footer_text !!}
               </div>
            </div>
         </div>
      </div>
   @endif

   @if(!empty(strip_tags($editData->bank_details)))
      <div style="page-break-inside: avoid;">
         <div class="inv-section" style="margin-top: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
               <thead>
                  <tr>
                     <th style="font-family: {{ language_font(strip_tags($getCurrentTranslation['bank_details_label'] ?? 'bank_details_label')) }};padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">{{ $getCurrentTranslation['bank_details_label'] ?? 'bank_details_label' }}</th>
                  </tr>
               </thead>
            </table>
            {{-- <h3 style="font-family: {{ language_font(strip_tags($editData->bank_details)) }}; background-color: #e7e7e7; padding: 5px 10px; margin: 0; font-size: 15px; color: #32323b;">Bank Details</h3> --}}
            <div class="inv-table-wrapper" style="padding: 10px; border: 1px solid #e5e5e5;">
               <div class="inv-footer-description" style="font-family: {{ language_font(strip_tags($editData->bank_details)) }}; font-size: 13px; color: #5d5e63; margin-bottom: 10px;">
                  {!! $editData->bank_details !!}
               </div>
            </div>
         </div>
      </div>
   @endif

   @if($editData->user->company && $editData->user->company->dark_seal_url)
      <div style="page-break-inside: avoid;">
         <div style="margin-top: 20px; text-align: right;">
            @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
               <img src="{{ $editData->user->company->dark_seal_url ?? '' }}" alt="Company Seal" style="max-width: 77px; height: auto;">
            @else
               <h4 style="text-align: right; margin: 0; color: #333238;">Company Seal Here Here</h4>
            @endif
         </div>
      </div>
   @endif
   
   {{-- @if($editData->user->company && $editData->user->company->company_invoice_id)
      <div style="page-break-inside: avoid;">
         <div style="margin-top: 20px; text-align: center;">
               <h4 style="font-family: {{ language_font(strip_tags($editData->user->company->company_invoice_id ?? '')) }}; text-align: center; margin: 0; color: #333238;">{{ $editData->user->company->company_invoice_id }}</h4>
         </div>
      </div>
   @endif --}}

</div>

@if(!isset($view))
   </body>
</html>
@endif
