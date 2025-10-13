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




    // Delete profile
    $(document).on('click', '.delete-table-data-btn', function (e) {
        e.preventDefault();

        let button = $(this);
        let id = button.data('id');
        let url = button.data('url');
        let title = $(this).attr('title');
        title = title ? `(${title})` : '';

        Swal.fire({
            title: `${getCurrentTranslation.are_you_sure ?? 'are_you_sure'} ${title}`,
			text: getCurrentTranslation.you_may_not_be_able_to_revert_this ?? 'you_may_not_be_able_to_revert_this',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            confirmButtonText: getCurrentTranslation.yes_delete_it ?? 'yes_delete_it',
            cancelButtonColor: '#d33',
            cancelButtonText: getCurrentTranslation.cancel ?? 'cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                $('.r-preloader').show();

                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
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