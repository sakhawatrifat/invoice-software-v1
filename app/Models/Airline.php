<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Airline extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {   
        $url = null;
        if($this->logo != null){
            $url = getUploadedUrl($this->logo);
        }

        return $url;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
