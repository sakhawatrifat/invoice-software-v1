<script>
    // function validateIpRequiredFields() {
    //     let isValid = true;
    //     let firstInvalid = null;

    //     $('.ip-validation-msg').slideUp(200, function () {
    //         $(this).remove();
    //     });

    //     $('[ip-required]').each(function () {
    //         let $input = $(this);
    //         let value = $input.val();
    //         let type = $input.attr('type');
    //         let name = $input.attr('name');
    //         let isEmpty = false;

    //         if (type === 'checkbox' || type === 'radio') {
    //             isEmpty = !$(`[name="${name}"]:checked`).length;
    //         } else {
    //             isEmpty = !value || value.trim() === '';
    //         }

    //         // Remove previous error
    //         //$input.removeClass('is-invalid');
    //         $input.closest('div').find('.ip-validation-msg').slideUp(200, function () {
    //             $(this).remove();
    //         });

    //         if (isEmpty) {
    //             isValid = false;

    //             if (!firstInvalid) {
    //                 firstInvalid = $input;
    //             }

    //             // Add error message with slideDown effect
    //             const $msg = $(`<span class="ip-validation-msg text-danger small mt-1" style="display:none;">${getCurrentTranslation.this_field_is_required ?? 'this_field_is_required'}</span>`);
    //             $input.closest('div').append($msg);
    //             $msg.slideDown(200);
    //             //$input.addClass('is-invalid');
    //         }
    //     });

    //     if (!isValid && firstInvalid) {
    //         $('html, body').animate({
    //             scrollTop: firstInvalid.offset().top - 120
    //         }, 500);
    //     }

    //     return isValid;
    // }

    function validateIpRequiredFields(form, isModal=0) {
        let isValid = true;
        let firstInvalid = null;

        const $form = $(form); // scope to this form

        // Remove previous validation messages inside this form only
        $form.find('.ip-validation-msg').slideUp(200, function () {
            $(this).remove();
        });

        // Loop through only inputs inside the provided form
        $form.find('[ip-required]').each(function () {
            const $input = $(this);
            const value = $input.val();
            const type = $input.attr('type');
            const name = $input.attr('name');
            let isEmpty = false;

            if (type === 'checkbox' || type === 'radio') {
                isEmpty = !$form.find(`[name="${name}"]:checked`).length;
            } else {
                isEmpty = !value || value.trim() === '';
            }

            // Remove any existing validation message near this field
            $input.closest('div').find('.ip-validation-msg').slideUp(200, function () {
                $(this).remove();
            });

            if (isEmpty) {
                isValid = false;

                if (!firstInvalid) {
                    firstInvalid = $input;
                }

                // Add error message with animation
                const $msg = $(`
                    <span class="ip-validation-msg text-danger small mt-1" style="display:none;">
                        ${getCurrentTranslation.this_field_is_required ?? 'this_field_is_required'}
                    </span>
                `);
                $input.closest('div').append($msg);
                $msg.slideDown(200);
            }
        });

        if (!isValid && firstInvalid && isModal == 0) {
            console.log(firstInvalid.attr('name'));
            $('html, body').animate({
                scrollTop: firstInvalid.parent().offset().top - 120
            }, 500);
        }

        return isValid;
    }


    // On focus: remove error message temporarily (if any)
    $(document).on('focus', '[ip-required]', function () {
        let $input = $(this);
        $input.removeClass('is-invalid');
        $input.closest('div').find('.ip-validation-msg').slideUp(200, function () {
            $(this).remove();
        });
    });

    // On blur: validate and show error if empty
    $(document).on('blur', '[ip-required]', function () {
        let $input = $(this);
        let value = $input.val();
        let type = $input.attr('type');
        let name = $input.attr('name');
        let isEmpty = false;

        if (type === 'checkbox' || type === 'radio') {
            isEmpty = !$(`[name="${name}"]:checked`).length;
        } else {
            isEmpty = !value || value.trim() === '';
        }

        let $msg = $input.closest('div').find('.ip-validation-msg');

        if (isEmpty) {
            if (!$msg.length) {
                const $newMsg = $(`<div class="ip-validation-msg text-danger small mt-1" style="display:none;">${getCurrentTranslation.this_field_is_required ?? 'this_field_is_required'}</div>`);
                $input.closest('div').append($newMsg);
                $newMsg.slideDown(200);
                //$input.addClass('is-invalid');
            }
        } else {
            $input.removeClass('is-invalid');
            $msg.slideUp(200, function () {
                $(this).remove();
            });
        }
    });

    // On input/change/click: remove error immediately if fixed (no show on empty here, only removal)
    $(document).on('input change click', '[ip-required]', function () {
        let $input = $(this);
        let value = $input.val();
        let type = $input.attr('type');
        let name = $input.attr('name');
        let isEmpty = false;

        if (type === 'checkbox' || type === 'radio') {
            isEmpty = !$(`[name="${name}"]:checked`).length;
        } else {
            isEmpty = !value || value.trim() === '';
        }

        if (!isEmpty) {
            $input.removeClass('is-invalid');
            $input.closest('div').find('.ip-validation-msg').slideUp(200, function () {
                $(this).remove();
            });
        }
    });


    $(document).ready(function() {
        if (localStorage.getItem('scrollToTopAfterReload') === 'true') {
            $('html, body').animate({
                scrollTop: $('body').offset().top - 100
            }, 500);
            localStorage.removeItem('scrollToTopAfterReload');
        }
    });


    // Submit form via AJAX
	$(document).on('click', '.form-submit-btn, .ajax-modal-form-submit-btn', function (e) {
		e.preventDefault();

        syncCKEditor5Fields();

        let isModal = $(this).hasClass('ajax-modal-form-submit-btn') || $(this).closest('.modal').length > 0;
        let $form = $(this).closest('form');
		let formData = new FormData($form[0]);

		if (!validateIpRequiredFields($form, isModal)) {
			return false;
		}

        if (!validateCKEditor5Fields()) {
			return false;
		}

		$.ajax({
			url: $form.attr('action'),
            method: $form.attr('method') || 'POST',
            data: formData,
            processData: false,  // ✅ Prevents jQuery from converting FormData into a query string
		    contentType: false,  // ✅ Tells jQuery not to set a default content type
			beforeSend: function () {
                $('.r-preloader').show();
				//console.log('Sending...');
			},
			success: function (response) {
                $('.r-preloader').hide();

				// Clear previous errors
				$('.error-message').remove();
				$('.is-invalid').removeClass('is-invalid');

				if (response.is_success === 0 && response.errors) {
					let firstField = null;

					Object.entries(response.errors).forEach(([field, messages]) => {
						// Convert dot notation to bracket notation
						const name = field
							.split('.')
							.map(part => /^\d+$/.test(part) ? `[${part}]` : part)
							.reduce((acc, part, index) => {
								if (index === 0) return part;
								return acc + (part.startsWith('[') ? part : `[${part}]`);
							}, '');

						const $input = $(`[name="${name}"]`);

						if ($input.length > 0) {
							//$input.addClass('is-invalid');

							// Get just the last field name (e.g., "email")
							const fieldLabel = field.split('.').pop().replace(/_/g, ' ');

							// Replace default Laravel error message with a shorter one
							let message = messages[0];
								// .replace(field, fieldLabel) // Replace full path with short name
								// .replace(/The\s+.+?\s+field/, `The ${fieldLabel} field`); // General fallback

							const $error = $(`
								<div class="error-message text-danger" style="font-size: 0.875rem; margin-top: 0.25rem;">
									${message}
								</div>
							`);
                            //console.log(messages[0]);
							$input.after($error);

							if (!firstField) {
								firstField = $input;
							}
						}
					});

					if (firstField && isModal == 0) {
						$('html, body').animate({
							scrollTop: firstField.offset().top - 100
						}, 600);
						firstField.focus();
					} else {
						Swal.fire({
							icon: 'warning',
							title: getCurrentTranslation.validation_warning ?? 'validation_warning',
							text: getCurrentTranslation.some_filed_missing_or_invalid ?? 'some_filed_missing_or_invalid',
                            confirmButtonText: getCurrentTranslation.ok || 'ok',
						});
					}
				} else {
					if (response.is_success === 1) {
                        if (response.is_ajax_modal !== undefined && response.is_ajax_modal == 1) {
                            const selectName = response.for_input;
                            const created = response.created_data;

                            // Build option element
                            const newOption = new Option(created.name, created.id, true, true);

                            // Find the target select by name (scoped safely)
                            const $select = $(`select[name="${selectName}"]`);

                            // Append and trigger Select2 update
                            $select.append(newOption).trigger('change');
                            
                            // Optional: close modal if it was opened via AJAX
                            if (response.is_ajax_modal == 1) {
                                $('#ajaxCreateModal').modal('hide');
                            }

                            toastr.success(response.message);
                        }else{
                            var $icon = response.icon
                            Swal.fire({
                                icon: $icon,
                                title: (getCurrentTranslation[$icon] || $icon).toUpperCase(),
                                text: response.message,
                                confirmButtonText: getCurrentTranslation.ok || 'ok',
                                allowOutsideClick: true,
                            }).then((result) => {
                                // Set scroll flag before reload
                                if (result.isConfirmed || result.isDismissed) {
                                    localStorage.setItem('scrollToTopAfterReload', 'true');
                                    location.reload();
                                }
                            });
                            // Optional: reset the form
                            //$form[0].reset();
                        }
					}else{
                        var $icon = response.icon
                        Swal.fire({
                            icon: $icon,
							title: (getCurrentTranslation[$icon] || $icon).toUpperCase(),
							text: response.message,
                            confirmButtonText: getCurrentTranslation.ok || 'ok',
							allowOutsideClick: true,
                        });
                    }
				}
			},
			error: function (xhr) {
                $('.r-preloader').hide();

                // Laravel CSRF mismatch status
                if (xhr.status === 419) { 
                    Swal.fire({
                        icon: 'error',
                        title: getCurrentTranslation.csrf_token_mismatch ?? 'csrf_token_mismatch',
                        text: getCurrentTranslation.csrf_token_mismatch_msg ?? 'csrf_token_mismatch_msg',
                        confirmButtonText: getCurrentTranslation.yes_reload_page || 'yes_reload_page'
                    }).then(() => {
                        location.reload(); // Reload after user confirms
                    });

                    return;
                }

				// Generic error fallback for 500s or connectivity issues
				Swal.fire({
					icon: 'error',
					title: getCurrentTranslation.server_error ?? 'server_error',
					text: getCurrentTranslation.something_went_wrong ?? 'something_went_wrong',
                    confirmButtonText: getCurrentTranslation.ok || 'ok'
				});
			}
		});
	});

    
    function syncCKEditor5Fields() {
        // Sync content for ALL textarea.ck-editor elements
        $('textarea.ck-editor').each(function () {
            const editor = ckeditors.get(this);
            if (editor) {
                const content = editor.getData().trim();
                this.value = content; // Sync content into textarea
            }
        });
    }

    function validateCKEditor5Fields() {
        let isValid = true;
        let firstInvalidElement = null;

        // Validate ONLY ck-editor elements with ip-required attribute
        $('.ck-editor[ip-required]').each(function () {
            const $element = $(this);
            const editor = ckeditors.get(this);

            if (editor) {
                const content = editor.getData().trim();

                if (content.length === 0) {
                    isValid = false;

                    if (!firstInvalidElement) {
                        firstInvalidElement = $element;
                    }

                    let $msg = $element.next('.ip-validation-msg');
                    if ($msg.length === 0) {
                        $msg = $(`<div class="ip-validation-msg text-danger small mt-1" style="display:none;">${getCurrentTranslation.this_field_is_required ?? 'this_field_is_required'}</div>`);
                        $msg.insertAfter($element).slideDown(200);
                    } else {
                        $msg.stop(true, true).slideDown(200);
                    }
                } else {
                    const $msg = $element.next('.ip-validation-msg');
                    if ($msg.length > 0) {
                        $msg.stop(true, true).slideUp(200, function () {
                            $(this).remove();
                        });
                    }
                }
            }
        });

        if (!isValid && firstInvalidElement) {
            $('html, body').animate({
                scrollTop: firstInvalidElement.offset().top - 100
            }, 400);
        }

        return isValid;
    }






</script>