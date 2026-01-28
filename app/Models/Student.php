<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'exam_id', 'exam_date', 'id_number'];

    protected function casts(): array
    {
        return [
            'exam_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }

    public function studentSchool()
    {
        return $this->hasOne(StudentSchool::class, 'student_id', 'id');
    }
    public function studentParent()
    {
        return $this->hasOne(StudentParent::class, 'student_id', 'id');
    }
}
