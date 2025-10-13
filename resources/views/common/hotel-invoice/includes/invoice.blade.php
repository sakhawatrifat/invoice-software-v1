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
<body style="font-family: Arial, sans-serif; color: #5d5e63; font-size: 13px; margin: 0; padding: 0; background: #ffffff;">
@endif

<div class="inv-content-wrapper" style="font-family: Arial, sans-serif; color: #000; background: #fff; {{ (isset($view)) && $view == 1 ? 'width: 60%; margin: 0 auto;' : '' }}">
    
    <style>
        *{
            font-family: Arial, sans-serif;
            color: #333333;
        }
    </style>

    <!-- Header -->
    <table width="100%" style="margin-bottom: 10px">
        <tr>
            <td style="vertical-align: middle; padding: 0px;">
                {{-- <h3 style="margin: 0; font-size: 20px; font-weight: bold; color: #2346ff">
                    {{ $editData->website_name }}
                </h3> --}}
                <div class="inv-logo" style="text-align: left; margin-bottom: 0px;">
                    @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                        <img src="{{ $editData->user->company->dark_logo_url ?? '' }}" alt="{{ $editData->user->company->company_name ?? 'N/A' }} Logo" style="max-width: 150px; height: auto;">
                    @else
                        <h4 style="text-align: center; margin: 0; color: #333238;">{{ $editData->user->company->company_name ?? 'N/A' }} Logo Here</h4>
                    @endif
                </div>
            </td>
            <td colspan="2" style="text-align: right; vertical-align: middle; padding: 0px;">
                <div class="small">Check-in voucher</div>
                <div class="small">PIN : {{ $editData->pin_number }}</div>
                <div class="small">Booking No: {{ $editData->booking_number }}</div>
            </td>
        </tr>
    </table>

    <!-- Body -->
    <table width="100%" style="border: 2px solid #8b98ab; margin-bottom: 0">
        <tr>
            <td colspan="3" style="vertical-align: baseline; padding: 14px; border-bottom: 1px solid #dddddd;">
                <table style="margin-bottom: 0px">
                    <tr>
                        <td style="width: 130px; vertical-align: top;">
                            @if(!empty(env('DB_PASSWORD')) || (isset($view)) && $view == 1)
                                <img src="{{ $editData->hotel_image_url }}" alt="{{ $editData->hotel_name }}" style="max-width: 130px; height:auto;">
                            @else
                                <h4 style="text-align: center; margin: 0; color: #32323b;">Hotel image here</h4>
                            @endif
                        </td>
                        <td style="vertical-align: top; padding: 2px 10px 0">
                            <div style="font-size: 18px; font-weight: bold;">{{ $editData->hotel_name }}</div>
                            <div>{!! nl2br($editData->hotel_address) !!}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="vertical-align: middle; padding: 25px 10px; border-bottom: 1px solid #dddddd;">
                <table style="width: 100%">
                    <tr>
                        <td style="vertical-align: middle; padding: 0px 25px 0px 0px; text-align: center; ; width: 35%">
                            <div>Check-in</div>
                            <div style="font-size: 20px; font-weight: bold;">{{ date('M d, Y', strtotime($editData->check_in_date)) }}</div>
                            <div>{{ date('D', strtotime($editData->check_in_date)) }}</div>
                            <div>After {{ date('H:i', strtotime($editData->check_in_time)) }}</div>
                            <div style="color: #787878">Hotel's local time</div>
                        </td>
                        <td style="vertical-align: middle; padding: 0px 25px 0px 25px; border-left: 1px solid #dddddd; border-right: 1px solid #dddddd; text-align: center">
                            <div>Check-out</div>
                            <div style="font-size: 20px; font-weight: bold;">{{ date('M d, Y', strtotime($editData->check_out_date)) }}</div>
                            <div>{{ date('D', strtotime($editData->check_out_date)) }}</div>
                            <div>Before {{ date('H:i', strtotime($editData->check_out_time)) }}</div>
                            <div style="color: #787878">Hotel's local time</div>
                        </td>
                        <td style="vertical-align: middle; padding: 0px 0px 0px 25px; text-align: center;">
                            <table style="width:auto; margin-bottom: 0px; margin:0 auto;">
                                <tr>
                                    <td style="text-align: center;">
                                        Rooms
                                    </td>
                                    <td style="text-align: center;">
                                        &nbsp;
                                    </td>
                                    <td style="text-align: center;">
                                        Nights
                                    </td>
                                </tr>
                                <tr>
                                    <td style="text-align: center; font-size: 20px; font-weight: bold;">
                                        {{ $editData->total_room }}
                                    </td>
                                    <td style="text-align: center; font-size: 20px; font-weight: bold; color: #787878">
                                        /
                                    </td>
                                    <td style="text-align: center; font-size: 20px; font-weight: bold;">
                                        {{ $editData->total_night }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="3" style="padding: 25px 10px; border-bottom: 1px solid #dddddd;">
                <table style="width: 100%">
                    <tr>
                        <td style="vertical-align: baseline; padding: 0px 25px; border-right: 1px solid #dddddd; width: 35%">
                            <div style="font-size: 18px; font-weight: bold; ">Price Details</div> <br>
                            <div style="color: #787878">Prepay Online</div>
                            <div style="">
                                <span style="">{{ $globalData->company->currency->short_name ?? 'N/A' }} {{ number_format($editData->total_price, 2) }}</span>
                                <span style="border: 1px solid #7c99d6; color: #7c99d6; padding: 0px 2px; border-radius: 3px">{{ $editData->payment_status }}</span>
                            </div>
                        </td>

                        <td colspan="2" style="vertical-align: baseline; padding: 0px 25px 0px 30px; ">
                        <div style="font-size: 18px; font-weight: bold; ">{{ $editData->room_type }}</div> <br>
                            <div style="color: #787878">Guest Names</div>
                            @if(isset($editData) && !empty($editData->guestInfo) && is_array($editData->guestInfo))
                                @foreach($editData->guestInfo as $item)
                                    <div style="">
                                        {{ $item['name'] ?? 'N/A' }}

                                        @if(isset($item['passport_number']) && !empty($item['passport_number']))
                                            (Passport No: {{ $item['passport_number'] }})
                                        @endif
                                    </div>
                                @endforeach
                            @endif

                            <br><div style="color: #787878">Occupancy (Per Room)</div>
                            <div style="">{!! nl2br($editData->occupancy_info) !!}</div> <br>
                            
                            @if(!empty($editData->room_info))
                                <div style="color: #787878">Room info</div>
                                <div style="">{!! nl2br($editData->room_info) !!}</div> <br>
                            @endif
                                
                            @if(!empty($editData->meal_info))
                                <div style="color: #787878">Meals</div>
                                <div style="">{!! nl2br($editData->meal_info) !!}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="3" style="padding: 25px 10px;">
                <table style="width: 100%">
                    <tr>
                        <td style="width: 35%; vertical-align: baseline; padding: 0px 25px;">
                            <div style="#dddddd; text-align: left">
                                <div style="font-size: 18px; font-weight: bold; ">Room Amenities</div> <br>
                                <div style="">
                                    {!! nl2br($editData->room_amenities) !!}
                                </div>
                            </div>
                        </td>
                        <td colspan="2" style="vertical-align: baseline; padding: 0px 25px; text-align: left; padding-left: 35px; border-left: 1px solid #dddddd;">
                            @if(isset($editData) && !empty($editData->cancellationPolicy) && is_array($editData->cancellationPolicy))
                                <div style="font-size: 18px; font-weight: bold; ">Cancellation Policy</div> <br>
                                <table style="width: 100%; margin-bottom: 5px; border-collapse: collapse;">
                                    <tr>
                                        <th style="text-align: center; border: 1px solid #dddddd; padding: 5px; box-sizing: border-box;">
                                            Hotel's Local Time
                                        </th>
                                        <th style="text-align: center; border: 1px solid #dddddd; padding: 5px; box-sizing: border-box;">
                                            Fee
                                        </th>
                                    </tr>
                                    @foreach($editData->cancellationPolicy as $item)
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
                                <div>{!! nl2br($editData->policy_note) !!}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table width="100%" style="margin-top: 10px">
        <tr>
            <td style="vertical-align: top; padding: 0px;">
                <div style="font-size: 15px; font-weight: bold; ">Contact Us</div>
                {!! nl2br($editData->contact_info) !!}
            </td>
        </tr>
    </table>

</div>

@if(!isset($view))
</body>
</html>
@endif
