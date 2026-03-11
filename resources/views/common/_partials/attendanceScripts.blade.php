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
        checkInLocation: null,
        checkInLocationUrl: null,
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

    // Location for check-in (set after user allows geolocation)
    let pendingCheckInLocation = null;

    // Prevent duplicate pause/resume requests and only one toast per action
    let pauseResumeInProgress = false;
    let lastPauseResumeToastAt = 0;
    const PAUSE_RESUME_TOAST_COOLDOWN_MS = 3000;

    function showPauseResumeToast(message, isSuccess) {
        var now = Date.now();
        if (now - lastPauseResumeToastAt < PAUSE_RESUME_TOAST_COOLDOWN_MS) return;
        lastPauseResumeToastAt = now;
        if (isSuccess) {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }

    // Get current location (optional). Calls callback with { lat, lng, accuracy } or null.
    // Uses a short watch to pick the best accuracy reading when available.
    function getCurrentLocation(callback) {
        if (!navigator.geolocation) {
            callback(null);
            return;
        }
        const opts = { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 };
        let best = null;
        let watchId = null;
        let done = false;

        function finish(result) {
            if (done) return;
            done = true;
            if (watchId !== null) {
                try { navigator.geolocation.clearWatch(watchId); } catch (e) {}
            }
            callback(result);
        }

        function toLoc(position) {
            return {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy
            };
        }

        // Start with one immediate attempt
        navigator.geolocation.getCurrentPosition(
            function(position) {
                best = toLoc(position);
                // If accuracy is good enough or we have no watch support, return immediately.
                if (!best.accuracy || best.accuracy <= 500) {
                    finish(best);
                    return;
                }
                try {
                    watchId = navigator.geolocation.watchPosition(
                        function(p2) {
                            const cand = toLoc(p2);
                            if (!best || (cand.accuracy && best.accuracy && cand.accuracy < best.accuracy)) {
                                best = cand;
                            } else if (!best) {
                                best = cand;
                            }
                            if (best && best.accuracy && best.accuracy <= 500) {
                                finish(best);
                            }
                        },
                        function() {
                            finish(best);
                        },
                        { enableHighAccuracy: true, maximumAge: 0, timeout: 30000 }
                    );
                } catch (e) {
                    finish(best);
                }
                setTimeout(function() { finish(best); }, 5000);
            },
            function() {
                finish(null);
            },
            opts
        );
    }

    const dailyWorkTimeHours = {{ env('DAILY_WORK_TIME', 8) }};
    const dailyWorkTimeMinutes = dailyWorkTimeHours * 60;

    // 0 = location not required for clock in/pause/resume/clock out; 1 = location mandatory
    const CLOCK_IN_LOCATION_ENABLE = {{ (int) (env('CLOCK_IN_LOCATION_ENABLE', 0)) }};

    // Clock-In popup: auto-show once daily between configured hours (user's local timezone, 24h)
    const CLOCK_IN_POPUP_STORAGE_KEY = 'attendance_clock_in_popup_shown_date';
    const CLOCK_IN_WINDOW_START_HOUR = {{ (int) (env('CLOCK_IN_POPUP_START_HOUR', 8)) }};   // e.g. 8 = 8:00 AM local
    const CLOCK_IN_WINDOW_END_HOUR = {{ (int) (env('CLOCK_IN_POPUP_END_HOUR', 17)) }};     // e.g. 17 = 5:00 PM local (exclusive)

    // All use browser local time (Date methods are local unless UTC suffix is used)
    function isWithinClockInWindow() {
        const now = new Date();
        const hour = now.getHours(); // local hour 0–23
        return hour >= CLOCK_IN_WINDOW_START_HOUR && hour < CLOCK_IN_WINDOW_END_HOUR;
    }

    function getTodayDateString() {
        const now = new Date();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d; // local date
    }

    function hasShownClockInPopupToday() {
        try {
            return (localStorage.getItem(CLOCK_IN_POPUP_STORAGE_KEY) || '') === getTodayDateString();
        } catch (e) {
            return false;
        }
    }

    function markClockInPopupShownToday() {
        try {
            localStorage.setItem(CLOCK_IN_POPUP_STORAGE_KEY, getTodayDateString());
        } catch (e) {}
    }

    function tryAutoShowClockInPopup() {
        if (attendanceStatus.isCheckedIn) return;
        if (!document.getElementById('attendanceModal')) return; // modal not in DOM (e.g. wrong layout)
        if (!isWithinClockInWindow()) return;
        if (hasShownClockInPopupToday()) return;
        if (CLOCK_IN_LOCATION_ENABLE) {
            requestLocationAndShowCheckInModal(true); // true = auto-show: mark only when modal actually opens
        } else {
            markClockInPopupShownToday();
            showCheckInModal();
        }
    }
    // Activity endpoint caches response. Backend clears cache on pause/resume; short delay so next fetch gets fresh data.
    const ACTIVITY_CACHE_MS = 500;
    // If accuracy is worse than this, it's typically IP-based / unreliable on desktop.
    const MAX_LOCATION_ACCURACY_METERS = 2000;

    function isPoorAccuracy(loc) {
        return !!(loc && typeof loc.accuracy === 'number' && isFinite(loc.accuracy) && loc.accuracy > MAX_LOCATION_ACCURACY_METERS);
    }

    // Format time helper (HH:MM:SS). Clamp to non-negative so break/work never show minus.
    function formatTime(minutes) {
        const m = Math.max(0, Number(minutes) || 0);
        const hours = Math.floor(m / 60);
        const mins = Math.floor(m % 60);
        const secs = Math.floor((m % 1) * 60);
        return String(hours).padStart(2, '0') + ':' +
               String(mins).padStart(2, '0') + ':' +
               String(secs).padStart(2, '0');
    }

    // Format total seconds as HH:MM:SS (for live break timer that ticks every second)
    function formatTimeFromSeconds(totalSeconds) {
        const s = Math.max(0, Math.floor(Number(totalSeconds) || 0));
        const hours = Math.floor(s / 3600);
        const mins = Math.floor((s % 3600) / 60);
        const secs = s % 60;
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
            // Live break: server already included current pause up to fetch time; add only elapsed since fetch
            const lastFetch = attendanceStatus.lastStatusFetchedAt || now;
            const addedSinceFetchMinutes = (now - lastFetch) / 60000;
            totalPauseMinutes = Math.max(0, (parseFloat(attendanceStatus.totalPauseMinutes) || 0) + addedSinceFetchMinutes);
        } else {
            const baseWork = parseFloat(attendanceStatus.totalWorkMinutes) || 0;
            const basePause = parseFloat(attendanceStatus.totalPauseMinutes) || 0;
            const lastFetch = attendanceStatus.lastStatusFetchedAt || now;
            const elapsedMinutes = (now - lastFetch) / 60000;
            currentSessionMinutes = Math.max(0, (baseWork - basePause) + elapsedMinutes);
            totalPauseMinutes = Math.max(0, basePause);
        }

        const previousTotalHours = parseFloat(attendanceStatus.previousTotalHours) || 0;
        const previousTotalMinutes = previousTotalHours * 60;
        const totalAccumulatedMinutes = previousTotalMinutes + currentSessionMinutes;

        // Break time in seconds for live HH:MM:SS (from same totalPauseMinutes so header and modal stay in sync)
        const totalPauseSeconds = Math.max(0, Math.floor((totalPauseMinutes || 0) * 60));

        return {
            currentSessionMinutes: currentSessionMinutes,
            totalPauseMinutes: totalPauseMinutes,
            totalPauseSeconds: totalPauseSeconds,
            previousTotalHours: previousTotalHours,
            previousTotalMinutes: previousTotalMinutes,
            totalAccumulatedMinutes: totalAccumulatedMinutes
        };
    }

    // Update timer display and modal work time (real-time, every second via setInterval)
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

        // Break time: live seconds so it ticks every second
        var breakTimeStr = formatTimeFromSeconds(v.totalPauseSeconds);
        $('#break-display').text('Break: ' + breakTimeStr);

        // Real-time update for checkout modal when visible (work time + break time tick every second)
        if ($('#attendanceModal').hasClass('show') && $('#confirm-attendance-action').data('action') === 'check-out') {
            let workTimeText = formatTime(v.currentSessionMinutes) + ' <small class="text-muted">(Session: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            if (v.previousTotalHours > 0) {
                workTimeText += '<br><strong>Total Today: ' + formatHoursMinutes(v.totalAccumulatedMinutes) + '</strong> <small class="text-muted">(Previous: ' + formatHoursMinutes(v.previousTotalMinutes) + ' + Current: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            }
            $('#modal-work-time').html(workTimeText);
            $('#modal-break-time').text(breakTimeStr);
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
    // Uses combined activity URL so one request can serve both attendance and chat refresh
    function fetchStatus() {
        $.ajax({
            url: '{{ route("chat.activity") }}',
            method: 'GET',
            success: function(response) {
                var data = (response && response.attendance) ? response.attendance : response;
                attendanceStatus.isCheckedIn = data.is_checked_in;
                attendanceStatus.isPaused = data.is_paused;
                attendanceStatus.checkInTime = data.check_in_time;
                attendanceStatus.checkInLocation = data.check_in_location || null;
                attendanceStatus.checkInLocationUrl = data.check_in_location_url || null;
                attendanceStatus.totalWorkMinutes = parseFloat(data.total_work_minutes) || 0;
                attendanceStatus.totalPauseMinutes = parseFloat(data.total_pause_minutes) || 0;
                attendanceStatus.previousTotalHours = parseFloat(data.previous_total_hours) || 0;
                attendanceStatus.forgotClockOut = data.forgot_clock_out || false;
                attendanceStatus.lastStatusFetchedAt = Date.now();

                if (data.is_paused && data.current_pause_start) {
                    attendanceStatus.currentPauseStart = data.current_pause_start;
                    // Calculate and store fixed net work time when paused
                    if (attendanceStatus.checkInTime) {
                        const checkInTime = new Date(attendanceStatus.checkInTime);
                        const pauseStart = new Date(data.current_pause_start);
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
                // Auto-show Clock-In popup once daily (8:00 AM – 5:00 PM user local time); only on first fetch per page load
                if (typeof tryAutoShowClockInPopup === 'function' && !window._clockInAutoShowAttempted) {
                    window._clockInAutoShowAttempted = true;
                    setTimeout(function() {
                        tryAutoShowClockInPopup();
                    }, 600);
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

    // Show check-in modal. Pass true to show "location unavailable" message and disable Confirm (user must Retry). When CLOCK_IN_LOCATION_ENABLE=0, location is not required.
    function showCheckInModal(locationUnavailable) {
        $('#check-in-info').hide();
        $('#check-in-location-info').hide();
        $('#break-time-info').hide();
        $('#overtime-section').hide();
        // Clear any error messages
        $('#overtime-task-description').removeClass('is-invalid');
        $('#overtime-task-error').hide();

        if (CLOCK_IN_LOCATION_ENABLE && locationUnavailable) {
            $('#check-in-location-unavailable').show();
            $('#confirm-attendance-action').prop('disabled', true).addClass('disabled');
        } else {
            $('#check-in-location-unavailable').hide();
            $('#confirm-attendance-action').prop('disabled', false).removeClass('disabled');
        }
        
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
        $('#check-in-location-info').show();
        if (attendanceStatus.checkInLocation) {
            $('#modal-check-in-location').text(attendanceStatus.checkInLocation);
            if (attendanceStatus.checkInLocationUrl) {
                $('#modal-check-in-location-link-wrap').html(' <a href="' + attendanceStatus.checkInLocationUrl + '" target="_blank" rel="noopener" class="small">({{ $getCurrentTranslation["view_on_map"] ?? "View on map" }})</a>');
            } else {
                $('#modal-check-in-location-link-wrap').empty();
            }
        } else {
            $('#modal-check-in-location').text('—');
            $('#modal-check-in-location-link-wrap').empty();
        }

        // Initial display (updateTimer() runs every 1s and keeps modal-work-time + modal-break-time live)
        const v = getWorkTimeValues();
        if (v) {
            let workTimeText = formatTime(v.currentSessionMinutes) + ' <small class="text-muted">(Session: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            if (v.previousTotalHours > 0) {
                workTimeText += '<br><strong>Total Today: ' + formatHoursMinutes(v.totalAccumulatedMinutes) + '</strong> <small class="text-muted">(Previous: ' + formatHoursMinutes(v.previousTotalMinutes) + ' + Current: ' + formatHoursMinutes(v.currentSessionMinutes) + ')</small>';
            }
            $('#modal-work-time').html(workTimeText);
            $('#modal-break-time').text(formatTimeFromSeconds(v.totalPauseSeconds));
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

    // Request location and show check-in modal only if user allows. When CLOCK_IN_LOCATION_ENABLE=0, location is not requested.
    // isAutoShow: when true, mark "shown today" only when modal actually opens (so auto-show runs once per day)
    function requestLocationAndShowCheckInModal(isAutoShow) {
        isAutoShow = !!isAutoShow;
        if (!CLOCK_IN_LOCATION_ENABLE) {
            if (isAutoShow) markClockInPopupShownToday();
            showCheckInModal();
            return;
        }
        if (!navigator.geolocation) {
            toastr.error('{{ $getCurrentTranslation["location_not_supported_by_browser"] ?? "Location is not supported by your browser. You cannot check in." }}');
            return;
        }
        // Always clear any previous location so we never reuse stale coordinates.
        pendingCheckInLocation = null;
        const locationRequiredMsg = '{{ $getCurrentTranslation["please_allow_location_to_check_in"] ?? "Please allow location access to check in." }}';
        const locationUnavailableMsg = '{{ $getCurrentTranslation["location_unavailable"] ?? "Location is unavailable. Please enable location and try again." }}';
        const locationTimeoutMsg = '{{ $getCurrentTranslation["location_request_timed_out"] ?? "Location request timed out. Please try again." }}';
        const $btn = $('#btn-check-in');
        $btn.prop('disabled', true);
        getCurrentLocation(function(loc) {
            $btn.prop('disabled', false);
            if (!loc) {
                pendingCheckInLocation = null;
                if (isAutoShow) markClockInPopupShownToday();
                showCheckInModal(true);
                if (typeof toastr !== 'undefined') toastr.warning(locationUnavailableMsg);
                return;
            }
            // If accuracy is too poor, warn user and allow retry/continue.
            if (isPoorAccuracy(loc)) {
                const km = Math.round((loc.accuracy / 1000) * 10) / 10;
                Swal.fire({
                    title: '{{ $getCurrentTranslation["location"] ?? "Location" }}',
                    text: 'Location accuracy is low (~' + km + ' km). This can cause a wrong address. Turn off VPN and enable Windows Location for better accuracy. Continue anyway?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: '{{ $getCurrentTranslation["confirm"] ?? "Confirm" }}',
                    cancelButtonColor: '#d33',
                    cancelButtonText: '{{ $getCurrentTranslation["retry"] ?? "Retry" }}',
                }).then((result) => {
                    if (result.isConfirmed) {
                        pendingCheckInLocation = loc;
                        if (isAutoShow) markClockInPopupShownToday();
                        if (typeof toastr !== 'undefined' && toastr.clear) toastr.clear();
                        showCheckInModal();
                    } else {
                        pendingCheckInLocation = null;
                        // Retry: request again (fresh, non-cached)
                        requestLocationAndShowCheckInModal(isAutoShow);
                    }
                });
                return;
            }

            pendingCheckInLocation = loc;
            if (isAutoShow) markClockInPopupShownToday();
            if (typeof toastr !== 'undefined' && toastr.clear) toastr.clear();
            showCheckInModal();
        });
    }

    // Retry location from inside the check-in modal (when location was unavailable)
    $(document).on('click', '#btn-retry-location', function() {
        var $retryBtn = $(this);
        if ($retryBtn.prop('disabled')) return;
        $retryBtn.prop('disabled', true).text('{{ $getCurrentTranslation["getting_location"] ?? "Getting location..." }}');
        getCurrentLocation(function(loc) {
            $retryBtn.prop('disabled', false).text('{{ $getCurrentTranslation["retry"] ?? "Retry" }} {{ $getCurrentTranslation["location"] ?? "Location" }}');
            if (!loc) {
                if (typeof toastr !== 'undefined') toastr.error('{{ $getCurrentTranslation["location_unavailable"] ?? "Location is unavailable. Please enable location and try again." }}');
                return;
            }
            if (isPoorAccuracy(loc)) {
                var km = Math.round((loc.accuracy / 1000) * 10) / 10;
                if (typeof toastr !== 'undefined') toastr.warning('Location accuracy is low (~' + km + ' km). You can still continue.');
            }
            pendingCheckInLocation = loc;
            $('#check-in-location-unavailable').hide();
            $('#confirm-attendance-action').prop('disabled', false).removeClass('disabled');
            if (typeof toastr !== 'undefined') toastr.success('{{ $getCurrentTranslation["location_obtained"] ?? "Location obtained. You can confirm check-in." }}');
        });
    });

    // Check-in button click. When location enabled, request location first; otherwise show modal directly.
    $('#btn-check-in').on('click', function() {
        if (CLOCK_IN_LOCATION_ENABLE) {
            requestLocationAndShowCheckInModal();
        } else {
            showCheckInModal();
        }
    });

    // Check-out button click
    $('#btn-check-out').on('click', function() {
        showCheckOutModal();
    });

    // Pause button click (updates user location when pausing) - delegated so it works when header is in DOM
    $(document).on('click', '#btn-pause', function(e) {
        e.stopImmediatePropagation();
        if (pauseResumeInProgress) return;
        pauseResumeInProgress = true;
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
            if (!result.isConfirmed) {
                pauseResumeInProgress = false;
                return;
            }
            function doPause(pauseData) {
                $('.r-preloader').show();
                $.ajax({
                    url: '{{ route("attendance.pause") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    data: pauseData,
                    success: function(response) {
                        pauseResumeInProgress = false;
                        $('.r-preloader').hide();
                        if (response.success) {
                            attendanceStatus.isPaused = true;
                            if (response.data && response.data.pause_start) {
                                attendanceStatus.currentPauseStart = response.data.pause_start;
                            } else {
                                attendanceStatus.currentPauseStart = new Date().toISOString().slice(0, 19).replace('T', ' ');
                            }
                            updateUI();
                            updateTimer();
                            showPauseResumeToast(response.message, true);
                            setTimeout(fetchStatus, ACTIVITY_CACHE_MS);
                        } else {
                            showPauseResumeToast(response.message, false);
                        }
                    },
                    error: function(xhr) {
                        pauseResumeInProgress = false;
                        $('.r-preloader').hide();
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to pause timer';
                        showPauseResumeToast(msg, false);
                    }
                });
            }
            if (CLOCK_IN_LOCATION_ENABLE) {
                getCurrentLocation(function(loc) {
                    if (!loc) {
                        pauseResumeInProgress = false;
                        toastr.error('{{ $getCurrentTranslation["location_required_to_pause"] ?? "Location is required to pause. Please allow location and try again." }}');
                        return;
                    }
                    var pauseData = {};
                    if (!isPoorAccuracy(loc)) {
                        pauseData.latitude = loc.lat;
                        pauseData.longitude = loc.lng;
                        pauseData.accuracy = loc.accuracy;
                    } else {
                        const km = Math.round((loc.accuracy / 1000) * 10) / 10;
                        toastr.warning('Location accuracy is low (~' + km + ' km). Skipping location update for pause.');
                    }
                    doPause(pauseData);
                });
            } else {
                var pauseData = {};
                getCurrentLocation(function(loc) {
                    if (loc && !isPoorAccuracy(loc)) {
                        pauseData.latitude = loc.lat;
                        pauseData.longitude = loc.lng;
                        pauseData.accuracy = loc.accuracy;
                    } else if (loc && isPoorAccuracy(loc)) {
                        const km = Math.round((loc.accuracy / 1000) * 10) / 10;
                        toastr.warning('Location accuracy is low (~' + km + ' km). Skipping location update for pause.');
                    }
                    doPause(pauseData);
                });
            }
        });
    });

    // Resume button click (updates user location when resuming) - delegated so it works when header is in DOM
    $(document).on('click', '#btn-resume', function(e) {
        e.stopImmediatePropagation();
        if (pauseResumeInProgress) return;
        pauseResumeInProgress = true;
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
            if (!result.isConfirmed) {
                pauseResumeInProgress = false;
                return;
            }
            function doResume(resumeData) {
                $('.r-preloader').show();
                $.ajax({
                    url: '{{ route("attendance.resume") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    data: resumeData,
                    success: function(response) {
                        pauseResumeInProgress = false;
                        $('.r-preloader').hide();
                        if (response.success) {
                            attendanceStatus.isPaused = false;
                            attendanceStatus.currentPauseStart = null;
                            attendanceStatus.fixedNetWorkMinutes = null;
                            var added = parseFloat(response.data && response.data.pause_duration_minutes) || 0;
                            attendanceStatus.totalPauseMinutes = (parseFloat(attendanceStatus.totalPauseMinutes) || 0) + added;
                            updateUI();
                            updateTimer();
                            showPauseResumeToast(response.message, true);
                            setTimeout(fetchStatus, ACTIVITY_CACHE_MS);
                        } else {
                            showPauseResumeToast(response.message, false);
                        }
                    },
                    error: function(xhr) {
                        pauseResumeInProgress = false;
                        $('.r-preloader').hide();
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to resume timer';
                        showPauseResumeToast(msg, false);
                    }
                });
            }
            if (CLOCK_IN_LOCATION_ENABLE) {
                getCurrentLocation(function(loc) {
                    if (!loc) {
                        pauseResumeInProgress = false;
                        toastr.error('{{ $getCurrentTranslation["location_required_to_resume"] ?? "Location is required to resume. Please allow location and try again." }}');
                        return;
                    }
                    var resumeData = {};
                    if (!isPoorAccuracy(loc)) {
                        resumeData.latitude = loc.lat;
                        resumeData.longitude = loc.lng;
                        resumeData.accuracy = loc.accuracy;
                    } else {
                        const km = Math.round((loc.accuracy / 1000) * 10) / 10;
                        toastr.warning('Location accuracy is low (~' + km + ' km). Skipping location update for resume.');
                    }
                    doResume(resumeData);
                });
            } else {
                var resumeData = {};
                getCurrentLocation(function(loc) {
                    if (loc && !isPoorAccuracy(loc)) {
                        resumeData.latitude = loc.lat;
                        resumeData.longitude = loc.lng;
                        resumeData.accuracy = loc.accuracy;
                    } else if (loc && isPoorAccuracy(loc)) {
                        const km = Math.round((loc.accuracy / 1000) * 10) / 10;
                        toastr.warning('Location accuracy is low (~' + km + ' km). Skipping location update for resume.');
                    }
                    doResume(resumeData);
                });
            }
        });
    });

    // Click on timer (clock) to pause or resume - same as clicking the button
    $(document).on('click', '#attendance-timer', function() {
        if (!attendanceStatus.isCheckedIn) return;
        if (attendanceStatus.isPaused) {
            $('#btn-resume').trigger('click');
        } else {
            $('#btn-pause').trigger('click');
        }
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
                    if (CLOCK_IN_LOCATION_ENABLE && !pendingCheckInLocation) {
                        toastr.error('{{ $getCurrentTranslation["location_required_to_check_in"] ?? "Location is required to check in. Please allow location and try again." }}');
                        return;
                    }
                    var checkInData = {};
                    if (pendingCheckInLocation) {
                        checkInData.latitude = pendingCheckInLocation.lat;
                        checkInData.longitude = pendingCheckInLocation.lng;
                        checkInData.accuracy = pendingCheckInLocation.accuracy;
                    }
                    $('.r-preloader').show();
                    $.ajax({
                        url: '{{ route("attendance.checkIn") }}',
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken()
                        },
                        data: checkInData,
                        success: function(response) {
                            $('.r-preloader').hide();
                            if (response.success) {
                                pendingCheckInLocation = null;
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
                    function doCheckOut(checkOutData) {
                        $('.r-preloader').show();
                        $.ajax({
                            url: '{{ route("attendance.checkOut") }}',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            data: checkOutData,
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
                    var baseData = {
                        overtime_task_description: overtimeTaskDescription,
                        forgot_clock_out: forgotClockOut ? 1 : 0
                    };
                    if (CLOCK_IN_LOCATION_ENABLE) {
                        getCurrentLocation(function(loc) {
                            if (!loc) {
                                toastr.error('{{ $getCurrentTranslation["location_required_to_check_out"] ?? "Location is required to check out. Please allow location and try again." }}');
                                return;
                            }
                            baseData.latitude = loc.lat;
                            baseData.longitude = loc.lng;
                            baseData.accuracy = loc.accuracy;
                            doCheckOut(baseData);
                        });
                    } else {
                        doCheckOut(baseData);
                    }
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
