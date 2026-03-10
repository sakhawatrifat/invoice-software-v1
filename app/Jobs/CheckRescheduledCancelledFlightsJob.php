<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\PaymentController;

class CheckRescheduledCancelledFlightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job: run the check and store result in cache for this user.
     */
    public function handle(): void
    {
        $controller = app(PaymentController::class);
        $payload = $controller->runRescheduledCancelledCheck($this->userId);

        if ($payload === null) {
            Cache::forget('rescheduled_cancelled_running_' . $this->userId);
            return;
        }

        [$found, $statusByPayment] = $payload;
        Cache::put('rescheduled_cancelled_result_' . $this->userId, [
            'payment_ids' => $found,
            'status_by_payment' => $statusByPayment,
            'checked_at' => now()->toDateTimeString(),
        ], now()->addHours(24));
        Cache::forget('rescheduled_cancelled_running_' . $this->userId);
    }
}
