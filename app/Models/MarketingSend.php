<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingSend extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'subject',
        'content',
        'customers',
        'document_path',
        'document_name',
        'sent_date_time',
        'created_by',
    ];

    protected $casts = [
        'customers' => 'array',
        'sent_date_time' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Full URL for the stored document (for display/download).
     */
    public function getDocumentUrlAttribute()
    {
        if (empty($this->document_path)) {
            return null;
        }
        return getUploadedUrl($this->document_path);
    }

    /**
     * File extension of document for preview type.
     */
    public function getDocumentExtensionAttribute()
    {
        if (empty($this->document_name)) {
            return '';
        }
        return strtolower(pathinfo($this->document_name, PATHINFO_EXTENSION));
    }
}
