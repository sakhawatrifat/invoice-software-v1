@component('mail::message')
<style type="text/css">
	*:not(a){
		color: #333333!important;
	}
</style>

Hi There,<br>
Please reset your {{config('app.name')}} account password from the link given below.

@component('mail::button', ['url' => route('password.reset.form',$content['token'])])
Reset Password
@endcomponent
or,
<br>
Use the OTP: {{$content['token']}}
<br><br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent

@php
    //exit();
@endphp
