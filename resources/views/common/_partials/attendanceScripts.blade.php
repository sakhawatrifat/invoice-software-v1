<script>
(function() {
    'use strict';

    // Get CSRF token
    function getCsrfToken() {
        const metaToken = $('meta[name="csrf-token"]').attr('content');
        return metaToken || '{{ csrf_token() }}';
    }

    let attendanceStatus = {
        isCheckedIn: false,
        isPaused: false,
        checkInTime: null,
        totalWorkMinutes: 0,
        totalPauseMinutes: 0,
        currentPauseStart: null,
        previousTotalHours: 0,
        forgotClockOut: false,
        fixedNetWorkMinutes: null, // Store net work time when paused
        lastStatusFetchedAt: null, // When we last received server status (for live tick)
        timerInterval: null,
        pauseTimerInterval: null,
        modalClockInterval: null // Real-time clock for modal
    };

    const dailyWorkTimeHours = {{ env('DAILY_WORK_TIME', 8) }};
    const dailyWorkTimeMinutes = dailyWorkTimeHours * 60;

    // Format time helper (HH:MM:SS)
    function formatTime(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = Math.floor(minutes % 60);
        const secs = Math.floor((minutes % 1) * 60);
        return String(hours).padStart(2, '0') + ':' + 
               String(mins).padStart(2, '0') + ':' + 
               String(secs).padStart(2, '0');
    }

    // Format hours and minutes (Xh Xm)
    function formatHoursMinutes(minutes) {
        const hours = Math.floor(minutes / 60);
        const mins = Math.floor(minutes % 60);
        if (hours > 0 && mins > 0) {
            return hours + 'h ' + mins + 'm';
        } else if (hours > 0) {
            return hours + 'h';
        } else if (mins > 0) {
            return mins + 'm';
        } else {
            return '0m';
        }
    }

    // Update UI based on status
    function updateUI() {
        const checkInBtn = $('#btn-check-in');
        const checkOutBtn = $('#btn-check-out');
        const pauseBtn = $('#btn-pause');
        const resumeBtn = $('#btn-resume');
        const timerDisplay = $('#attendance-timer');
        const controls = $('#attendance-controls');

        if (attendanceStatus.isCheckedIn) {
            checkInBtn.hide();
            checkOutBtn.show();
            timerDisplay.show();
            controls.show();

            if (attendanceStatus.isPaused) {
                pauseBtn.hide();
                resumeBtn.show();
            } else {
                pauseBtn.show();
                resumeBtn.hide();
            }
        } else {
            checkInBtn.show();
            checkOutBtn.hide();
            pauseBtn.hide();
            resumeBtn.hide();
            timerDisplay.hide();
            controls.show();
        }
    }

    // Compute current work/break totals (shared by header timer and checkout modal)
    function getWorkTimeValues() {
        if (!attendanceStatus.isCheckedIn) {
            return null;
        }
        const now = Date.now();
        let currentSessionMinutes;
        let totalPauseMinutes;

        if (attendanceStatus.isPaused && attendanceStatus.currentPauseStart) {
            const baseNet = (parseFloat(attendanceStatus.totalWorkMinutes) || 0) - (parseFloat(attendanceStatus.totalPauseMinutes) || 0);
            currentSessionMinutes = Math.max(0, baseNet);
            const pauseStart = new Date(attendanceStatus.currentPauseStart).getTime();
            totalPauseMinutes = (attendanceStatus.totalPauseMinutes || 0) + (now - pauseStart) / 60000;
        } else {
            const baseWork = parseFloat(attendanceStatus.totalWorkMinutes) || 0;
            const basePause = parseFloat(attendanceStatus.totalPauseMinutes) || 0;
            const lastFetch = attendanceStatus.lastStatusFetchedAt || now;
            const elapsedMinutes = (now - lastFetch) / 60000;
            currentSessionMinutes = Math.max(0, (baseWork - basePause) + elapsedMinutes);
            totalPauseMinutes = basePause;
        }

        const previousTotalHours = parseFloat(attendanceStatus.previousTotalHours) || 0;
        const previousTotalMinutes = previousTotalHours * 60;
        const totalAccumulatedMinutes = previousTotalMinutes + currentSessionMinutes;

        return {
            currentSessionMinutes: currentSessionMinutes,
            totalPauseMinutes: totalPauseMinutes,
            previousTotalHours: previousTotalHours,
            previousTotalMinutes: previousTotalMinutes,
            totalAccumulatedMinutes: totalAccumulatedMinutes
        };
    }

    // Update timer display and modal work time (real-time)
    function updateTimer() {
        const v = getWorkTimeValues();
        if (!v) return;

        let timerText;
        if (v.previousTotalHours > 0) {
            timerText = '<strong>' + formatHoursMinutes(v.totalAccumulatedMinutes) + '</strong>';
            timerText += ' <small class="text-muted">(Session: ' + formatTime(v.currentSessionMinutes) + ')</small>';
        } else {
            timerText = formatTime(v.currentSessionMinutes);
        }
        $('#timer-display').html(timerText);
        $('#break-display').text('Break: ' + formatTime(v.totalPauseMinutes));

        // Real-time update for checkout modal when visible
        if ($('#attendanceModal').hasClass('show') && $('#confirm-attendance-action').data('action') === 'check-out') {
            let workTimeText = formatTime(v.currentSessionMinutes) + ' <small class="text-muted">(Session: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            if (v.previousTotalHours > 0) {
                workTimeText += '<br><strong>Total Today: ' + formatHoursMinutes(v.totalAccumulatedMinutes) + '</strong> <small class="text-muted">(Previous: ' + formatHoursMinutes(v.previousTotalMinutes) + ' + Current: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            }
            $('#modal-work-time').html(workTimeText);
            $('#modal-break-time').text(formatTime(v.totalPauseMinutes));
        }
    }

    // Start timer
    function startTimer() {
        if (attendanceStatus.timerInterval) {
            clearInterval(attendanceStatus.timerInterval);
        }
        attendanceStatus.timerInterval = setInterval(updateTimer, 1000);
        updateTimer();
    }

    // Stop timer
    function stopTimer() {
        if (attendanceStatus.timerInterval) {
            clearInterval(attendanceStatus.timerInterval);
            attendanceStatus.timerInterval = null;
        }
    }

    // Fetch current status (background update - no preloader to avoid disturbing user)
    function fetchStatus() {
        $.ajax({
            url: '{{ route("attendance.status") }}',
            method: 'GET',
            success: function(response) {
                attendanceStatus.isCheckedIn = response.is_checked_in;
                attendanceStatus.isPaused = response.is_paused;
                attendanceStatus.checkInTime = response.check_in_time;
                attendanceStatus.totalWorkMinutes = parseFloat(response.total_work_minutes) || 0;
                attendanceStatus.totalPauseMinutes = parseFloat(response.total_pause_minutes) || 0;
                attendanceStatus.previousTotalHours = parseFloat(response.previous_total_hours) || 0;
                attendanceStatus.forgotClockOut = response.forgot_clock_out || false;
                attendanceStatus.lastStatusFetchedAt = Date.now();

                if (response.is_paused && response.current_pause_start) {
                    attendanceStatus.currentPauseStart = response.current_pause_start;
                    // Calculate and store fixed net work time when paused
                    if (attendanceStatus.checkInTime) {
                        const checkInTime = new Date(attendanceStatus.checkInTime);
                        const pauseStart = new Date(response.current_pause_start);
                        const pauseStartMinutes = (pauseStart - checkInTime) / 1000 / 60;
                        const completedPauseMinutes = attendanceStatus.totalPauseMinutes || 0;
                        attendanceStatus.fixedNetWorkMinutes = pauseStartMinutes - completedPauseMinutes;
                    }
                } else {
                    attendanceStatus.currentPauseStart = null;
                    attendanceStatus.fixedNetWorkMinutes = null; // Reset when not paused
                }

                updateUI();
                if (attendanceStatus.isCheckedIn) {
                    startTimer();
                } else {
                    stopTimer();
                }
            },
            error: function() {
                console.error('Failed to fetch attendance status');
            }
        });
    }

    // Update modal clock in real-time
    function updateModalClock() {
        const now = new Date();
        const dateStr = now.toLocaleDateString();
        const timeStr = now.toLocaleTimeString();
        const dayStr = now.toLocaleDateString('en-US', { weekday: 'long' });

        $('#modal-date').text(dateStr);
        $('#modal-time').text(timeStr);
        $('#modal-day').text(dayStr);
    }

    // Start modal clock
    function startModalClock() {
        // Clear any existing interval
        if (attendanceStatus.modalClockInterval) {
            clearInterval(attendanceStatus.modalClockInterval);
        }
        // Update immediately
        updateModalClock();
        // Update every second
        attendanceStatus.modalClockInterval = setInterval(updateModalClock, 1000);
    }

    // Stop modal clock
    function stopModalClock() {
        if (attendanceStatus.modalClockInterval) {
            clearInterval(attendanceStatus.modalClockInterval);
            attendanceStatus.modalClockInterval = null;
        }
    }

    // Show check-in modal
    function showCheckInModal() {
        $('#check-in-info').hide();
        $('#break-time-info').hide();
        $('#overtime-section').hide();
        // Clear any error messages
        $('#overtime-task-description').removeClass('is-invalid');
        $('#overtime-task-error').hide();
        
        // Show previous work time if exists (multiple check-ins on same date)
        const previousTotalHours = parseFloat(attendanceStatus.previousTotalHours) || 0;
        if (previousTotalHours > 0) {
            const previousTotalMinutes = previousTotalHours * 60;
            $('#modal-work-time').html('<strong>Previous Work Today: ' + formatHoursMinutes(previousTotalMinutes) + '</strong>');
            $('#work-time-info').show();
        } else {
            $('#work-time-info').hide();
        }
        
        $('#attendanceModalTitle').text('{{ $getCurrentTranslation["check_in"] ?? "Check In" }}');
        $('#confirm-attendance-action').data('action', 'check-in');

        const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
        modal.show();
        
        // Start real-time clock
        startModalClock();
    }

    // Show check-out modal
    function showCheckOutModal() {
        if (attendanceStatus.checkInTime) {
            const checkInTime = new Date(attendanceStatus.checkInTime);
            $('#modal-check-in-time').text(checkInTime.toLocaleString());
            $('#check-in-info').show();
        }

        // Initial display (same logic as timer; updateTimer() will keep modal-work-time real-time)
        const v = getWorkTimeValues();
        if (v) {
            let workTimeText = formatTime(v.currentSessionMinutes) + ' <small class="text-muted">(Session: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            if (v.previousTotalHours > 0) {
                workTimeText += '<br><strong>Total Today: ' + formatHoursMinutes(v.totalAccumulatedMinutes) + '</strong> <small class="text-muted">(Previous: ' + formatHoursMinutes(v.previousTotalMinutes) + ' + Current: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            }
            $('#modal-work-time').html(workTimeText);
            $('#modal-break-time').text(formatTime(v.totalPauseMinutes));
        }
        $('#work-time-info').show();
        $('#break-time-info').show();

        // Check for overtime based on accumulated total
        const totalAccumulatedMinutes = v ? v.totalAccumulatedMinutes : 0;
        if (totalAccumulatedMinutes > dailyWorkTimeMinutes) {
            $('#overtime-section').show();
            
            // Check if forgot_clock_out was previously checked
            const previouslyForgotClockOut = attendanceStatus.forgotClockOut || false;
            
            if (previouslyForgotClockOut) {
                // Previously checked - hide textarea and check the checkbox
                $('#forgot-clock-out').prop('checked', true);
                $('#overtime-task-group').slideUp(300);
                $('#overtime-task-description').prop('required', false);
            } else {
                // Not previously checked - show textarea and uncheck
                $('#forgot-clock-out').prop('checked', false);
                $('#overtime-task-group').slideDown(300);
                $('#overtime-task-description').prop('required', true);
            }
            
            // Clear any previous error messages
            $('#overtime-task-description').removeClass('is-invalid');
            $('#overtime-task-error').hide();
        } else {
            $('#overtime-section').hide();
            $('#overtime-task-group').hide();
            $('#overtime-task-description').prop('required', false);
            // Clear any error messages
            $('#overtime-task-description').removeClass('is-invalid');
            $('#overtime-task-error').hide();
        }

        $('#attendanceModalTitle').text('{{ $getCurrentTranslation["check_out"] ?? "Check Out" }}');
        $('#confirm-attendance-action').data('action', 'check-out');

        const modal = new bootstrap.Modal(document.getElementById('attendanceModal'));
        modal.show();
        
        // Start real-time clock
        startModalClock();
    }

    // Handle forgot clock out checkbox
    $('#forgot-clock-out').on('change', function() {
        if ($(this).is(':checked')) {
            $('#overtime-task-group').slideUp(300);
            $('#overtime-task-description').prop('required', false);
            // Clear error when checkbox is checked
            $('#overtime-task-description').removeClass('is-invalid');
            $('#overtime-task-error').hide();
        } else {
            $('#overtime-task-group').slideDown(300);
            const totalWorkMinutes = parseFloat(attendanceStatus.totalWorkMinutes) || 0;
            const totalPauseMinutes = parseFloat(attendanceStatus.totalPauseMinutes) || 0;
            const netWorkMinutes = totalWorkMinutes - totalPauseMinutes;
            const previousTotalHours = parseFloat(attendanceStatus.previousTotalHours) || 0;
            const totalAccumulatedMinutes = (previousTotalHours * 60) + netWorkMinutes;
            if (totalAccumulatedMinutes > dailyWorkTimeMinutes) {
                $('#overtime-task-description').prop('required', true);
            }
        }
    });

    // Clear error message when user starts typing
    $('#overtime-task-description').on('input keyup', function() {
        if ($(this).val().trim().length > 0) {
            $(this).removeClass('is-invalid');
            $('#overtime-task-error').hide();
        }
    });

    // Check-in button click
    $('#btn-check-in').on('click', function() {
        showCheckInModal();
    });

    // Check-out button click
    $('#btn-check-out').on('click', function() {
        showCheckOutModal();
    });

    // Pause button click
    $('#btn-pause').on('click', function() {
        Swal.fire({
            title: '{{ $getCurrentTranslation["pause_timer"] ?? "Pause Timer" }}',
            text: '{{ $getCurrentTranslation["are_you_sure_you_want_to_pause_the_timer"] ?? "Are you sure you want to pause the timer?" }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            confirmButtonText: '{{ $getCurrentTranslation["yes_pause"] ?? "Yes, Pause" }}',
            cancelButtonColor: '#d33',
            cancelButtonText: '{{ $getCurrentTranslation["cancel"] ?? "Cancel" }}',
        }).then((result) => {
            if (result.isConfirmed) {
                $('.r-preloader').show();
                $.ajax({
                    url: '{{ route("attendance.pause") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    success: function(response) {
                        $('.r-preloader').hide();
                        if (response.success) {
                            toastr.success(response.message);
                            fetchStatus();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        $('.r-preloader').hide();
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Failed to pause timer');
                        }
                    }
                });
            }
        });
    });

    // Resume button click
    $('#btn-resume').on('click', function() {
        Swal.fire({
            title: '{{ $getCurrentTranslation["resume_timer"] ?? "Resume Timer" }}',
            text: '{{ $getCurrentTranslation["are_you_sure_you_want_to_resume_the_timer"] ?? "Are you sure you want to resume the timer?" }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            confirmButtonText: '{{ $getCurrentTranslation["yes_resume"] ?? "Yes, Resume" }}',
            cancelButtonColor: '#d33',
            cancelButtonText: '{{ $getCurrentTranslation["cancel"] ?? "Cancel" }}',
        }).then((result) => {
            if (result.isConfirmed) {
                $('.r-preloader').show();
                $.ajax({
                    url: '{{ route("attendance.resume") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    success: function(response) {
                        $('.r-preloader').hide();
                        if (response.success) {
                            toastr.success(response.message);
                            fetchStatus();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        $('.r-preloader').hide();
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Failed to resume timer');
                        }
                    }
                });
            }
        });
    });

    // Confirm attendance action
    $('#confirm-attendance-action').on('click', function() {
        const action = $(this).data('action');
        
        if (action === 'check-in') {
            Swal.fire({
                title: '{{ $getCurrentTranslation["check_in"] ?? "Check In" }}',
                text: '{{ $getCurrentTranslation["are_you_sure_you_want_to_check_in"] ?? "Are you sure you want to check in?" }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: '{{ $getCurrentTranslation["yes_check_in"] ?? "Yes, Check In" }}',
                cancelButtonColor: '#d33',
                cancelButtonText: '{{ $getCurrentTranslation["cancel"] ?? "Cancel" }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    $('.r-preloader').show();
                    $.ajax({
                        url: '{{ route("attendance.checkIn") }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        success: function(response) {
                            $('.r-preloader').hide();
                            if (response.success) {
                                // Update previous total hours if provided
                                if (response.data && response.data.previous_total_hours !== undefined) {
                                    attendanceStatus.previousTotalHours = parseFloat(response.data.previous_total_hours) || 0;
                                }
                                toastr.success(response.message);
                                $('#attendanceModal').modal('hide');
                                fetchStatus();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            $('.r-preloader').hide();
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                            } else {
                                toastr.error('Failed to check in');
                            }
                        }
                    });
                }
            });
        } else if (action === 'check-out') {
            const overtimeTaskDescription = $('#overtime-task-description').val();
            const forgotClockOut = $('#forgot-clock-out').is(':checked');

            // Validate overtime description if needed (based on accumulated total)
            const totalWorkMinutes = parseFloat(attendanceStatus.totalWorkMinutes) || 0;
            const totalPauseMinutes = parseFloat(attendanceStatus.totalPauseMinutes) || 0;
            const netWorkMinutes = totalWorkMinutes - totalPauseMinutes;
            const previousTotalHours = parseFloat(attendanceStatus.previousTotalHours) || 0;
            const totalAccumulatedMinutes = (previousTotalHours * 60) + netWorkMinutes;
            
            if (totalAccumulatedMinutes > dailyWorkTimeMinutes && !forgotClockOut && !overtimeTaskDescription) {
                $('#overtime-section').show();
                $('#overtime-task-group').slideDown(300);
                $('#overtime-task-description').addClass('is-invalid');
                $('#overtime-task-error').show();
                toastr.error('{{ $getCurrentTranslation["overtime_task_description_required"] ?? "Overtime task description is required" }}');
                return;
            }

            Swal.fire({
                title: '{{ $getCurrentTranslation["check_out"] ?? "Check Out" }}',
                text: '{{ $getCurrentTranslation["are_you_sure_you_want_to_check_out"] ?? "Are you sure you want to check out?" }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                confirmButtonText: '{{ $getCurrentTranslation["yes_check_out"] ?? "Yes, Check Out" }}',
                cancelButtonColor: '#d33',
                cancelButtonText: '{{ $getCurrentTranslation["cancel"] ?? "Cancel" }}',
            }).then((result) => {
                if (result.isConfirmed) {
                    $('.r-preloader').show();
                    $.ajax({
                        url: '{{ route("attendance.checkOut") }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        data: {
                            overtime_task_description: overtimeTaskDescription,
                            forgot_clock_out: forgotClockOut ? 1 : 0
                        },
                        success: function(response) {
                            $('.r-preloader').hide();
                            if (response.success) {
                                $('#attendanceModal').modal('hide');
                                $('#overtime-task-description').val('');
                                $('#forgot-clock-out').prop('checked', false);
                                stopTimer();
                                
                                // Show SweetAlert and reload page when closed
                                Swal.fire({
                                    title: '{{ $getCurrentTranslation["check_out"] ?? "Check Out" }}',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK',
                                    allowOutsideClick: true,
                                    allowEscapeKey: true,
                                    showCloseButton: true
                                }).then(() => {
                                    // Reload page when swal is closed (OK clicked, outside click, or ESC)
                                    location.reload();
                                });
                            } else {
                                if (response.requires_overtime_description) {
                                    $('#overtime-section').show();
                                    $('#overtime-task-group').slideDown(300);
                                    $('#overtime-task-description').addClass('is-invalid');
                                    $('#overtime-task-error').show();
                                    toastr.error(response.message);
                                } else {
                                    toastr.error(response.message);
                                }
                            }
                        },
                        error: function(xhr) {
                            $('.r-preloader').hide();
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                toastr.error(xhr.responseJSON.message);
                                if (xhr.responseJSON.requires_overtime_description) {
                                    $('#overtime-section').show();
                                    $('#overtime-task-group').slideDown(300);
                                    $('#overtime-task-description').addClass('is-invalid');
                                    $('#overtime-task-error').show();
                                }
                            } else {
                                toastr.error('Failed to check out');
                            }
                        }
                    });
                }
            });
        }
    });

    // Stop modal clock when modal is closed
    $('#attendanceModal').on('hidden.bs.modal', function() {
        stopModalClock();
    });

    // Initialize on page load
    $(document).ready(function() {
        fetchStatus();
        // Refresh status every 30 seconds
        setInterval(fetchStatus, 30000);
    });
})();
</script>
