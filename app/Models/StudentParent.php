<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentParent extends Model
{
    protected $fillable = ['parent_id' ,'student_id'];

    public function student()
    {
        return $this->belongsTo(Student::class,'student_id','id');
    }
}
