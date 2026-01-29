<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PracticeExam extends Model
{
    protected $fillable = ['title', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
