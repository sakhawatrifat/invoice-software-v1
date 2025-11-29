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
        'departure_route',
        'return_route',
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

    // ✅ Departure Route (outbound flights - no parent_id)
    public function getDepartureRouteAttribute()
    {
        // Get main flights only (parent_id = null) - these are separate booking legs
        $mainFlights = $this->allFlights->filter(fn($flight) => is_null($flight->parent_id))->values();

        if ($mainFlights->isEmpty()) {
            return 'N/A';
        }

        // For Round Trip, get the outbound segment (first main flight group)
        $outboundFlight = $mainFlights->first();
        $routeParts = [];
        
        // Add starting point
        $routeParts[] = extractPrimaryCity($outboundFlight->leaving_from);
        
        // Add all destinations in the outbound journey
        $routeParts[] = extractPrimaryCity($outboundFlight->going_to);
        
        // Include any transits for this outbound flight
        if ($outboundFlight->transits && $outboundFlight->transits->isNotEmpty()) {
            foreach ($outboundFlight->transits as $transit) {
                $routeParts[] = extractPrimaryCity($transit->going_to);
            }
        }

        return implode(' - ', array_filter($routeParts));
    }

    // ✅ Return Route (separate return booking - identified by trip structure)
    public function getReturnRouteAttribute()
    {
        // Return route only applicable for Round Trip
        if ($this->trip_type !== 'Round Trip') {
            return 'N/A';
        }

        // Get all main flights (parent_id = null)
        $mainFlights = $this->allFlights->filter(fn($flight) => is_null($flight->parent_id))->values();

        if ($mainFlights->count() < 2) {
            return 'N/A';
        }

        // Get the return segment (second main flight)
        $returnFlight = $mainFlights->get(1);

        if (!$returnFlight) {
            return 'N/A';
        }

        $routeParts = [];
        
        // Add starting point
        $routeParts[] = extractPrimaryCity($returnFlight->leaving_from);
        
        // Add destination
        $routeParts[] = extractPrimaryCity($returnFlight->going_to);
        
        // Include any transits for this return flight
        if ($returnFlight->transits && $returnFlight->transits->isNotEmpty()) {
            foreach ($returnFlight->transits as $transit) {
                $routeParts[] = extractPrimaryCity($transit->going_to);
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
