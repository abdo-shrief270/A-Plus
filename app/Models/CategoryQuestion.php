<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryQuestion extends Model
{
    protected $fillable =['section_category_id','question_id'];


    public function category() :BelongsTo
    {
        return $this->belongsTo(SectionCategory::class,'section_category_id','id');
    }
    public function question() :BelongsTo
    {
        return $this->belongsTo(Question::class,'question_id','id');
    }
}
