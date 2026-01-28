<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements FilamentUser, JWTSubject
{
    use HasRoles, \Illuminate\Notifications\Notifiable;
    protected $fillable = ['name', 'email', 'password', 'active'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->active;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
