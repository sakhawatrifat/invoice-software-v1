<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'expense_id',
        'document_name',
        'document_file',
        'document_type',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['document_file_url'];

    public function getDocumentFileUrlAttribute()
    {   
        $url = null;
        if($this->document_file != null){
            $url = getUploadedUrl($this->document_file);
        }

        return $url;
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
