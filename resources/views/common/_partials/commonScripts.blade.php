<div class="modal fade" id="ajaxCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>

<script>
	function openAjaxCreateModal(route, for_input, title='New Data') {
		$('.r-preloader').show();

		const modal = $('#ajaxCreateModal');
		const myModal = new bootstrap.Modal(modal[0]);

		$.ajax({
			url: route,
			method: 'GET',
			success: function (response) {
				$('.r-preloader').hide();

				if (response.is_success) {
					modal.find('.modal-body').html(response.view);
					modal.find('[name="for_input"]').val(for_input);
					modal.find('.modal-title').text(title)
					myModal.show();
				} else {
					toastr.error(response.message || 'Failed to load data.');
				}
			},
			error: function (xhr) {
				$('.r-preloader').hide();

				if (xhr.status === 419) {
					Swal.fire({
						icon: 'error',
						title: getCurrentTranslation.csrf_token_mismatch ?? 'CSRF Token Mismatch',
						text: getCurrentTranslation.csrf_token_mismatch_msg ?? 'Your session has expired. Please reload the page.',
						confirmButtonText: getCurrentTranslation.yes_reload_page ?? 'Reload Page'
					}).then(() => {
						location.reload();
					});
					return;
				}

				Swal.fire(
					getCurrentTranslation.error ?? 'Error',
					getCurrentTranslation.something_went_wrong ?? 'Something went wrong. Please try again.',
					'error'
				);
			}
		});
	}
</script>


