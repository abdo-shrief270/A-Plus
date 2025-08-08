<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSchool extends Model
{
    protected $fillable = ['school_id' ,'student_id'];

    public function student()
    {
        return $this->belongsTo(Student::class,'student_id','id');
    }
}
