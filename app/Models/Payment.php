<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = ['issued_suppliers', 'issued_suppliers_name'];

    protected function casts(): array
    {
        return [
            'paymentData' => 'json',
            'issued_supplier_ids' => 'array',
        ];
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'user_id', 'id');
    // }


    public function getIssuedSuppliersAttribute()
    {
        return IssuedSupplier::whereIn('id', $this->issued_supplier_ids ?? [])->get();
    }

    public function getIssuedSuppliersNameAttribute()
    {
        return IssuedSupplier::whereIn('id', $this->issued_supplier_ids ?? [])
            ->pluck('name')
            ->implode(', ');
    }


    public function paymentDocuments()
    {
        return $this->hasMany(PaymentDocument::class, 'payment_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function introductionSource()
    {
        return $this->belongsTo(IntroductionSource::class, 'introduction_source_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'customer_country_id', 'id');
    }

    // public function issuedSupplier()
    // {
    //     return $this->belongsTo(IssuedSupplier::class, 'issued_supplier_id', 'id');
    // }

    public function issuedBy()
    {
        return $this->belongsTo(IssuedBy::class, 'issued_by_id', 'id');
    }

    public function airline()
    {
        return $this->belongsTo(Airline::class, 'airline_id', 'id');
    }

    public function transferTo()
    {
        return $this->belongsTo(TransferTo::class, 'transfer_to_id', 'id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    public function issuedCardType()
    {
        return $this->belongsTo(IssuedCardType::class, 'issued_card_type_id', 'id');
    }

    public function cardOwner()
    {
        return $this->belongsTo(CardOwner::class, 'card_owner_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}
