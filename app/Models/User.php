<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use SoftDeletes;
    //use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'json',
        ];
    }


    protected $appends = ['image_url', 'company_data', 'business_id'];

    public function getImageUrlAttribute()
    {
        //getUploadedAsset($this->image);
        return $this->image ? getUploadedUrl($this->image) : null;
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id', 'id');
    }

    public function company()
    {
        return $this->hasOne(UserCompany::class, 'user_id', 'id');
    }

    // Rename this
    public function ownCompany()
    {
        return $this->hasOne(UserCompany::class, 'user_id', 'id');
    }

    // Keep this as-is
    public function companyViaAdmin()
    {
        return $this->hasOneThrough(
            UserCompany::class,
            User::class,
            'id',
            'user_id',
            'parent_id',
            'id'
        );
    }

    // Accessor stays the same
    public function getCompanyDataAttribute()
    {
        return $this->is_staff ? $this->companyViaAdmin : $this->ownCompany;
    }

    // Accessor stays the same
    public function getBusinessIdAttribute()
    {
        return $this->is_staff ? $this->parent_id : $this->id;
    }


    // public function user()
    // {
    //     return $this->belongsTo(User::class, 'user_id', 'id');
    // }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

}
