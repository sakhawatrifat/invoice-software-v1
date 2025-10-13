@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;"><img style="width: 250px!important; max-height: unset; height: auto; background: transparent; color: #ffffff; border-radius: 5px; padding: 5px 10px; text-align: center;" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" class="logo" alt="{{ $globalData->company_data->company_name ?? 'N/A' }}">
</a>
</td>
</tr>
