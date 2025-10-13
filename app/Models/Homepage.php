<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Homepage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'bannerData' => 'json',
        'featureContent' => 'json',
        'content' => 'json'
    ];

    protected $appends = ['banner_url', 'auth_bg_image_url'];

    public function getBannerUrlAttribute()
    {   
        $url = null;
        if($this->banner != null){
            $url = getUploadedUrl($this->banner);
        }

        return $url;
    }

    public function getAuthBgImageUrlAttribute()
    {   
        $url = null;
        if($this->auth_bg_image != null){
            $url = getUploadedUrl($this->auth_bg_image);
        }

        return $url;
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'lang', 'code');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
