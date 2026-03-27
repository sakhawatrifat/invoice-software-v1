@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
	$getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
	<style>
		.customer-name-checkbox-wrap {
			white-space: nowrap;
		}
	</style>
	<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
		<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
			<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'dashboard' }}</a> &nbsp; -
					</li>
					@if(isset($listRoute) && !empty($listRoute))
						<li class="breadcrumb-item text-muted">
							<a href="{{ $listRoute }}?document_type=ticket" class="text-muted text-hover-primary">{{ $getCurrentTranslation['ticket_list'] ?? 'ticket_list' }}</a> &nbsp; -
						</li>
					@endif
					<li class="breadcrumb-item">{{ $getCurrentTranslation['send_whatsapp'] ?? 'Send WhatsApp' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3 flex-wrap">
				@if(isset($editData) && !empty($editData))
					<div class="btn-group">
						<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
							{{ $getCurrentTranslation['download_ticket'] ?? 'download_ticket' }}
						</button>
						<div class="dropdown-menu p-0">
							<a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=1" class="dropdown-item btn btn-sm fw-bold btn-success">{{ $getCurrentTranslation['with_price'] ?? 'with_price' }}</a>
							<a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0" class="dropdown-item btn btn-sm fw-bold btn-info">{{ $getCurrentTranslation['without_price'] ?? 'without_price' }}</a>
							@if(count($editData->passengers) > 0)
								@foreach($editData->passengers as $passenger)
									<a href="{{ route('ticket.downloadPdf', $editData->id) }}?ticket=1&withPrice=0&passenger={{ $passenger->id }}" class="dropdown-item btn btn-sm fw-bold btn-info">
										{{ $passenger->name }}
									</a>
								@endforeach
							@endif
						</div>
					</div>
				@endif

				@if (hasPermission('ticket.mail'))
					<a href="{{ route('ticket.mail', $editData->id) }}" class="btn btn-sm fw-bold btn-secondary" title="{{ $getCurrentTranslation['send_mail'] ?? 'send_mail' }}">
						<i class="fa-solid fa-envelope"></i>
						{{ $getCurrentTranslation['send_mail'] ?? 'send_mail' }}
					</a>
				@endif

				@if (hasPermission('ticket.show'))
					<a href="{{ route('ticket.show', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-pager"></i>
						{{ $getCurrentTranslation['details'] ?? 'details' }}
					</a>
				@endif

				@if (hasPermission('ticket.edit'))
					<a href="{{ route('ticket.edit', $editData->id) }}" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-pen-to-square"></i>
						{{ $getCurrentTranslation['edit'] ?? 'edit' }}
					</a>
				@endif

				@if(isset($listRoute) && !empty($listRoute))
					<a href="{{ $listRoute }}?document_type=ticket" class="btn btn-sm fw-bold btn-primary">
						<i class="fa-solid fa-arrow-left"></i>
						{{ $getCurrentTranslation['back_to_list'] ?? 'back_to_list' }}
					</a>
				@endif
			</div>
		</div>
	</div>

	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="alert alert-primary d-flex align-items-center mb-5">
				<i class="fa-brands fa-whatsapp fs-2x me-3"></i>
				<div>
					{{ $getCurrentTranslation['whatsapp_message_plain_hint'] ?? 'Message is sent as plain text via Twilio using your approved WhatsApp template. A ticket PDF is attached using the same PDF engine as email.' }}
				</div>
			</div>

			<form method="post" action="{{ route('ticket.whatsappSend', $editData->id) }}" enctype="multipart/form-data">
				@csrf
				@method('put')

				<div class="card rounded border mt-5 bg-white append-item-container">
					<div class="card-header">
						<h3 class="card-title">
							<i class="fa-brands fa-whatsapp text-success fs-2x me-2"></i>
							{{ $getCurrentTranslation['send_whatsapp'] ?? 'Send WhatsApp' }}
							<span class="badge badge-light-info ms-2">{{ $getCurrentTranslation['total_mail_sent'] ?? 'total_mail_sent' }}: {{ $editData->mail_sent_count ?? 0 }}</span>
						</h3>
					</div>
					<div class="card-body">
						<div class="row">
							<div class="col-md-12">
								<label class="form-label">{{ $getCurrentTranslation['select_passenger'] ?? 'select_passenger' }} ({{ $getCurrentTranslation['phone'] ?? 'Phone' }})</label>
								<small class="d-block text-muted mb-2">{{ $getCurrentTranslation['whatsapp_phone_required_each_selected'] ?? 'Enter a WhatsApp number (with country code) for each selected passenger.' }}</small>
								@if(count($editData->passengers) > 0)
									@foreach($editData->passengers as $key => $passenger)
										<div class="d-flex align-items-center form-item mb-4 customer-item">
											<div class="customer-name-checkbox-wrap mb-2 pe-2">
												<div class="form-check">
													<label class="form-check-label user-select-none">
														<input type="checkbox"
															class="form-check-input group-checkbox customer-name-checkbox"
															name="passengers[{{$key}}][id]"
															value="{{ $passenger->id }}"
															@if(!$loop->last) checked @endif>
														{{ $passenger->name }}
													</label>
												</div>
											</div>
											<div class="col-md-6 mb-2">
												<input class="form-control passenger-phone-input"
													type="text"
													name="passengers[{{$key}}][phone]"
													value="{{ old('passengers.'.$key.'.phone', $passenger->phone) }}"
													placeholder="+1..."
													autocomplete="tel"
													@if(!$loop->last) ip-required @endif>
											</div>
										</div>
									@endforeach
								@endif
							</div>

							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<label class="form-label mb-0">{{ $getCurrentTranslation['send_mail_individual_msg'] ?? 'send_mail_individual_msg' }}</label>
								<small class="d-block mb-2 text-warning">{{ $getCurrentTranslation['send_mail_individual_note'] ?? 'send_mail_individual_note' }}</small>
								<div class="form-check mb-4">
									<label class="form-check-label user-select-none">
										<input type="checkbox" class="form-check-input group-checkbox" name="send_individually" value="1">
										{{ $getCurrentTranslation['send_individually_label'] ?? 'send_individually_label' }}
									</label>
								</div>
							</div>

							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<div class="form-item mb-5">
									<label class="form-label mb-0">{{ $getCurrentTranslation['whatsapp_message_label'] ?? 'Message (plain text)' }}</label>
									<small class="d-block mb-2 text-muted">{{ $getCurrentTranslation['whatsapp_message_placeholders'] ?? 'Optional: {passenger_automatic_name_here} and {passenger_automatic_data_here} (plain text).' }}</small>
									<textarea class="form-control" name="whatsapp_message" rows="12" ip-required>{{ old('whatsapp_message') }}</textarea>
									@error('whatsapp_message')
										<span class="text-danger text-sm">{{ $message }}</span>
									@enderror
								</div>
							</div>

							<hr class="border-top opacity-100">

							<div class="col-md-12">
								<label class="form-label">{{ $getCurrentTranslation['ticket'] ?? 'ticket' }} PDF</label>
								<div class="d-flex align-items-center form-item mb-4 flex-wrap gap-4">
									<div class="form-check">
										<label class="form-check-label user-select-none">
											<input type="checkbox" class="form-check-input group-checkbox" name="ticket_with_price" value="1">
											{{ $getCurrentTranslation['with_price'] ?? 'with_price' }}
										</label>
									</div>
								</div>
							</div>

							@if(hasPermission('ticket.multiLayout'))
								<hr class="border-top opacity-100">
								<div class="col-md-12">
									<label class="form-label">{{ $getCurrentTranslation['select_ticket_layout'] ?? 'select_ticket_layout' }}</label>
									<div class="d-flex align-items-center form-item mb-4 gap-3 flex-wrap">
										<div class="ticket-layout-card-outer">
											<label class="ticket-layout-card mb-1">
												<input type="radio" class="hidden" name="ticket_layout" value="1" checked>
												<img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-1.png') }}" class="ticket-img" alt="">
											</label>
											<a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-1.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'layout' }} 1</b></a>
										</div>
										<div class="ticket-layout-card-outer">
											<label class="ticket-layout-card mb-1">
												<input type="radio" class="hidden" name="ticket_layout" value="2">
												<img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-2.png') }}" class="ticket-img" alt="">
											</label>
											<a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-2.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'layout' }} 2</b></a>
										</div>
										<div class="ticket-layout-card-outer">
											<label class="ticket-layout-card mb-1">
												<input type="radio" class="hidden" name="ticket_layout" value="3">
												<img width="120" src="{{ asset('assets/images/ticket-layout/ticket-layout-3.png') }}" class="ticket-img" alt="">
											</label>
											<a class="mf-prev d-inline" data-src="{{ asset('assets/images/ticket-layout/ticket-layout-3.png') }}"><b>{{ $getCurrentTranslation['layout'] ?? 'layout' }} 3</b></a>
										</div>
									</div>
								</div>
							@else
								<input type="hidden" name="ticket_layout" value="1">
							@endif
						</div>
					</div>
				</div>

				<div class="d-flex justify-content-end my-10">
					<button type="submit" class="btn btn-success form-submit-btn ajax-submit append-submit">
						<span class="indicator-label"><i class="fa-brands fa-whatsapp"></i> {{ $getCurrentTranslation['send_via_whatsapp'] ?? 'Send via WhatsApp' }}</span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
<script>
	function syncWhatsappPhoneRequired() {
		$('.customer-item').each(function () {
			var $row = $(this);
			var checked = $row.find('.customer-name-checkbox').is(':checked');
			var $phone = $row.find('.passenger-phone-input');
			if (checked) {
				$phone.attr('ip-required', '');
			} else {
				$phone.removeAttr('ip-required');
			}
		});
	}

	$(document).ready(function () {
		let maxWidth = 0;
		$(".customer-name-checkbox-wrap").each(function () {
			$(this).css("width", "auto");
			let labelWidth = $(this).find(".form-check-label").outerWidth(true);
			if (labelWidth > maxWidth) {
				maxWidth = labelWidth;
			}
		});
		$(".customer-name-checkbox-wrap").width(maxWidth + 40);
		syncWhatsappPhoneRequired();
	});

	$(document).on('change', '.customer-name-checkbox', function () {
		syncWhatsappPhoneRequired();
	});
</script>
@endpush
