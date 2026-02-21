<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chat file upload max size (KB)
    |--------------------------------------------------------------------------
    | Set in .env as CHAT_FILE_MAX_SIZE_KB. Use 0 or leave unset for unlimited.
    | Example: 5120 = 5MB.
    */
    'max_file_size_kb' => (function () {
        $v = env('CHAT_FILE_MAX_SIZE_KB');
        if ($v === null || $v === '' || (int) $v === 0) {
            return 0;
        }
        return (int) $v;
    })(),

    /*
    | Polling interval in seconds for new messages and activity refresh.
    | Set in .env as CHAT_POLL_INTERVAL_SECONDS (default 15). Higher = fewer requests.
    */
    'poll_interval_seconds' => (int) env('CHAT_POLL_INTERVAL_SECONDS', 15),

    /*
    | Activity response cache TTL in seconds (per user). Reduces DB load when
    | clients poll frequently. Set in .env as CHAT_ACTIVITY_CACHE_SECONDS (default 8).
    | Set to 0 to disable caching.
    */
    'activity_cache_seconds' => (int) env('CHAT_ACTIVITY_CACHE_SECONDS', 8),
];
