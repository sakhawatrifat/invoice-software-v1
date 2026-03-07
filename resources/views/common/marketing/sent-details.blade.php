@php
    $layout = Auth::user()->user_type == 'admin' ? 'admin.layouts.default' : 'frontend.layouts.default';
    $getCurrentTranslation = getCurrentTranslation();
@endphp

@extends($layout)
@section('content')

<style>
    .editor-address * { margin: 0; }
</style>

<div class="d-flex flex-column flex-column-fluid">
	<!--Toolbar-->
	<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
		<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
			<div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
				<h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0"></h1>
				<ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
					<li class="breadcrumb-item text-muted">
						<a href="{{ route((Auth::user()->user_type == 'admin') ? 'admin.dashboard' : 'user.dashboard') }}" class="text-muted text-hover-primary">{{ $getCurrentTranslation['dashboard'] ?? 'Dashboard' }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item text-muted">
						<a href="{{ $listRoute }}" class="text-muted text-hover-primary">{{ $listLabel }}</a> &nbsp; -
					</li>
					<li class="breadcrumb-item">{{ $detailsTitle ?? '' }}</li>
				</ul>
			</div>
			<div class="d-flex align-items-center gap-2 gap-lg-3">
				<a href="{{ $listRoute }}" class="btn btn-sm fw-bold btn-primary">
					<i class="fa-solid fa-arrow-left"></i>
					{{ $getCurrentTranslation['back_to_list'] ?? 'Back to list' }}
				</a>
			</div>
		</div>
	</div>

	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="app-container container-fluid">
			<div class="card rounded border mb-4 shadow-sm">
				<div class="card-header">
					<h5 class="card-title">{{ $detailsTitle ?? '' }}</h5>
				</div>
				<div class="card-body">
					<div class="container-fluid py-3">
						<div class="row">
							<div class="col-12">
								<!-- Basic info -->
								<div class="card mb-4">
									<div class="card-header align-items-center bg-primary text-white">
										<h5 class="mb-0 text-light">{{ $getCurrentTranslation['basic_informations'] ?? 'Basic Informations' }}</h5>
									</div>
									<div class="card-body">
										<div class="row">
											<div class="col-md-6 mb-3">
												<strong>{{ $getCurrentTranslation['subject'] ?? 'Subject' }}:</strong>
												<p>{{ $editData->subject ?? '—' }}</p>
											</div>
											<div class="col-md-3 mb-3">
												<strong>{{ $getCurrentTranslation['recipients_count'] ?? 'Recipients' }}:</strong>
												<p>{{ is_array($editData->customers) ? count($editData->customers) : 0 }}</p>
											</div>
											<div class="col-md-3 mb-3">
												<strong>{{ $getCurrentTranslation['sent_date_time'] ?? 'Sent Date & Time' }}:</strong>
												<p>{{ ($editData->sent_date_time ?? $editData->created_at) ? \Carbon\Carbon::parse($editData->sent_date_time ?? $editData->created_at)->format('Y-m-d H:i') : '—' }}</p>
											</div>
											<div class="col-md-3 mb-3">
												<strong>{{ $getCurrentTranslation['created_by'] ?? 'Created By' }}:</strong>
												<p>{{ $editData->creator?->name ?? '—' }}</p>
											</div>
										</div>
									</div>
								</div>

								<!-- Message content -->
								<div class="card mb-4">
									<div class="card-header align-items-center bg-info text-white">
										<h5 class="mb-0 text-light">{{ $getCurrentTranslation['message_content'] ?? 'Message Content' }}</h5>
									</div>
									<div class="card-body">
										<div class="editor-address">{!! $editData->content ?? '—' !!}</div>
									</div>
								</div>

								<!-- Marketing document -->
								@if($editData->document_path && $editData->document_url)
								<div class="card mb-4">
									<div class="card-header align-items-center bg-warning text-dark">
										<h5 class="mb-0">{{ $getCurrentTranslation['marketing_document'] ?? 'Marketing Document' }}</h5>
									</div>
									<div class="card-body">
										@php
											$fileUrl = $editData->document_url;
											$extension = $editData->document_extension;
											$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
											$isImage = in_array($extension, $imageExtensions);
											$isPdf = $extension === 'pdf';
										@endphp
										<div class="row">
											<div class="col-md-4">
												<div class="card border h-100">
													<div class="card-body">
														<h6 class="card-title">{{ $editData->document_name ?? ($getCurrentTranslation['document'] ?? 'Document') }}</h6>
														@if($isImage)
															<div class="mt-2">
																<div class="append-prev mf-prev hover-effect m-0" data-src="{{ $fileUrl }}">
																	<img src="{{ $fileUrl }}" alt="Document" style="max-height:200px; max-width:100%; object-fit:contain; border-radius: 4px;">
																</div>
															</div>
														@elseif($isPdf)
															<div class="mt-2 text-center">
																<div class="append-prev mf-prev hover-effect m-0" data-src="{{ $fileUrl }}">
																	<a href="javascript:void(0);" class="btn btn-sm btn-danger">
																		<i class="fas fa-file-pdf fa-2x"></i>
																		<br>
																		<small>{{ $getCurrentTranslation['view_pdf'] ?? 'View PDF' }}</small>
																	</a>
																</div>
															</div>
														@else
															<div class="mt-2 text-center">
																<a href="{{ $fileUrl }}" target="_blank" class="btn btn-sm btn-secondary">
																	<i class="fas fa-file-alt fa-2x"></i>
																	<br>
																	<small>{{ $getCurrentTranslation['view_file'] ?? 'View file' }}</small>
																</a>
															</div>
														@endif
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								@else
								<div class="card mb-4">
									<div class="card-header align-items-center bg-secondary text-white">
										<h5 class="mb-0">{{ $getCurrentTranslation['marketing_document'] ?? 'Marketing Document' }}</h5>
									</div>
									<div class="card-body">
										<p class="text-muted mb-0">{{ $getCurrentTranslation['no_document_attached'] ?? 'No document attached.' }}</p>
									</div>
								</div>
								@endif

								<!-- Customers / Recipients -->
								<div class="card mb-4">
									<div class="card-header align-items-center bg-dark">
										<h5 class="mb-0 text-light">{{ $getCurrentTranslation['recipients'] ?? 'Recipients' }} ({{ is_array($editData->customers) ? count($editData->customers) : 0 }})</h5>
									</div>
									<div class="card-body p-0">
										@if($editData->customers && count($editData->customers) > 0)
										<div class="table-responsive">
											<table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3 mb-0">
												<thead>
													<tr class="fw-bold text-muted">
														<th>#</th>
														<th>{{ $getCurrentTranslation['name'] ?? 'Name' }}</th>
														<th>{{ $getCurrentTranslation['email_label'] ?? 'Email' }}</th>
														<th>{{ $getCurrentTranslation['phone_label'] ?? 'Phone' }}</th>
														@if(!empty($editData->customers[0]['pax_type'] ?? null) || !empty($editData->customers[0]['nationality'] ?? null))
														<th>{{ $getCurrentTranslation['pax_type'] ?? 'Type' }}</th>
														<th>{{ $getCurrentTranslation['nationality'] ?? 'Nationality' }}</th>
														@endif
													</tr>
												</thead>
												<tbody>
													@foreach($editData->customers as $idx => $c)
													<tr>
														<td>{{ $idx + 1 }}</td>
														<td>{{ $c['name'] ?? '—' }}</td>
														<td>{{ $c['email'] ?? '—' }}</td>
														<td>{{ $c['phone'] ?? '—' }}</td>
														@if(!empty($editData->customers[0]['pax_type'] ?? null) || !empty($editData->customers[0]['nationality'] ?? null))
														<td>{{ $c['pax_type'] ?? '—' }}</td>
														<td>{{ $c['nationality'] ?? '—' }}</td>
														@endif
													</tr>
													@endforeach
												</tbody>
											</table>
										</div>
										@else
										<p class="p-4 text-muted mb-0">{{ $getCurrentTranslation['no_recipients'] ?? 'No recipients data.' }}</p>
										@endif
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@push('script')
@include('common._partials.appendJs')
@include('common._partials.formScripts')
@endpush