<!-- Ticket Global Modal -->
<div class="modal fade" id="ticketLayoutModal" tabindex="-1" aria-labelledby="ticketLayoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-2 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketLayoutModalLabel">{{ $getCurrentTranslation['choose_ticket_layout'] ?? 'choose_ticket_layout' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center space-y-3 pb-10">
				<div class="row">
					<div class="col-4">
						<a href="#" class="btn btn-info w-100 ticket-layout-btn" data-layout="1">
							<i class="fa-solid fa-layer-group me-1"></i> {{ $getCurrentTranslation['layout'] ?? 'layout' }}-1
						</a>
					</div>
					<div class="col-4">
						<a href="#" class="btn btn-info w-100 ticket-layout-btn" data-layout="2">
							<i class="fa-solid fa-layer-group me-1"></i> {{ $getCurrentTranslation['layout'] ?? 'layout' }}-2
						</a>
					</div>
					<div class="col-4">
						<a href="#" class="btn btn-info w-100 ticket-layout-btn" data-layout="3">
							<i class="fa-solid fa-layer-group me-1"></i> {{ $getCurrentTranslation['layout'] ?? 'layout' }}-3
						</a>
					</div>
				</div>
            </div>
        </div>
    </div>
</div>

<script>
	$(document).on("click", ".show-ticket-btn", function () {
		var url = $(this).data("url"); // get URL from clicked button

		// set dynamic links
		$("#ticketLayoutModal .ticket-layout-btn").each(function () {
			var layout = $(this).data("layout"); // get layout number
			$(this).attr("href", url + "?layout=" + layout); // set dynamic link
		});

		// show modal
		$("#ticketLayoutModal").modal("show");
	});

	$(document).on("click", ".ticket-layout-btn", function (e) {
		// Detect if the link opens in a new tab
		const isNewTab = e.ctrlKey || e.metaKey || e.which === 2 || $(this).attr("target") === "_blank";

		if (!isNewTab) {
			// Only show preloader for same-tab navigation
			$(".r-preloader").show();
		} else {
			// Hide modal and preloader if opened in new tab
			$("#ticketLayoutModal").modal("hide");
			$(".r-preloader").hide();
		}
	});

	// Hide preloader when page is loaded (new tab, reload, or back)
	$(window).on("load pageshow", function () {
		$("#ticketLayoutModal").modal("hide");
		$(".r-preloader").hide();
	});


</script>

<script>
    // Data modify confirmation
	$(document).on('click', '.data-confirm-button', function(event) {
		event.preventDefault();

		let url = $(this).attr('href');
		let title = $(this).attr('title');
        title = title ? `(${title})` : '';

		Swal.fire({
			title: `${getCurrentTranslation.are_you_sure ?? 'are_you_sure'} ${title}`,
			text: getCurrentTranslation.you_may_not_be_able_to_revert_this ?? 'you_may_not_be_able_to_revert_this',
			icon: 'info',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			confirmButtonText: `${getCurrentTranslation.yes_confirm ?? 'yes_confirm'} ${title}`,
			cancelButtonColor: '#d33',
			cancelButtonText: getCurrentTranslation.cancel ?? 'cancel',
		}).then((result) => {
			if (result.isConfirmed) {
				window.location.href = url;
			}
		});
	});
</script>


<script>
	// Reset button click
	$('.filter-reset-btn').on('click', function (e) {
		e.preventDefault();
		var $form = $(this).closest('form.filter-data-form');

		// Reset inputs and textareas
		$form.find('input:not([type="hidden"]):not([type="button"]):not([type="submit"])').val('');
		$form.find('textarea').val('');

		// Reset selects (including multiple selects)
	    $form.find('select').each(function () {
	        if ($(this).prop('multiple')) {
	            $(this).val([]).trigger('change'); // Clear all selected options
	        } else {
	            $(this).val($(this).find('option:first').val()).trigger('change');
	        }
	    });

		// Reset daterange picker
		// Reset daterange picker
		$form.find('.dateRangePicker').each(function () {
		    var $picker = $(this);
		    var pickerInstance = $picker.data('daterangepicker');

		    if (pickerInstance) {
		        // Reset to plugin's min/max or default range instead of null
		        var start = moment().startOf('day');
		        var end = moment().endOf('day');

		        // Update internal values safely (avoids null error)
		        pickerInstance.setStartDate(start);
		        pickerInstance.setEndDate(end);

		        // Clear input + label but don't nullify moment objects
		        $picker.find('span').html($picker.data('placeholder') || (getCurrentTranslation.select_date_range ?? 'select_date_range'));
		        $picker.find('.dateRangeInput').val('');

		        // Reset UI states
		        pickerInstance.container.find('li.active, li.selected').removeClass('active selected');
		        pickerInstance.container.find('input[name="daterangepicker_start"]').val('');
		        pickerInstance.container.find('input[name="daterangepicker_end"]').val('');

		        // Optionally trigger a "cleared" flag if you use filters
		        $picker.removeClass('filled').addClass('empty').trigger('change');
		    }
		});


		// Only reload DataTable if button has 'datatable-filter' class
		if ($(this).hasClass('datatable-filter')) {
			var baseUrl = '{{ $dataTableRoute ?? '' }}'; // keep your base URL
			var formData = [];

			// Serialize all inputs
			$form.find('input, textarea').each(function() {
				var name = $(this).attr('name');
				if (name && $(this).val() !== '') {
					formData.push({ name: name, value: $(this).val() });
				}
			});

			// Serialize all selects
			$form.find('select').each(function() {
				var name = $(this).attr('name');
				if (!name) return;

				var values = $(this).val();
				if (values !== null && values !== '') {
					if (Array.isArray(values)) {
						values.forEach(function(val) {
							formData.push({ name: name + '[]', value: val });
						});
					} else {
						formData.push({ name: name, value: values });
					}
				}
			});

			// Convert to query string
			var query = $.param(formData);
			var newUrl = baseUrl + (query ? '?' + query : '');

			// Reload DataTable with updated (empty) filters
			$('#datatable').DataTable().ajax.url(newUrl).load();
		}
	});
</script>

<script>
	// Image preview with validation
	$(document).ready(function () {
		// When the file input changes
		$(document).on('change', '.image-input', function () {

			$(this).closest('.input-item-wrap').find('a.preview-image').hide();
			$(this).closest('.input-item-wrap').find('span.text-danger').remove();

			var thisPrevPopup = $(this).closest('.input-item-wrap').find('.mf-prev');
			var thisPrev = $(this).closest('.input-item-wrap').find('.preview-img');
			var thisHref = $(this).closest('.input-item-wrap').find('a.file-prev-thumb');
			var thisPdfPreview = $(this).closest('.input-item-wrap').find('.pdf-preview');
			var thisImagePreview = $(this).closest('.input-item-wrap').find('.image-preview');
			var thisPreviewContainer = $(this).closest('.input-item-wrap').find('.preview-image');
			
			// Create preview containers if they don't exist
			if (thisPreviewContainer.length === 0) {
				thisPreviewContainer = $('<div class="preview-image mt-2" style="display: none;"></div>');
				$(this).closest('.input-item-wrap').find('.file-input-box').after(thisPreviewContainer);
			}
			if (thisImagePreview.length === 0) {
				thisImagePreview = $('<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="" style="display: none;"><img src="" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;"></div>');
				thisPreviewContainer.append(thisImagePreview);
			}
			if (thisPdfPreview.length === 0) {
				thisPdfPreview = $('<div class="pdf-preview" data-src="" style="display: none;"><a href="javascript:void(0);" class="file-prev-thumb"><i class="fas fa-file-pdf fa-3x text-danger"></i></a></div>');
				thisPreviewContainer.append(thisPdfPreview);
			}
			const maxSize = $(this).attr('max-size') * 1024; // Convert KB to bytes
			let acceptType = $(this).attr('accept');
			const allowedTypes = $(this).attr('accept').split(','); // Allowed types
			const file = this.files[0]; // Get the file
			let maxMB = (maxSize / (1024 * 1024));

			if (file) {
				// Validate file type
				var fileType = file.type;
				var mainType = fileType.split('/')[0];
				var fileName = file.name.toLowerCase();
				var fileExt = '.' + fileName.split('.').pop();
				var isPdf = fileExt === '.pdf' || fileType === 'application/pdf';
				var isImage = mainType === 'image';

				console.log(fileType);

				if (!allowedTypes.includes(fileExt)) {
					Swal.fire({
						title: 'Invalid file!',
						text: `Invalid file format. Please upload ${acceptType}`,
						icon: 'warning',
						showCancelButton: false,
						confirmButtonColor: '#3085d6',
						cancelButtonColor: '#d33',
						confirmButtonText: 'Got It!'
					});
					resetToOldImage($(this));
					return;
				}

				// Validate file size
				if (maxMB > 0 && file.size > maxSize) {
					Swal.fire({
						title: 'Too large file!',
						text: `File size exceeds the maximum limit of ${maxMB}MB.`,
						icon: 'warning',
						showCancelButton: false,
						confirmButtonColor: '#3085d6',
						cancelButtonColor: '#d33',
						confirmButtonText: 'Got It!'
					});
					resetToOldImage($(this));
					return;
				}
			
				// Show the new preview
				const reader = new FileReader();
				var displayFileName = `You selected "${file.name}"`;
				reader.onload = function (e) {
					var inputElement = this;
					
					// Ensure preview container exists and is visible
					if (thisPreviewContainer.length === 0) {
						thisPreviewContainer = $('<div class="preview-image mt-2"></div>');
						$(inputElement).closest('.input-item-wrap').find('.file-input-box').after(thisPreviewContainer);
					}
					
					// Re-find preview elements in case they were just created
					thisImagePreview = $(inputElement).closest('.input-item-wrap').find('.image-preview');
					thisPdfPreview = $(inputElement).closest('.input-item-wrap').find('.pdf-preview');
					thisPrev = $(inputElement).closest('.input-item-wrap').find('.preview-img');
					
					// Ensure preview elements exist
					if (thisImagePreview.length === 0) {
						thisImagePreview = $('<div class="append-prev mf-prev hover-effect m-0 image-preview" data-src="" style="display: none;"><img src="" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain;"></div>');
						thisPreviewContainer.append(thisImagePreview);
						thisPrev = thisImagePreview.find('.preview-img');
					}
					if (thisPdfPreview.length === 0) {
						thisPdfPreview = $('<div class="pdf-preview" data-src="" style="display: none;"><a href="javascript:void(0);" class="file-prev-thumb"><i class="fas fa-file-pdf fa-3x text-danger"></i></a></div>');
						thisPreviewContainer.append(thisPdfPreview);
					}
					
					if (isPdf) {
						// Hide image preview, show PDF preview
						thisImagePreview.hide();
						thisPdfPreview.attr('data-src', e.target.result).show();
						thisPreviewContainer.show();
					} else if (isImage) {
						// Hide PDF preview, show image preview
						thisPdfPreview.hide();
						thisImagePreview.attr('data-src', e.target.result);
						
						// Find or create img tag inside image-preview
						var imgTag = thisImagePreview.find('img.preview-img');
						if (imgTag.length === 0) {
							imgTag = $('<img src="" class="preview-img ml-2" width="100" style="max-height:100px; max-width:100%; object-fit:contain; display:block;">');
							thisImagePreview.append(imgTag);
						}
						
						// Set image source and show
						imgTag.attr('src', e.target.result).attr('alt', displayFileName).css('display', 'block');
						thisImagePreview.css('display', 'inline-flex');
						thisPreviewContainer.css('display', 'block');
					} else {
						// For other file types, just set href
						if (thisHref.length > 0) {
							thisHref.attr('href', e.target.result);
						}
						thisPreviewContainer.show();
					}
				}.bind(this);
				reader.readAsDataURL(file);
			} else {
				// If no file is selected (user cancelled), restore from data-old attribute
				restoreFromDataOld($(this));
			}
		});

		// Function to restore from data-old attribute
		function restoreFromDataOld(input) {
			var inputItemWrap = input.closest('.input-item-wrap');
			var thisPreviewContainer = inputItemWrap.find('.preview-image');
			var thisImagePreview = inputItemWrap.find('.image-preview');
			var thisPdfPreview = inputItemWrap.find('.pdf-preview');
			var thisPrev = inputItemWrap.find('.preview-img');
			
			// Get the original file URL from data-old attribute (on input or preview container)
			var oldFileUrl = input.attr('data-old') || thisPreviewContainer.attr('data-old') || '';
			
			if (oldFileUrl && oldFileUrl !== '' && !oldFileUrl.startsWith('data:')) {
				// We have an existing file from server - restore it
				var fileExt = oldFileUrl.toLowerCase().split('.').pop();
				var isPdf = fileExt === 'pdf';
				var isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt);
				
				if (isImage) {
					// Restore image preview
					thisPrev.attr('src', oldFileUrl).attr('old-selected', oldFileUrl).show();
					thisImagePreview.attr('data-src', oldFileUrl).show();
					thisPdfPreview.hide();
					thisPreviewContainer.show();
				} else if (isPdf) {
					// Restore PDF preview
					thisPdfPreview.attr('data-src', oldFileUrl).attr('old-selected', oldFileUrl).show();
					thisImagePreview.hide();
					thisPreviewContainer.show();
				}
				return;
			}
			
			// Check for old image stored in old-selected attribute
			const oldImage = thisPrev.attr('old-selected');
			if (oldImage && oldImage !== '' && !oldImage.startsWith('data:')) {
				// Restore old image from server
				thisPrev.attr('src', oldImage).show();
				thisImagePreview.attr('data-src', oldImage).show();
				thisPdfPreview.hide();
				thisPreviewContainer.show();
				return;
			}
			
			// Check for old PDF stored in old-selected attribute
			const oldPdf = thisPdfPreview.attr('old-selected');
			if (oldPdf && oldPdf !== '' && !oldPdf.startsWith('data:')) {
				// Restore old PDF from server
				thisPdfPreview.attr('data-src', oldPdf).show();
				thisImagePreview.hide();
				thisPreviewContainer.show();
				return;
			}
			
			// Check for existing file from server (not a data URL)
			var existingImageSrc = thisImagePreview.attr('data-src');
			var existingPdfSrc = thisPdfPreview.attr('data-src');
			
			if (existingImageSrc && existingImageSrc !== '' && !existingImageSrc.startsWith('data:')) {
				// Keep existing image from server
				thisImagePreview.show();
				thisPdfPreview.hide();
				thisPreviewContainer.show();
				return;
			}
			
			if (existingPdfSrc && existingPdfSrc !== '' && !existingPdfSrc.startsWith('data:')) {
				// Keep existing PDF from server
				thisPdfPreview.show();
				thisImagePreview.hide();
				thisPreviewContainer.show();
				return;
			}
			
			// Only hide if it was a newly selected file (data URL)
			var currentImageSrc = thisImagePreview.attr('data-src');
			var currentPdfSrc = thisPdfPreview.attr('data-src');
			if (currentImageSrc && currentImageSrc.startsWith('data:')) {
				thisPreviewContainer.hide();
			} else if (currentPdfSrc && currentPdfSrc.startsWith('data:')) {
				thisPreviewContainer.hide();
			}
		}

		// Function to reset to old image (for validation errors)
		function resetToOldImage(input) {
			restoreFromDataOld(input);
		}
	});
</script>
