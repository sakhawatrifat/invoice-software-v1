<!--begin::Header-->
<div id="kt_app_header" class="app-header">
	<div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
		<div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
			<a href="{{ route('home') }}" class="brand-logo">
				<img alt="{{ $globalData->company_data->company_name ?? 'N/A' }}" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" class="" />
			</a>
		</div>
		<div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
			<div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
				{{-- <a class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0">
					<div class="menu-item here show menu-here-bg me-0 me-lg-2">
						<span class="menu-link">
							<span class="menu-title">Home</span>
							<span class="menu-arrow d-lg-none"></span>
						</span>
					</div>
				</a> --}}
			</div>
			<div class="app-navbar flex-shrink-0">
				@if(Auth::check())
					<div class="app-navbar-item ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
						<div class="cursor-pointer symbol symbol-35px symbol-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
							@if(!empty(Auth::user()->image_url))
								<img src="{{ Auth::user()->image_url }}" alt="User Image"/>
							@else
								<img src="{{ asset('/assets') }}/images/avatar-place.png" alt="User Image"/>
							@endif
						</div>
						<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
							<div class="menu-item px-3">
								<div class="menu-content d-flex align-items-center px-3">
									<div class="symbol symbol-50px me-5">
										@if(!empty(Auth::user()->image_url))
											<img src="{{ Auth::user()->image_url }}" alt="User Image"/>
										@else
											<img src="{{ asset('/assets') }}/images/avatar-place.png" alt="User Image"/>
										@endif
									</div>
									<div class="d-flex flex-column">
										<div class="fw-bold d-flex align-items-center fs-5"> {{Auth::user()->name}}
									</div>
										<a class="fw-semibold text-muted text-hover-primary fs-7">{{Auth::user()->email}}</a>
									</div>
								</div>
							</div>
							<div class="separator my-2"></div>
							<div class="menu-item px-5">
								<a href="{{ Auth::user()->user_type == 'admin' ? route('admin.dashboard') : route('user.dashboard') }}" class="menu-link px-5">Dashboard</a>
							</div>
							<div class="menu-item px-5">
								<a href="{{ route('myProfile') }}" class="menu-link px-5">My Profile</a>
							</div>
							<div class="menu-item px-5">
								<a class="menu-link px-5" onclick="document.getElementById('logoutForm').submit()">
									<span class="menu-text">Logout</span>
								</a>

								{{-- Logout form --}}
								<form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display: none;">
									@csrf
									<button type="submit">Logout</button>
								</form> 
							</div>
						</div>
					</div>
				@else
					<div class="menu-item d-flex align-items-center me-5">
						<a href="{{ route('login') }}" class="menu-link btn btn-primary btn-sm">Login</a>
					</div>
					@if(homepageData()->is_registration_enabled == 1)
						<div class="menu-item d-flex align-items-center">
							<a href="{{ route('register') }}" class="menu-link btn btn-info btn-sm">Start Now</a>
						</div>
					@endif
				@endif

				@if(Auth::check())
					{{-- <div class="app-navbar-item d-lg-none ms-2 me-n3" title="Show header menu">
						<div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_header_menu_toggle">
							<span class="svg-icon svg-icon-1">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M13 11H3C2.4 11 2 10.6 2 10V9C2 8.4 2.4 8 3 8H13C13.6 8 14 8.4 14 9V10C14 10.6 13.6 11 13 11ZM22 5V4C22 3.4 21.6 3 21 3H3C2.4 3 2 3.4 2 4V5C2 5.6 2.4 6 3 6H21C21.6 6 22 5.6 22 5Z" fill="currentColor" />
									<path opacity="0.3" d="M21 16H3C2.4 16 2 15.6 2 15V14C2 13.4 2.4 13 3 13H21C21.6 13 22 13.4 22 14V15C22 15.6 21.6 16 21 16ZM14 20V19C14 18.4 13.6 18 13 18H3C2.4 18 2 18.4 2 19V20C2 20.6 2.4 21 3 21H13C13.6 21 14 20.6 14 20Z" fill="currentColor" />
								</svg>
							</span>
						</div>
					</div> --}}
				@endif
			</div>
		</div>
	</div>
</div>