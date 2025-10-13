<!DOCTYPE html>
<html lang="en">
<head>
    <base href="{{url('/')}}"/>
    <title>{{config('app.name')}}</title>
    <meta charset="utf-8" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="" />
    <meta property="og:url" content="" />
    <meta property="og:site_name" content="" />
    <link rel="canonical" href="" />
    <link rel="shortcut icon" href="{{ $globalData->company_data->dark_icon_url ?? '' }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{asset('/assets')}}/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="{{asset('/assets')}}/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <link href="{{asset('/assets')}}/css/custom.css?v={{time()}}" rel="stylesheet" type="text/css" />
</head>
<body id="kt_body" class="app-blank app-blank bgi-size-cover bgi-position-center bgi-no-repeat">
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-theme-mode");
            } else {
                if (localStorage.getItem("data-theme") !== null) {
                    themeMode = localStorage.getItem("data-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-theme", themeMode);
        }
    </script>
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <style>
            body {
                background-image: url('{{asset('/assets')}}/media/auth/bg4.jpg');
            }
            [data-theme="dark"] body {
                background-image: url('{{asset('/assets')}}/media/auth/bg4-dark.jpg');
            }
        </style>
        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <style>
                body {
                    background-image: url('{{asset('/assets')}}/media/auth/bg4.jpg');
                }
                [data-theme="dark"] body {
                    background-image: url('{{asset('/assets')}}/media/auth/bg4-dark.jpg');
                }
            </style>
            
            @yield('content')
        </div>
    </div>
    <script>var hostUrl = "{{asset('/assets')}}/";</script>
    <script src="{{asset('/assets')}}/plugins/global/plugins.bundle.js"></script>
    <script src="{{asset('/assets')}}/js/scripts.bundle.js"></script>
    <script src="{{asset('/assets')}}/js/custom/authentication/sign-in/general.js"></script>
    <script src="{{asset('/assets')}}/js/custom.js?v={{time()}}"></script>
</body>
</html>
