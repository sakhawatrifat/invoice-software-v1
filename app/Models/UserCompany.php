<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCompany extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $appends = ['light_logo_url', 'dark_logo_url', 'light_icon_url', 'dark_icon_url', 'light_seal_url', 'dark_seal_url'];

    protected $with = ['currency'];

    protected function casts(): array
    {
        return [
            'cc_emails' => 'json',
            'bcc_emails' => 'json',
        ];
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'id');
    }

    public function getLightLogoUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->light_logo ? getUploadedUrl($this->light_logo) : null;
    }

    public function getDarkLogoUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->dark_logo ? getUploadedUrl($this->dark_logo) : null;
    }

    public function getLightIconUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->light_icon ? getUploadedUrl($this->light_icon) : null;
    }

    public function getDarkIconUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->dark_icon ? getUploadedUrl($this->dark_icon) : null;
    }

    public function getLightSealUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->light_seal ? getUploadedUrl($this->light_seal) : null;
    }

    public function getDarkSealUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->dark_seal ? getUploadedUrl($this->dark_seal) : null;
    }
}
