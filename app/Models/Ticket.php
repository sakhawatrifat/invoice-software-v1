<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;
    use SoftDeletes;

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
}
