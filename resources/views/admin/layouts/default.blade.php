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
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="shortcut icon" href="{{ $globalData->company_data->dark_icon_url ?? '' }}" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <link href="{{asset('/assets')}}/plugins/custom/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
        <link href="{{asset('/assets')}}/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
        <link href="{{asset('/assets')}}/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
        <link href="{{asset('/assets')}}/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="{{asset('/assets')}}/plugins/summernote/summernote.min.css" rel="stylesheet" type="text/css" />
        <!--<link href="{{asset('/assets')}}/plugins/custom/wheel-datepicker/css/mobiscroll.jquery.min.css" rel="stylesheet" type="text/css" /> -->
        <link href="{{asset('/assets')}}/plugins/custom/picker-js/picker.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link href="{{asset('/assets')}}/css/style.bundle.css?v={{ env('APP_DESIGN_VERSION') ?? time() }}" rel="stylesheet" type="text/css" />
        <link href="{{asset('/assets')}}/css/custom.css?v={{time()}}" rel="stylesheet" type="text/css" />
        <style>
            /* Force sidebar scrollbar to always be visible */
            #kt_app_sidebar_menu_wrapper {
                overflow-y: scroll !important;
                overflow-x: hidden !important;
            }
            #kt_app_sidebar_menu_wrapper::-webkit-scrollbar {
                width: 8px !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-track {
                background: transparent !important;
            }
            #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.2) !important;
                border-radius: 4px !important;
                visibility: visible !important;
                opacity: 1 !important;
                display: block !important;
                min-height: 20px !important;
            }
            /* CRITICAL: Override framework's default opacity: 0 - force visible when NOT hovered */
            #kt_app_sidebar_menu_wrapper:not(:hover)::-webkit-scrollbar-thumb,
            .hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
            #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
            .app-sidebar-wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.2) !important;
                opacity: 1 !important;
                visibility: visible !important;
                display: block !important;
            }
            #kt_app_sidebar_menu_wrapper:hover::-webkit-scrollbar-thumb,
            #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-thumb:hover {
                background: rgba(0, 0, 0, 0.3) !important;
                opacity: 1 !important;
            }
            /* Override any hover-only rules - make thumb always visible */
            .hover-scroll-overlay-y::-webkit-scrollbar-thumb,
            #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y::-webkit-scrollbar-thumb,
            .app-sidebar-wrapper.hover-scroll-overlay-y::-webkit-scrollbar-thumb {
                opacity: 1 !important;
                visibility: visible !important;
                background: rgba(0, 0, 0, 0.2) !important;
                display: block !important;
            }
            /* CRITICAL: Force thumb visible when NOT hovered - override framework default */
            #kt_app_sidebar_menu_wrapper:not(:hover)::-webkit-scrollbar-thumb,
            .hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
            #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
            .app-sidebar-wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
            .app-sidebar-wrapper:not(:hover)::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.2) !important;
                opacity: 1 !important;
                visibility: visible !important;
                display: block !important;
            }
        </style>
    </head>

    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">

        <script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-theme-mode")) { themeMode = document.documentElement.getAttribute("data-theme-mode"); } else { if ( localStorage.getItem("data-theme") !== null ) { themeMode = localStorage.getItem("data-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-theme", themeMode); }</script>

        <div class="r-preloader" style="display: none;">
            <div class="r-spinner"></div>
        </div>

        <div class="mf-img-popup">
            <div class="mf-img-popup-img-wrap">
                <img src="" alt="Popup Image">
            </div>
            <div class="mf-img-popup-rotate-btn" title="Rotate"><i class="fa-solid fa-rotate-right"></i></div>
            <div class="mf-img-popup-close-btn">
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
        </div>

        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
                <!--Header-->
                @include('admin._partials.header')

                <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                    <!--Sidebar-->
                    @include('admin._partials.sidebar')

                    <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                        <!--Page Content-->
                        @yield('content')


                        <!--Footer-->
                        @include('admin._partials.footer')
                    </div>
                </div>
            </div>
        </div>

        @auth
        @include('common.chat.partials.widget')
        @endauth

        <!--begin::Javascript-->
        <script>var hostUrl = "{{asset('/assets')}}/";</script>
        <!--begin::Global Javascript Bundle(used by all pages)-->
        <script src="{{asset('/assets')}}/plugins/global/plugins.bundle.js"></script>
        <script src="{{asset('/assets')}}/plugins/summernote/summernote.min.js"></script>
        <script src="{{asset('/assets')}}/js/scripts.bundle.js"></script>
        <!--begin::Vendors Javascript(used by this page)-->
        <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
        <script src="{{asset('/assets')}}/plugins/custom/daterangepicker/daterangepicker.js"></script>
        <script src="{{asset('/assets')}}/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/percent.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/radar.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/map.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/worldLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/continentsLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/usaLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZonesLow.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/geodata/worldTimeZoneAreasLow.js"></script>
        <script src="{{asset('/assets')}}/plugins/custom/datatables/datatables.bundle.js"></script>
        <!--begin::Custom Javascript(used by this page)-->
        <!-- <script src="{{asset('/assets')}}/plugins/custom/wheel-datepicker/js/mobiscroll.jquery.min.js"></script> -->
        <script src="{{asset('/assets')}}/plugins/custom/picker-js/picker.js"></script>
        <script src="{{asset('/assets')}}/js/widgets.bundle.js"></script>
        <script src="{{asset('/assets')}}/js/custom/widgets.js"></script>
        <script src="{{asset('/assets')}}/js/custom/apps/chat/chat.js"></script>
        <script src="{{asset('/assets')}}/js/custom/utilities/modals/upgrade-plan.js"></script>
        <script src="{{asset('/assets')}}/js/custom/utilities/modals/create-app.js"></script>
        <script src="{{asset('/assets')}}/js/custom/utilities/modals/new-target.js"></script>
        <script src="{{asset('/assets')}}/js/custom/utilities/modals/users-search.js"></script>

        <!-- Chart JS -->
        <script src="{{asset('assets')}}/js/chart.js"></script>
        <script src="{{asset('assets')}}/js/chartjs-plugin-datalabels.js"></script>

        <script src="{{asset('/assets')}}/js/custom.js?v={{time()}}"></script>

        <script>
            // Force sidebar scrollbar to always be visible
            document.addEventListener('DOMContentLoaded', function() {
                function forceSidebarScrollbar() {
                    const sidebarWrapper = document.getElementById('kt_app_sidebar_menu_wrapper');
                    if (sidebarWrapper) {
                        // Force overflow-y to scroll
                        sidebarWrapper.style.overflowY = 'scroll';
                        sidebarWrapper.style.overflowX = 'hidden';
                        
                        // Add style tag to ensure scrollbar is visible
                        if (!document.getElementById('sidebar-scrollbar-style')) {
                            const style = document.createElement('style');
                            style.id = 'sidebar-scrollbar-style';
                            style.textContent = `
                                #kt_app_sidebar_menu_wrapper {
                                    overflow-y: scroll !important;
                                    overflow-x: hidden !important;
                                }
                                #kt_app_sidebar_menu_wrapper::-webkit-scrollbar {
                                    width: 8px !important;
                                    display: block !important;
                                    visibility: visible !important;
                                    opacity: 1 !important;
                                }
                                #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-track {
                                    background: transparent !important;
                                }
                                #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-thumb {
                                    background: rgba(0, 0, 0, 0.2) !important;
                                    border-radius: 4px !important;
                                    visibility: visible !important;
                                    opacity: 1 !important;
                                    display: block !important;
                                    min-height: 20px !important;
                                }
                                /* CRITICAL: Override framework's default opacity: 0 - force visible when NOT hovered */
                                #kt_app_sidebar_menu_wrapper:not(:hover)::-webkit-scrollbar-thumb,
                                .hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
                                #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
                                .app-sidebar-wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb {
                                    background: rgba(0, 0, 0, 0.2) !important;
                                    opacity: 1 !important;
                                    visibility: visible !important;
                                    display: block !important;
                                }
                                #kt_app_sidebar_menu_wrapper:hover::-webkit-scrollbar-thumb,
                                #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-thumb:hover {
                                    background: rgba(0, 0, 0, 0.3) !important;
                                    opacity: 1 !important;
                                }
                                /* Override any hover-only rules - make thumb always visible */
                                .hover-scroll-overlay-y::-webkit-scrollbar-thumb,
                                #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y::-webkit-scrollbar-thumb,
                                .app-sidebar-wrapper.hover-scroll-overlay-y::-webkit-scrollbar-thumb {
                                    opacity: 1 !important;
                                    visibility: visible !important;
                                    background: rgba(0, 0, 0, 0.2) !important;
                                    display: block !important;
                                }
                            `;
                            document.head.appendChild(style);
                        }
                    }
                }
                
                // Run immediately
                forceSidebarScrollbar();
                
                // Run after delays to ensure framework has initialized
                setTimeout(forceSidebarScrollbar, 100);
                setTimeout(forceSidebarScrollbar, 500);
                setTimeout(forceSidebarScrollbar, 1000);
                
                // Use MutationObserver to watch for style changes and override them
                const observer = new MutationObserver(function(mutations) {
                    const sidebarWrapper = document.getElementById('kt_app_sidebar_menu_wrapper');
                    if (sidebarWrapper) {
                        // Force styles via inline style attribute
                        const style = sidebarWrapper.style;
                        if (style.overflowY !== 'scroll') {
                            style.setProperty('overflow-y', 'scroll', 'important');
                        }
                        if (style.overflowX !== 'hidden') {
                            style.setProperty('overflow-x', 'hidden', 'important');
                        }
                    }
                });
                
                // Observe the sidebar wrapper for attribute changes
                const sidebarWrapper = document.getElementById('kt_app_sidebar_menu_wrapper');
                if (sidebarWrapper) {
                    observer.observe(sidebarWrapper, {
                        attributes: true,
                        attributeFilter: ['style', 'class']
                    });
                }
            });
            
            // Also run on window load to catch late-loading styles
            window.addEventListener('load', function() {
                const style = document.createElement('style');
                style.id = 'sidebar-scrollbar-force-visible';
                style.textContent = `
                    /* Force scrollbar thumb always visible - highest priority */
                    #kt_app_sidebar_menu_wrapper::-webkit-scrollbar-thumb,
                    #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y::-webkit-scrollbar-thumb,
                    .hover-scroll-overlay-y::-webkit-scrollbar-thumb,
                    .app-sidebar-wrapper.hover-scroll-overlay-y::-webkit-scrollbar-thumb {
                        opacity: 1 !important;
                        visibility: visible !important;
                        background: rgba(0, 0, 0, 0.2) !important;
                        display: block !important;
                    }
                    #kt_app_sidebar_menu_wrapper:not(:hover)::-webkit-scrollbar-thumb,
                    #kt_app_sidebar_menu_wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
                    .hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb,
                    .app-sidebar-wrapper.hover-scroll-overlay-y:not(:hover)::-webkit-scrollbar-thumb {
                        opacity: 1 !important;
                        visibility: visible !important;
                        background: rgba(0, 0, 0, 0.2) !important;
                        display: block !important;
                    }
                `;
                if (!document.getElementById('sidebar-scrollbar-force-visible')) {
                    document.head.appendChild(style);
                }
            });
        </script>

        @include('common._partials.message')
        @include('common._partials.commonScripts')
        @include('common._partials.attendanceScripts')
        @include('common._partials.dateRangeScripts')
        @stack('script')
    </body>
</html>