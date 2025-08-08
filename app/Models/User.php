<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'user_name',
        'phone',
        'email',
        'type',
        'gender',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    protected function hidden(): array
    {
        return [
            'created_at',
            'updated_at',
            'password',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function student()
    {
        return $this->hasOne(Student::class,'user_id','id');
    }

    public function studentParent()
    {
        return $this->hasMany(StudentParent::class,'parent_id','id');
    }

    public function scopeIsParent($query)
    {
        return $query->whereHas('studentParent');
    }

    public function scopeIsSchool($query)
    {
        return $query->whereHas('studentSchool');
    }

    public function scopeIsStudent($query)
    {
        return $query->whereHas('student');
    }
}
