<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketPassenger extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function flights()
    {
        return $this->hasMany(TicketPassengerFlight::class, 'passenger_id', 'id');
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
