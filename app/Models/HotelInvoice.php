<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HotelInvoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $appends = ['hotel_image_url'];

    public function getHotelImageUrlAttribute()
    {
        //getUploadedAsset($this->hotel_image);
        return $this->hotel_image ? getUploadedUrl($this->hotel_image) : null;
    }

    protected function casts(): array
    {
        return [
            'guestInfo' => 'json',
            'cancellationPolicy' => 'json',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
