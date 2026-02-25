<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\SubmitAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Services\ScoreService;
use Illuminate\Http\JsonResponse;

/**
 * @tags الأسئلة والإجابات (Questions & Answers)
 */
class AnswerController extends BaseApiController
{
    protected ScoreService $scoreService;

    public function __construct(ScoreService $scoreService)
    {
        $this->scoreService = $scoreService;
    }

    /**
     * تسجيل إجابة الطالب
     * 
     * يقوم هذا المسار بتسجيل إجابة الطالب على سؤال معين. إذا كانت الإجابة صحيحة وكان الطالب يجيب لأول مرة، سيحصل على نقاط تُضاف إلى رصيده (Gamification). يتم تحديد النقاط المكتسبة بناءً على مستوى صعوبة السؤال (سهل: 10، متوسط: 15، صعب: 20).
     * 
     * @param SubmitAnswerRequest $request
     * @return JsonResponse
     */
    public function submit(SubmitAnswerRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse(__('messages.unauthenticated', [], 'ar') ?? 'غير مصرح للوصول', 401);
        }

        $question = Question::with('type')->findOrFail($request->question_id);

        $isCorrect = false;
        $scoreEarned = 0;

        // Simple MCQ logic
        if ($request->answer_id) {
            $answer = Answer::find($request->answer_id);
            if ($answer && $answer->is_correct) {
                $isCorrect = true;
            }
        }

        // Score logic based on difficulty (as from V1)
        if ($isCorrect) {
            $scoreEarned = 10; // Base score for 'easy' or default
            if ($question->difficulty === 'hard') {
                $scoreEarned = 20;
            } elseif ($question->difficulty === 'medium') {
                $scoreEarned = 15;
            }
        }

        // Save or Update Answer
        $studentAnswer = StudentAnswer::updateOrCreate(
            [
                'user_id' => $user->id,
                'question_id' => $question->id,
            ],
            [
                'answer_id' => $request->answer_id,
                'user_answer' => $request->user_answer,
                'is_correct' => $isCorrect,
                'score_earned' => $scoreEarned,
            ]
        );

        // Award Gamification Score if correct
        if ($isCorrect && $studentAnswer->wasRecentlyCreated) {
             // Ensure we pass the Student model, not the User model
            $student = $user->student ?? $user; // fallback if needed, but it should be student
            $this->scoreService->addScore($student, $scoreEarned, 'question_correct', $question);
        }

        $responseData = [
            'is_correct' => $isCorrect,
            'score_earned' => $scoreEarned,
        ];

        if (!$isCorrect) {
            $responseData['correct_answer'] = $this->getCorrectAnswer($question);
        }

        return $this->successResponse(
            $responseData,
            __('messages.answer_submitted_successfully', [], 'ar') ?? 'تم تقديم الإجابة بنجاح'
        );
    }

    protected function getCorrectAnswer(Question $question)
    {
        // Return the correct answer ID or text depending on the question setup
        return $question->answers()->where('is_correct', true)->first()?->id;
    }
}
