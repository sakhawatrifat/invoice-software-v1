<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class Notification extends Model
{
    protected $appends = ['full_url']; // auto-append this to JSON responses

    public function getFullUrlAttribute()
    {
        // If 'url' is already absolute (like http:// or https://), return as is
        if (preg_match('/^https?:\/\//', $this->url)) {
            return $this->url;
        }

        // Otherwise, prepend the app URL (from APP_URL in .env)
        return URL::to($this->url);
    }
}
