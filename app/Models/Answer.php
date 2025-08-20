<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    protected $fillable =['question_id','text','is_correct','order'];

    public function question():BelongsTo
    {
        return $this->belongsTo(Question::class,'question_id','id');
    }

    protected static function booted()
    {
        static::creating(function ($answer) {
            if (is_null($answer->order)) {
                $maxOrder = Answer::where('question_id', $answer->question_id)->max('order');
                $answer->order = $maxOrder ? $maxOrder + 1 : 1;
            }
        });
    }

    
}
