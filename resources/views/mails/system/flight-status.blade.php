<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        *:not(a) { color: #333333 !important; }
        p { font-size: 14px !important; }
        table { border-collapse: collapse; width: 100%; margin: 1em 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.5; padding: 20px;">
<div>
    {!! $mailContent !!}
    <p style="margin-top: 20px;">Thanks,<br>{{ config('app.name') }}</p>
</div>
</body>
</html>
