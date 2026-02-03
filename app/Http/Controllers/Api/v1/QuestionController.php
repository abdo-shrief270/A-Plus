<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\Question;
// use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\select;

class QuestionController extends BaseApiController
{
    // use ApiResponse;

    public function questionData(Question $question)
    {
        try {
            return $this->successResponse([
                'question' => $question->setHidden(['answers']),
                'answers' => $question->answers->select('order', 'text')
            ], 'Question Data Returned Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Question Data Returning failed: ' . $e->getMessage(), 500);
        }
    }




}
