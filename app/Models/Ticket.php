<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = [
        'departure_city',
        'destination_city',
        'flight_route',
        'departure_datetime',
        'return_datetime'
    ];

    public function allFlights()
    {
        return $this->hasMany(TicketFlight::class, 'ticket_id', 'id');
    }
    
    public function flights()
    {
        return $this->hasMany(TicketFlight::class, 'ticket_id', 'id')->whereNull('parent_id');
    }

    public function passengers()
    {
        return $this->hasMany(TicketPassenger::class, 'ticket_id', 'id');
    }

    public function fareSummary()
    {
        return $this->hasMany(TicketFareSummary::class, 'ticket_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors (Appended Attributes)
    |--------------------------------------------------------------------------
    */

    // ✅ Departure City
    public function getDepartureCityAttribute()
    {
        $firstFlight = $this->allFlights->first();
        return extractPrimaryCity(optional($firstFlight)->leaving_from);
    }

    // ✅ Destination City
    public function getDestinationCityAttribute()
    {
        $flights = $this->allFlights;
        if ($flights->count() > 1) {
            return extractPrimaryCity(optional($flights->last())->going_to);
        }
        return extractPrimaryCity(optional($flights->first())->going_to);
    }

    // ✅ Flight Route (DAC - DXB - LHR ...)
    public function getFlightRouteAttribute()
    {
        $flights = $this->allFlights;

        if ($flights->isEmpty()) {
            return 'N/A';
        }

        $firstFlight = $flights->first();
        $routeParts = [];
        $routeParts[] = extractPrimaryCity(optional($firstFlight)->leaving_from);
        $routeParts[] = extractPrimaryCity(optional($firstFlight)->going_to);

        if ($flights->count() > 1) {
            foreach ($flights->skip(1) as $flight) {
                $routeParts[] = extractPrimaryCity(optional($flight)->going_to);
            }
        }

        return implode(' - ', array_filter($routeParts));
    }

    // ✅ Departure DateTime
    public function getDepartureDatetimeAttribute()
    {
        return optional($this->flights->first())->departure_date_time;
    }

    // ✅ Return DateTime (only for Round Trip)
    public function getReturnDatetimeAttribute()
    {
        if ($this->trip_type != 'Round Trip' || $this->allFlights->isEmpty()) {
            return null;
        }

        $lastFlight = $this->allFlights
            ->filter(fn($flight) => !empty($flight->departure_date_time))
            ->last();

        return $lastFlight->departure_date_time ?? null;
    }
}
