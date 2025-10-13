@component('mail::message')
<style type="text/css">
    *:not(a) {
        color: #333333 !important;
    }
    p{
        font-size: 14px!important;
    }
</style>

{!! $mailContent !!}

@endcomponent
@php
    //exit();
@endphp
