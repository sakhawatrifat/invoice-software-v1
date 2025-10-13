<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDocument extends Model
{
    protected $appends = ['file_full_url'];

    public function getFileFullUrlAttribute()
    {   
        $url = null;
        if($this->file_url != null){
            $url = getUploadedUrl($this->file_url);
        }

        return $url;
    }
}
