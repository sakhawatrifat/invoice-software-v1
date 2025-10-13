@component('mail::message')
<style type="text/css">
	*:not(a){
		color: #333333!important;
	}
</style>

Hi {{$content['user']->name}},<br>
Welcome to {{ config('app.name') }}. You've registered as a {{strtoupper(str_replace('_', ' ', $content['user']->user_type))}}.
All your account information is given below. Please take a look: <br>

<b>Name:</b> {{$content['user']->name}} <br>
@if(!empty($content['user']->address))
<b>Address:</b> {{$content['user']->address}} <br>
@endif
@if(!empty($content['user']->phone))
<b>Phone:</b> {{$content['user']->phone}} <br>
@endif
<b>Email:</b> {{$content['user']->email}} <br>
<b>Account Status:</b> 
@if($content['user']->status == 'Active')
<span style="background-color: #28a745; color: #ffffff!important; padding: 0px 6px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block;">Active</span>
@else
<span style="background-color: #dc3545; color: #ffffff!important; padding: 0px 6px; border-radius: 4px; font-size: 14px; font-weight: bold; display: inline-block;">Inactive</span>
@endif <br>

You can set your transaction charge and update other information from the profile menu after logging into your account.<br>
<b>Note:</b> Please first change your password after login your account. <br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent

@php
    //exit();
@endphp
