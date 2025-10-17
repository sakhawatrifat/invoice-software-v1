<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketFlight extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $append = ['from_city', 'to_city'];
    protected $with = ['airline'];

    // Accessor for from_city
    public function getFromCityAttribute()
    {
        return extractPrimaryCity($this->leaving_from);
    }

    // Accessor for to_city
    public function getToCityAttribute()
    {
        return extractPrimaryCity($this->going_to);
    }
    
    public function transits()
    {
        return $this->hasMany(TicketFlight::class, 'parent_id', 'id');
    }

    public function airline()
    {
        return $this->belongsTo(Airline::class, 'airline_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
