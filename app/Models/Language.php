<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function translations()
    {
        return $this->hasMany(Translation::class, 'lang', 'code');
    }
}
