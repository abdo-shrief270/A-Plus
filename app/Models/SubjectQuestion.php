<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectQuestion extends Model
{
    protected $fillable =['exam_subject_id','question_id'];

    public function subject() :BelongsTo
    {
        return $this->belongsTo(ExamSubject::class,'exam_subject_id','id');
    }
    public function question() :BelongsTo
    {
        return $this->belongsTo(Question::class,'question_id','id');
    }
}
