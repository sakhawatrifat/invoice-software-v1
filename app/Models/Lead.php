<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_full_name',
        'email',
        'phone',
        'company_name',
        'website',
        'source_id',
        'status',
        'priority',
        'assigned_to',
        'notes',
        'last_contacted_at',
        'converted_customer_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $dates = [
        'last_contacted_at',
        'converted_customer_at',
    ];

    public function source()
    {
        return $this->belongsTo(LeadSource::class, 'source_id', 'id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
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

