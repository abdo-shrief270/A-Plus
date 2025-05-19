<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = ['user_id','exam_id','exam_date','id_number'];

    protected function casts(): array
    {
        return [
            'exam_date' => 'datetime',
        ];
    }

    public function user() :BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
