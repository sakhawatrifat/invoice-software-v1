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

	$(document).on("click", ".ticket-layout-btn", function () {
		$('.r-preloader').show();
	});

	// Hide preloader when page is loaded (new tab, reload, or back)
	$(window).on('load pageshow', function (event) {
		// Hide modal and preloader
		$("#ticketLayoutModal").modal("hide");
		$('.r-preloader').hide();
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

		// Reset selects to first option
		$form.find('select').each(function() {
			$(this).val($(this).find('option:first').val()).trigger('change');
		});

		// Reset daterange picker
		$form.find('.dateRangePicker').each(function () {
			var $picker = $(this);
			$picker.removeClass('filled').addClass('empty');
			$picker.find('span').html('');
			$picker.find('.dateRangeInput').val('');
			$picker.closest('.daterange-picker-wrap').find('.clear-date-range').click();
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
				var fileName = `You selected "${fileName}"`
				reader.onload = function (e) {
					thisPrevPopup.attr('data-src', e.target.result)
					thisPrev.attr('src', e.target.result).attr('alt', fileName).show();
					thisPrevPopup.show();
					thisHref.attr('href', e.target.result);
				};
				reader.readAsDataURL(file);
			} else {
				// Reset to old image if no file is selected
				resetToOldImage($(this));
			}
		});

		// Function to reset to old image
		function resetToOldImage(input) {
			var thisPrev = input.closest('.input-item-wrap').find('.preview-img');
			const oldImage = thisPrev.attr('old-selected');
			thisPrev.attr('src', oldImage).toggle(!!oldImage);
			input.val(''); // Clear the input value
		}
	});
</script>