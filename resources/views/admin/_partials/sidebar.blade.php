@php
	$getCurrentTranslation = getCurrentTranslation();
@endphp

<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
	<div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
		<a href="{{route('admin.dashboard')}}">
			<img alt="{{ $globalData->company_data->company_name ?? 'N/A' }}" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" class="h-25px app-sidebar-logo-default" />
			<img alt="{{ $globalData->company_data->company_name ?? 'N/A' }}" src="{{ $globalData->company_data->dark_logo_url ?? '' }}" class="h-20px app-sidebar-logo-minimize" />
		</a>
		<div id="kt_app_sidebar_toggle" class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary body-bg h-30px w-30px position-absolute top-50 start-100 translate-middle rotate" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="app-sidebar-minimize">
			<span class="svg-icon svg-icon-2 rotate-180">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path opacity="0.5" d="M14.2657 11.4343L18.45 7.25C18.8642 6.83579 18.8642 6.16421 18.45 5.75C18.0358 5.33579 17.3642 5.33579 16.95 5.75L11.4071 11.2929C11.0166 11.6834 11.0166 12.3166 11.4071 12.7071L16.95 18.25C17.3642 18.6642 18.0358 18.6642 18.45 18.25C18.8642 17.8358 18.8642 17.1642 18.45 16.75L14.2657 12.5657C13.9533 12.2533 13.9533 11.7467 14.2657 11.4343Z" fill="currentColor" />
					<path d="M8.2657 11.4343L12.45 7.25C12.8642 6.83579 12.8642 6.16421 12.45 5.75C12.0358 5.33579 11.3642 5.33579 10.95 5.75L5.40712 11.2929C5.01659 11.6834 5.01659 12.3166 5.40712 12.7071L10.95 18.25C11.3642 18.6642 12.0358 18.6642 12.45 18.25C12.8642 17.8358 12.8642 17.1642 12.45 16.75L8.2657 12.5657C7.95328 12.2533 7.95328 11.7467 8.2657 11.4343Z" fill="currentColor" />
				</svg>
			</span>
		</div>
	</div>
	<div class="app-sidebar-menu overflow-hidden flex-column-fluid">
		<div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
			<div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
				<div class="menu-item">
					<a class="menu-link" href="{{route('admin.dashboard')}}">
						<span class="menu-icon">
							<span class="svg-icon svg-icon-2">
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<rect x="2" y="2" width="9" height="9" rx="2" fill="currentColor" />
									<rect opacity="0.3" x="13" y="2" width="9" height="9" rx="2" fill="currentColor" />
									<rect opacity="0.3" x="13" y="13" width="9" height="9" rx="2" fill="currentColor" />
									<rect opacity="0.3" x="2" y="13" width="9" height="9" rx="2" fill="currentColor" />
								</svg>
							</span>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</span>
					</a>
				</div>

				@if(Auth::user()->id == 1)
					@php
						$userRoutes = ['admin.user.index','admin.user.datatable','admin.user.create','admin.user.store','admin.user.status','admin.user.show','admin.user.edit','admin.user.update','admin.user.destory'];
					@endphp
					<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($userRoutes, 'hover show') }}">
						<span class="menu-link">
							<span class="menu-icon">
								<i class="fa-solid fa-user-gear h4 mb-0"></i>
								{{-- <i class="fa-solid fa-users h4 mb-0"></i> --}}
							</span>
							<span class="menu-title">{{ $getCurrentTranslation['manage_users'] ?? 'manage_users' }}</span>
							<span class="menu-arrow"></span>
						</span>
						<div class="menu-sub menu-sub-accordion {{ getActiveClass($userRoutes, 'show') }}">
							<div class="menu-item">
								<a class="menu-link {{ getCurrentRouteName() == 'admin.user.index' ? 'active' : '' }}" href="{{ route('admin.user.index') }}">
									<span class="menu-bullet">
										<span class="bullet bullet-dot"></span>
									</span>
									<span class="menu-title">{{ $getCurrentTranslation['user_list'] ?? 'user_list' }}</span>
								</a>
							</div>
							<div class="menu-item">
								<a class="menu-link {{ getCurrentRouteName() == 'admin.user.create' ? 'active' : '' }}" href="{{ route('admin.user.create') }}">
									<span class="menu-bullet">
										<span class="bullet bullet-dot"></span>
									</span>
									<span class="menu-title">{{ $getCurrentTranslation['create_user'] ?? 'create_user' }}</span>
								</a>
							</div>
						</div>
					</div>
				@endif

				@php
					$staffRoutes = ['staff.index','staff.datatable','staff.create','staff.store','staff.status','staff.show','staff.edit','staff.update','staff.destory'];
				@endphp
				@if(hasPermission('staff.index') || hasPermission('staff.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($staffRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-users h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_staffs'] ?? 'manage_staffs' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($staffRoutes, 'show') }}">
						@if(hasPermission('staff.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'staff.index' ? 'active' : '' }}" href="{{ route('staff.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['staff_list'] ?? 'staff_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('staff.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'staff.create' ? 'active' : '' }}" href="{{ route('staff.create') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_staff'] ?? 'create_staff' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif

				@php
					$airlineRoutes = ['admin.airline.index','admin.airline.datatable','admin.airline.create','admin.airline.store','admin.airline.status','admin.airline.show','admin.airline.edit','admin.airline.update','admin.airline.destory'];
				@endphp
				@if(hasPermission('airline.index') || hasPermission('airline.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($airlineRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-plane h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_airlines'] ?? 'manage_airlines' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($airlineRoutes, 'show') }}">
						@if(hasPermission('airline.index'))
							<div class="menu-item">
								<a class="menu-link {{ getCurrentRouteName() == 'admin.airline.index' ? 'active' : '' }}" href="{{ route('admin.airline.index') }}">
									<span class="menu-bullet">
										<span class="bullet bullet-dot"></span>
									</span>
									<span class="menu-title">{{ $getCurrentTranslation['airline_list'] ?? 'airline_list' }}</span>
								</a>
							</div>
						@endif

						@if(hasPermission('airline.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'admin.airline.create' ? 'active' : '' }}" href="{{ route('admin.airline.create') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_airline'] ?? 'create_airline' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif

				@php
					$ticketRoutes = ['ticket.index','ticket.datatable','ticket.create','ticket.store','ticket.status','ticket.show','ticket.edit','ticket.update','ticket.destory'];
				@endphp
				@if(hasPermission('ticket.index') || hasPermission('ticket.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($ticketRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-ticket h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_tickets'] ?? 'manage_tickets' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($ticketRoutes, 'show') }}">
						@if(hasPermission('ticket.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.index' && request()->document_type == 'ticket&invoice' ? 'active' : '' }}" href="{{ route('ticket.index') }}?document_type=ticket-invoice">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['ticket_and_invoice_list'] ?? 'ticket_and_invoice_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.index' && request()->document_type == 'ticket' ? 'active' : '' }}" href="{{ route('ticket.index') }}?document_type=ticket">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['ticket_list'] ?? 'ticket_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.index' && request()->document_type == 'invoice' ? 'active' : '' }}" href="{{ route('ticket.index') }}?document_type=invoice">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['invoice_list'] ?? 'invoice_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.create' && request()->document_type=='ticket' ? 'active' : '' }}" href="{{ route('ticket.create') }}?document_type=ticket">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_ticket'] ?? 'create_ticket' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.create' && request()->document_type=='invoice' ? 'active' : '' }}" href="{{ route('ticket.create') }}?document_type=invoice">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_invoice'] ?? 'create_invoice' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.index' && request()->document_type == 'quotation' ? 'active' : '' }}" href="{{ route('ticket.index') }}?document_type=quotation">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['quotation_list'] ?? 'quotation_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.create' && request()->document_type=='quotation' ? 'active' : '' }}" href="{{ route('ticket.create') }}?document_type=quotation">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_quotation'] ?? 'create_quotation' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('ticket.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.index' && request()->data_for == 'agent' && request()->document_type == 'all' ? 'active' : '' }}" href="{{ route('ticket.index') }}?data_for=agent&document_type=all">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['agent_documents'] ?? 'agent_documents' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif

				@php
					$reminderRoutes = ['ticket.reminder.index', 'ticket.reminder.datatable', 'ticket.reminder.form'];
				@endphp
				@if(hasPermission('ticket.reminder'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($reminderRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-bell h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['ticket_reminder'] ?? 'ticket_reminder' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($reminderRoutes, 'show') }}">
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.reminder.index' ? 'active' : '' }}" href="{{ route('ticket.reminder.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['passenger_list'] ?? 'passenger_list' }}</span>
							</a>
						</div>
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.reminder.form' ? 'active' : '' }}" href="{{ route('ticket.reminder.form') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['reminder_informations'] ?? 'reminder_informations' }}</span>
							</a>
						</div>
					</div>
				</div>
				@endif

				@php
					$ticketRoutes = ['hotel.invoice.index','hotel.invoice.datatable','hotel.invoice.create','hotel.invoice.store','hotel.invoice.status','hotel.invoice.show','hotel.invoice.edit','hotel.invoice.update','hotel.invoice.destory'];
				@endphp
				@if(hasPermission('hotel.invoice.index') || hasPermission('hotel.invoice.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($ticketRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-hotel h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_hotel_invoice'] ?? 'manage_hotel_invoice' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($ticketRoutes, 'show') }}">
						@if(hasPermission('hotel.invoice.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'hotel.invoice.index' && request()->document_type == 'invoice' ? 'active' : '' }}" href="{{ route('hotel.invoice.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['hotel_invoice_list'] ?? 'hotel_invoice_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('hotel.invoice.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'hotel.invoice.create' && request()->document_type=='ticket' ? 'active' : '' }}" href="{{ route('hotel.invoice.create') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_hotel_invoice'] ?? 'create_hotel_invoice' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif

				@php
					$ticketRoutes = ['payment.index','payment.datatable','payment.create','payment.store','payment.status','payment.show','payment.edit','payment.update','payment.destory'];
				@endphp
				@if(hasPermission('payment.index') || hasPermission('payment.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($ticketRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fas fa-money-check-alt h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_payment'] ?? 'manage_payment' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($ticketRoutes, 'show') }}">
						@if(hasPermission('payment.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'payment.index' ? 'active' : '' }}" href="{{ route('payment.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['payment_list'] ?? 'payment_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('payment.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'payment.create' && request()->document_type=='ticket' ? 'active' : '' }}" href="{{ route('payment.create') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_payment'] ?? 'create_payment' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif



				@php
					$paymentSetupRoutes = [
						'introductionSource.index', 'introductionSource.create', 'introductionSource.edit',
						'issuedSupplier.index', 'issuedSupplier.create', 'issuedSupplier.edit',
						'issuedBy.index', 'issuedBy.create', 'issuedBy.edit',
						'transferTo.index', 'transferTo.create', 'transferTo.edit',
						'paymentMethod.index', 'paymentMethod.create', 'paymentMethod.edit',
						'issuedCardType.index', 'issuedCardType.create', 'issuedCardType.edit',
						'cardOwner.index', 'cardOwner.create', 'cardOwner.edit',
					];
				@endphp
				@if(hasPermission('introductionSource') || hasPermission('introductionSource') || hasPermission('issuedSupplier') || hasPermission('issuedBy') || hasPermission('transferTo') || hasPermission('paymentMethod') || hasPermission('issuedCardType') || hasPermission('cardOwner'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($paymentSetupRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fas fa-toolbox h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_payment_setup'] ?? 'manage_payment_setup' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($paymentSetupRoutes, 'show') }}">
						@if(hasPermission('introductionSource'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'introductionSource') ? 'active' : '' }}" href="{{ route('introductionSource.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['introduction_source'] ?? 'introduction_source' }}</span>
							</a>
						</div>
						@endif
						

						@if(hasPermission('issuedSupplier'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'issuedSupplier') ? 'active' : '' }}" href="{{ route('issuedSupplier.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['issued_supplier'] ?? 'issued_supplier' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('issuedBy'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'issuedBy') ? 'active' : '' }}" href="{{ route('issuedBy.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['issued_by'] ?? 'issued_by' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('transferTo'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'transferTo') ? 'active' : '' }}" href="{{ route('transferTo.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['transfer_to'] ?? 'transfer_to' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('paymentMethod'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'paymentMethod') ? 'active' : '' }}" href="{{ route('paymentMethod.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['payment_method'] ?? 'payment_method' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('issuedCardType'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'issuedCardType') ? 'active' : '' }}" href="{{ route('issuedCardType.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['issued_card_type'] ?? 'issued_card_type' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('cardOwner'))
						<div class="menu-item">
							<a class="menu-link {{ Str::contains(getCurrentRouteName(), 'cardOwner') ? 'active' : '' }}" href="{{ route('cardOwner.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['card_owner'] ?? 'card_owner' }}</span>
							</a>
						</div>
						@endif

					</div>
				</div>
				@endif

				@php
					$reportRoutes = ['admin.profitLossReport'];
				@endphp
				@if(hasPermission('admin.profitLossReport'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($reportRoutes, 'hover show') }} ">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fas fa-book h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_reports'] ?? 'manage_reports' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($reportRoutes, 'show') }}">
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'admin.profitLossReport' ? 'active' : '' }}" href="{{ route('admin.profitLossReport') }}?invoice_date_range={{ getDateRange(6, 'Previous') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['profit_loss_report'] ?? 'profit_loss_report' }}</span>
							</a>
						</div>
					</div>
				</div>
				@endif

				@php
					$homepageRoutes = ['admin.homepage.index','admin.homepage.datatable','admin.homepage.create','admin.homepage.store','admin.homepage.status','admin.homepage.show','admin.homepage.edit','admin.homepage.update','admin.homepage.destory'];
				@endphp
				@if(hasPermission('homepage.edit'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($homepageRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-house h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_homepage'] ?? 'manage_homepage' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($homepageRoutes, 'show') }}">
						{{-- <div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'admin.homepage.index' ? 'active' : '' }}" href="{{ route('admin.homepage.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['homepage_list'] ?? 'homepage_list' }}</span>
							</a>
						</div> --}}
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'admin.homepage.edit' ? 'active' : '' }}" href="{{ route('admin.homepage.edit', 1) }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['edit_homepage'] ?? 'edit_homepage' }}</span>
							</a>
						</div>
					</div>
				</div>
				@endif


				@php
					$languageRoutes = ['admin.language.index','admin.language.datatable','admin.language.create','admin.language.store','admin.language.status','admin.language.show','admin.language.edit','admin.language.update','admin.language.destory', 'admin.language.translate.form'];
				@endphp
				@if(hasPermission('language.index') || hasPermission('language.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($languageRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-language h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_language'] ?? 'manage_language' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($languageRoutes, 'show') }}">
						@if(hasPermission('language.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'admin.language.index' ? 'active' : '' }}" href="{{ route('admin.language.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['language_list'] ?? 'language_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('language.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'admin.language.create' ? 'active' : '' }}" href="{{ route('admin.language.create') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_language'] ?? 'create_language' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif

			</div>
		</div>
	</div>

	@if(env('UNDER_DEVELOPMENT') == true)
		<div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
			<a href="https://preview.keenthemes.com/html/metronic/docs" target="_blank" class="btn btn-flex flex-center btn-custom btn-primary overflow-hidden text-nowrap px-0 h-40px w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click" title="200+ in-house components and 3rd-party plugins">
				<span class="btn-label">Docs & Components</span>
				<span class="svg-icon btn-icon svg-icon-2 m-0">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path opacity="0.3" d="M19 22H5C4.4 22 4 21.6 4 21V3C4 2.4 4.4 2 5 2H14L20 8V21C20 21.6 19.6 22 19 22ZM12.5 18C12.5 17.4 12.6 17.5 12 17.5H8.5C7.9 17.5 8 17.4 8 18C8 18.6 7.9 18.5 8.5 18.5L12 18C12.6 18 12.5 18.6 12.5 18ZM16.5 13C16.5 12.4 16.6 12.5 16 12.5H8.5C7.9 12.5 8 12.4 8 13C8 13.6 7.9 13.5 8.5 13.5H15.5C16.1 13.5 16.5 13.6 16.5 13ZM12.5 8C12.5 7.4 12.6 7.5 12 7.5H8C7.4 7.5 7.5 7.4 7.5 8C7.5 8.6 7.4 8.5 8 8.5H12C12.6 8.5 12.5 8.6 12.5 8Z" fill="currentColor" />
						<rect x="7" y="17" width="6" height="2" rx="1" fill="currentColor" />
						<rect x="7" y="12" width="10" height="2" rx="1" fill="currentColor" />
						<rect x="7" y="7" width="6" height="2" rx="1" fill="currentColor" />
						<path d="M15 8H20L14 2V7C14 7.6 14.4 8 15 8Z" fill="currentColor" />
					</svg>
				</span>
			</a>
		</div>
	@endif
</div>
