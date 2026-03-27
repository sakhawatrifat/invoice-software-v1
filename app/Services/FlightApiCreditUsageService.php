<?php

namespace App\Services;

use App\Models\FlightApiCreditUsage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FlightApiCreditUsageService
{
    /** @var array<int, string>|null */
    private static ?array $columns = null;

    /**
     * Record credits after a successful FlightAPI ticket search request.
     *
     * @param  array<string, mixed>  $searchParams
     */
    public function recordTicketSearch(?int $creditUsedBy, array $searchParams): void
    {
        $uid = $this->normalizeUserId($creditUsedBy);
        $this->insertRow(
            $uid,
            FlightApiCreditUsage::USED_FOR_TICKET_SEARCH,
            FlightApiCreditUsage::COST_TICKET_SEARCH,
            $searchParams
        );
    }

    /**
     * Record one credit per FlightAPI flight status (tracking) request.
     */
    /**
     * @param  array<string, mixed>  $context
     */
    public function recordFlightStatus(?int $creditUsedBy, array $context = []): void
    {
        $uid = $this->normalizeUserId($creditUsedBy);
        $this->insertRow(
            $uid,
            FlightApiCreditUsage::USED_FOR_FLIGHT_STATUS,
            FlightApiCreditUsage::COST_FLIGHT_STATUS,
            $context
        );
    }

    private function insertRow(
        ?int $creditUsedBy,
        string $usedFor,
        int $creditAmount,
        array $context = []
    ): void {
        try {
            $payload = [
                'credit_used_by' => $creditUsedBy,
                'usage_date_time' => now(),
                'used_for' => $usedFor,
                'credit_amount' => $creditAmount,
            ];

            $columns = $this->getTableColumns();

            if (in_array('payment_id', $columns, true)) {
                $paymentId = isset($context['payment_id']) ? (int) $context['payment_id'] : null;
                $payload['payment_id'] = $paymentId > 0 ? $paymentId : null;
            }

            // Some deployed schemas keep a JSON context column for debugging/analytics.
            $metaColumns = ['meta', 'request_meta', 'extra_meta', 'context'];
            foreach ($metaColumns as $metaColumn) {
                if (in_array($metaColumn, $columns, true)) {
                    $payload[$metaColumn] = json_encode($context, JSON_UNESCAPED_UNICODE);
                }
            }

            FlightApiCreditUsage::create($payload);
        } catch (\Throwable $e) {
            Log::warning('flight_api_credit_usage insert failed: ' . $e->getMessage(), [
                'used_for' => $usedFor,
                'credit_used_by' => $creditUsedBy,
                'context' => $context,
            ]);
        }
    }

    private function normalizeUserId(?int $creditUsedBy): ?int
    {
        $uid = $creditUsedBy ?? (Auth::check() ? (int) Auth::id() : null);
        if ($uid !== null && $uid <= 0) {
            return null;
        }

        return $uid;
    }

    /**
     * @return array<int, string>
     */
    private function getTableColumns(): array
    {
        if (self::$columns !== null) {
            return self::$columns;
        }

        try {
            self::$columns = Schema::getColumnListing((new FlightApiCreditUsage())->getTable());
        } catch (\Throwable $e) {
            self::$columns = [];
            Log::warning('flight_api_credit_usage column discovery failed: ' . $e->getMessage());
        }

        return self::$columns;
    }
}
