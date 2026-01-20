<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'expense_category_id',
        'for_user_id',
        'title',
        'description',
        'amount',
        'expense_date',
        'payment_method',
        'reference_number',
        'notes',
        'payment_status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id', 'id');
    }

    public function forUser()
    {
        return $this->belongsTo(User::class, 'for_user_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function documents()
    {
        return $this->hasMany(ExpenseDocument::class, 'expense_id', 'id');
    }
}
