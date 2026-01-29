<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parentt extends User
{
    protected $table = 'users';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('parent', function ($builder) {
            $builder->where('type', 'parent');
        });

        static::creating(function ($model) {
            $model->type = 'parent';
        });
    }
}
