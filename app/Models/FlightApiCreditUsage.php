<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightApiCreditUsage extends Model
{
    public const USED_FOR_TICKET_SEARCH = 'flight_ticket_search';

    public const USED_FOR_FLIGHT_STATUS = 'flight_status';

    public const COST_TICKET_SEARCH = 2;

    public const COST_FLIGHT_STATUS = 1;

    protected $table = 'flight_api_credit_usage';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'usage_date_time' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'credit_used_by');
    }
}
