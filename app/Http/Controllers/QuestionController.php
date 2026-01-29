<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\Question;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\select;

class QuestionController extends Controller
{
    use ApiResponse;

    public function questionData(Question $question)
    {
        try {
            return $this->apiResponse(200, 'Question Data Returned Successfully', null, [
                'question' => $question->setHidden(['answers']),
                'answers' => $question->answers->select('order', 'text')
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Question Data Returning failed: ' . $e->getMessage());
        }
    }




}
