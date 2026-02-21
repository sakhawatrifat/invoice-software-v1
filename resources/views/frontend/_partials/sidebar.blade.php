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
							<a class="menu-link {{ getCurrentRouteName() == 'ticket.index' && request()->document_type == 'all' ? 'active' : '' }}" href="{{ route('ticket.index') }}?document_type=all">
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

				<!-- Essentials -->
				<div class="menu-item">
					<div class="menu-content pt-8 pb-2">
						<span class="menu-section text-uppercase fs-8 ls-1 px-3 py-2 rounded" style="background-color: #f1f1f2; color: #5e6278; display: inline-block; width: 100%;"><strong>{{ $getCurrentTranslation['essentials'] ?? 'Essentials' }}</strong></span>
					</div>
				</div>

				<div class="menu-item">
					<a class="menu-link {{ getCurrentRouteName() == 'chat.index' ? 'active' : '' }}" href="{{ route('chat.index') }}">
						<span class="menu-icon">
							<i class="fa-solid fa-comments h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['view_all_messages'] ?? 'View all messages' }}</span>
					</a>
				</div>

				@php
					$stickyNoteRoutes = ['sticky_note.index','sticky_note.datatable','sticky_note.create','sticky_note.store','sticky_note.show','sticky_note.edit','sticky_note.update','sticky_note.destroy'];
				@endphp
				@if(hasPermission('sticky_note.index') || hasPermission('sticky_note.create'))
				<div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ getActiveClass($stickyNoteRoutes, 'hover show') }}">
					<span class="menu-link">
						<span class="menu-icon">
							<i class="fa-solid fa-note-sticky h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['manage_sticky_notes'] ?? 'manage_sticky_notes' }}</span>
						<span class="menu-arrow"></span>
					</span>
					<div class="menu-sub menu-sub-accordion {{ getActiveClass($stickyNoteRoutes, 'show') }}">
						@if(hasPermission('sticky_note.index'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'sticky_note.index' ? 'active' : '' }}" href="{{ route('sticky_note.index') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['sticky_note_list'] ?? 'sticky_note_list' }}</span>
							</a>
						</div>
						@endif

						@if(hasPermission('sticky_note.create'))
						<div class="menu-item">
							<a class="menu-link {{ getCurrentRouteName() == 'sticky_note.create' ? 'active' : '' }}" href="{{ route('sticky_note.create') }}">
								<span class="menu-bullet">
									<span class="bullet bullet-dot"></span>
								</span>
								<span class="menu-title">{{ $getCurrentTranslation['create_sticky_note'] ?? 'create_sticky_note' }}</span>
							</a>
						</div>
						@endif
					</div>
				</div>
				@endif

				@if(hasPermission('email_marketing') || hasPermission('whatsapp_marketing'))
				<!-- Marketing -->
				<div class="menu-item">
					<div class="menu-content pt-8 pb-2">
						<span class="menu-section text-uppercase fs-8 ls-1 px-3 py-2 rounded" style="background-color: #f1f1f2; color: #5e6278; display: inline-block; width: 100%;"><strong>{{ $getCurrentTranslation['marketing'] ?? 'Marketing' }}</strong></span>
					</div>
				</div>
				@if(hasPermission('email_marketing'))
				<div class="menu-item">
					<a class="menu-link {{ getCurrentRouteName() == 'marketing.email.form' ? 'active' : '' }}" href="{{ route('marketing.email.form') }}">
						<span class="menu-icon">
							<i class="fa-solid fa-envelope h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['email_marketing'] ?? 'Email Marketing' }}</span>
					</a>
				</div>
				@endif
				@if(hasPermission('whatsapp_marketing'))
				<div class="menu-item">
					<a class="menu-link {{ getCurrentRouteName() == 'marketing.whatsapp.form' ? 'active' : '' }}" href="{{ route('marketing.whatsapp.form') }}">
						<span class="menu-icon">
							<i class="fa-brands fa-whatsapp h4 mb-0"></i>
						</span>
						<span class="menu-title">{{ $getCurrentTranslation['whatsapp_marketing'] ?? 'WhatsApp Marketing' }}</span>
					</a>
				</div>
				@endif
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
