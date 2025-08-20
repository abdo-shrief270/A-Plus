<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    protected $fillable =['student_id','question_id'];

    public function student():BelongsTo
    {
        return $this->belongsTo(Student::class,'student_id','id');
    }

    public function question():BelongsTo
    {
        return $this->belongsTo(Question::class,'question_id','id');
    }
}
