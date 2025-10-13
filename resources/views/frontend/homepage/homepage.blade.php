@php
    $layout = 'frontend.layouts.website';
@endphp

@extends($layout)
@section('content')
<div class="d-flex flex-column flex-column-fluid">
	<!--Main Content-->
	<div id="kt_app_content" class="app-content flex-column-fluid">
		<div id="kt_app_content_container" class="">

			<section class="banner-section">
				<div class="banner-item">
					<img src="{{ $homepageData->banner_url ?? asset('assets/images/invoice-bg-1.jpg') }}" alt="{{ $homepageData->title ?? env('APP_NAME') }}">
				</div>
			</section>

			<section class="py-10 features-section">
				<div class="container">
					<div class="row justify-content-center">
						@if($homepageData->featureContent && is_array($homepageData->featureContent) && count($homepageData->featureContent))
							@foreach($homepageData->featureContent as $item)
								<div class="col-12 col-md-4 px-5 feature-item">
									<div class="row justify-content-center align-items-center">
										<div class="col-lg-4 col-md-6 col-3 image">
											@php
												$isFileExist = false;
												$imgUrl = null;
												if (isset($item['image']) && !empty($item['image'])) {
													$imgUrl = getUploadedUrl($item['image']);
													if (!empty($imgUrl)) {
														$isFileExist = true;
													}
												}
											@endphp
											<img src="{{ $isFileExist ? $imgUrl : asset('assets/images/feature-img-01.png') }}" alt="{{ $item['title'] ?? env('APP_NAME') }}" width="100%"/>
										</div>
										<div class="col-lg-8 col-md-12 text-left content">
											<h2 class="fs-1 fw-bold mb-1">{{ $item['title'] ?? 'N/A' }}</h2>
											<p class="fs-3 mb-0">{{ $item['details'] ?? 'N/A' }}</p>
										</div>
									</div>
								</div>
							@endforeach
						@else
							@for($i=1; $i <= 3; $i++)
								<div class="col-12 col-md-4 px-5 feature-item">
									<div class="row justify-content-center align-items-center">
										<div class="col-lg-4 col-md-6 col-3 image">
											<img src="{{ asset('assets/images/feature-img-01.png') }}" alt="{{ env('APP_NAME') }}" width="100%"/>
										</div>
										<div class="col-lg-8 col-md-12 text-left content">
											<h2 class="fs-1 fw-bold mb-1">Lorem ipsum dolor sit amet.</h2>
											<p class="fs-3 mb-0">Lorem ipsum dolor sit amet consectetur adipisicing elit. Ab mollitia delectus accusamus.</p>
										</div>
									</div>
								</div>
							@endfor
						@endif
					</div>
				</div>
			</section>

			<section class="description-section">
				<div class="container mt-10 pt-sm-5 mx-auto">
					<div class="row text-center">
						<div class="col mx-auto">
							<h2 class="fs-2x mb-6">{{ $homepageData['title'] ?? 'N/A' }}</h2>
							<div class="content-item-details fs-3 mb-4 pb-3 mx-lg-5">
								{!! $homepageData['description'] !!}
							</div>
						</div>
					</div>
				</div>
			</section>

			<section class="content-section">
				<div class="container pt-lg-5">
					@if($homepageData->content && is_array($homepageData->content) && count($homepageData->content))
						@foreach($homepageData->content as $item)
							<div class="content-item row align-items-center">
								<!-- Text & App Links Section -->
								<div class="col-12 col-lg-6">
									<!-- Security Info -->
									<div class="px-3 px-lg-5 text-center text-lg-start">
										<h2 class="fs-2x mb-6">{{ $item['title'] ?? 'N/A' }}</h2>
										<div class="content-item-details fs-3">
											{!! $item['details'] !!}
										</div>
									</div>
								</div>

								<!-- Right Image Section -->
								<div class="col-12 col-lg-6 d-lg-block text-center px-0">
									<div class="content-item-image">
										@php
											$isFileExist = false;
											$imgUrl = null;
											if (isset($item['image']) && !empty($item['image'])) {
												$imgUrl = getUploadedUrl($item['image']);
												if (!empty($imgUrl)) {
													$isFileExist = true;
												}
											}
										@endphp
										<img src="{{ $isFileExist ? $imgUrl : asset('assets/images/content-img-1.png') }}" alt="{{ $item['title'] ?? env('APP_NAME') }}"/>
									</div>
								</div>
							</div>
						@endforeach
					@else
						@for($i=1; $i <= 3; $i++)
							<div class="content-item row align-items-center">
							<!-- Text & App Links Section -->
							<div class="col-12 col-lg-6">
								<!-- Security Info -->
								<div class="px-3 px-lg-5 text-center text-lg-start">
									<h2 class="fs-2x mb-6">The Free Invoice Generator Upgrades Your Security</h2>
									<div class="content-item-details fs-3">
										<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Perferendis consequatur non quia libero repellendus quas quaerat voluptatibus. Dignissimos doloribus commodi sequi, ratione molestias delectus! Perspiciatis quia ad mollitia officiis ipsum?</p>
										<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Perferendis consequatur non quia libero repellendus quas quaerat voluptatibus. Dignissimos doloribus commodi sequi, ratione molestias delectus! Perspiciatis quia ad mollitia officiis ipsum?</p>
										<p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Perferendis consequatur non quia libero repellendus quas quaerat voluptatibus. Dignissimos doloribus commodi sequi, ratione molestias delectus! Perspiciatis quia ad mollitia officiis ipsum?</p>
									</div>
								</div>
							</div>

							<!-- Right Image Section -->
							<div class="col-12 col-lg-6 d-lg-block text-center px-0">
								<div class="content-item-image">
									<img src="{{ asset('assets/images/feature-img-01.png') }}" alt="{{ env('APP_NAME') }}">
								</div>
							</div>
						</div>
						@endfor
					@endif
				</div>
			</section>


		</div>
		<!--end::Content container-->
	</div>
</div>
@endsection