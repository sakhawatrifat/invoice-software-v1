@component('mail::message')
<style type="text/css">
	*:not(a){
		color: #333333!important;
	}
</style>

Hi There,<br>
Please activate your {{config('app.name')}} account from the link given below.

@component('mail::button', [ 'url' => route('account.verify.confirm',['email' => $content['user']->email, 'otp' => $content['user']->otp]) ])
Activate Account
@endcomponent
or,
<br>
Use the OTP: {{ $content['user']->otp }}
<br><br>

Thanks,<br>
{{ config('app.name') }}
@endcomponent

@php
    //exit();
@endphp
