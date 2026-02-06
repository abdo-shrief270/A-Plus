<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StudentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'score',
        'reason',
        'reference_type',
        'reference_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
