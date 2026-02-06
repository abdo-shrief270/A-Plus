<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\Question;
use App\Http\Resources\QuestionResource;
use App\Services\WalletService;
// use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;

class QuestionController extends BaseApiController
{
    // use ApiResponse;

    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function show(Question $question)
    {
        $user = auth('api')->user();

        // Check access/pay
        if ($user && $question->points_cost > 0) {
            $paid = $this->walletService->payForContent($user, $question, $question->points_cost, 'question_view');
            if (!$paid) {
                return $this->errorResponse('Insufficient points to view this question', 402);
            }
        }

        return $this->successResponse(
            new QuestionResource($question),
            'Question Data Returned Successfully'
        );
    }
}
