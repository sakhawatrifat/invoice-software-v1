<script>
    // Status toggle
    $(document).on('change', '.toggle-table-data-status', function () {
        let checkbox = $(this);
        let url = $(this).data('url') || $(this).attr('href');

        Swal.fire({
            title: getCurrentTranslation.are_you_sure ?? 'are_you_sure',
            text: getCurrentTranslation.confirm_status_change ?? 'confirm_status_change',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: getCurrentTranslation.yes_change_status ?? 'yes_change_status',
            cancelButtonText: getCurrentTranslation.cancel ?? 'cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $('.r-preloader').show();

                $.ajax({
                    url: url,
                    method: 'GET',
                    success: function (response) {
                        $('.r-preloader').hide();
                        if (response.is_success) {
                            dataTable.ajax.reload(null, false);
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                            checkbox.prop('checked', !checkbox.prop('checked')); // revert checkbox
                        }
                    },
                    error: function () {
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

                        Swal.fire(getCurrentTranslation.error ?? 'error', getCurrentTranslation.something_went_wrong ?? 'something_went_wrong', 'error');
                        checkbox.prop('checked', !checkbox.prop('checked')); // revert checkbox
                    }
                });
            } else {
                $('.r-preloader').hide();
                // revert if cancelled
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        });
    });




    // Delete data
    $(document).on('click', '.delete-table-data-btn', function (e) {
        e.preventDefault();

        let button = $(this);
        let id = button.data('id');
        let url = button.data('url');
        let title = $(this).attr('title');
        title = title ? `(${title})` : '';

        // Build optional checkbox HTML if confirm-relational-delete class exists
        let extraHtml = '';
        if (button.hasClass('confirm-relational-delete')) {
            let relDelTitle = button.attr('rel-del-title') || '';
            extraHtml = `
                <div style="margin-top:10px; text-align:left;">
                    <label style="font-weight:500; display:flex; align-items:center; justify-content: center; gap:6px; cursor:pointer; color:#ff0000; user-select: none">
                        <input type="checkbox" id="rel-delete-confirm-checkbox" name="delete_relational_data" value="1" style="accent-color:#ff0000; border:2px solid #ff0000;">
                        ${relDelTitle}
                    </label>
                </div>
            `;
        }

        Swal.fire({
            title: `${getCurrentTranslation.are_you_sure ?? 'are_you_sure'} ${title}`,
            html: `
                <p>${getCurrentTranslation.you_may_not_be_able_to_revert_this ?? 'you_may_not_be_able_to_revert_this'}</p>
                ${extraHtml}
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            confirmButtonText: getCurrentTranslation.yes_delete_it ?? 'yes_delete_it',
            cancelButtonColor: '#d33',
            cancelButtonText: getCurrentTranslation.cancel ?? 'cancel',
            didOpen: () => {
                // Disable confirm button if checkbox exists
                // if ($('#rel-delete-confirm-checkbox').length) {
                //     Swal.disableConfirmButton();
                //     $('#rel-delete-confirm-checkbox').on('change', function () {
                //         if ($(this).is(':checked')) {
                //             Swal.enableConfirmButton();
                //         } else {
                //             Swal.disableConfirmButton();
                //         }
                //     });
                // }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('.r-preloader').show();

                // Prepare data payload
                let requestData = {
                    _token: '{{ csrf_token() }}'
                };

                // Add relational delete flag if checkbox exists and is checked
                let relDeleteCheckbox = $('#rel-delete-confirm-checkbox');
                if (relDeleteCheckbox.length && relDeleteCheckbox.is(':checked')) {
                    requestData[relDeleteCheckbox.attr('name')] = relDeleteCheckbox.val();
                }

                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: requestData,
                    success: function (response) {
                        $('.r-preloader').hide();

                        if (response.is_success) {
                            dataTable.ajax.reload(null, false);
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                            if (typeof checkbox !== 'undefined') {
                                checkbox.prop('checked', !checkbox.prop('checked'));
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
                        
                        toastr.error(getCurrentTranslation.something_went_wrong ?? 'something_went_wrong');
                    }
                });
            }
        });
    });



</script>