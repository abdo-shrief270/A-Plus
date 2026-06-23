<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\SubmitAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Services\ScoreService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags الأسئلة والإجابات (Questions & Answers)
 */
class AnswerController extends BaseApiController
{
    public function __construct(
        protected ScoreService $scoreService,
        protected WalletService $walletService
    ) {
    }

    /**
     * Submit Question Answer (إرسال إجابة السؤال)
     * 
     * يُستخدم هذا المسار لدورة الإجابة على الأسئلة (The Answer Cycle).
     * عندما يقوم الطالب بالإجابة على سؤال، يتم استدعاء هذا المسار لتسجيل إجابته والتحقق من صحتها.
     * 
     * **منطق التلعيب (Gamification):**
     * إذا كانت الإجابة صحيحة حصراً (ولم يسبق له الإجابة عليها صحيحاً من قبل)، سيحصل على نقاط تُضاف إلى رصيده.
     * تُحدد النقاط المكتسبة بناءً على مستوى صعوبة السؤال:
     * - سؤال سهل (`easy`) = 10 نقاط.
     * - سؤال متوسط (`medium`) = 15 نقطة.
     * - سؤال صعب (`hard`) = 20 نقطة.
     *
     * على الواجهة الأمامية (Frontend) التحقق من مفتاح `score_earned` داخل الاستجابة، وإذا كان أكبر من 0 يجب إطلاق تأثير حركي (Celebration Animation) لإظهار النقاط المكتسبة للطالب.
     * أما إذا كانت الإجابة خاطئة، سيرجع النظام مفتاح `correct_answer` ليتم تظليل الإجابة الصحيحة أمام الطالب.
     * 
     * @bodyParam question_id integer required المعرف الافتراضي للسؤال المُراد الإجابة عليه. Example: 154
     * @bodyParam answer_id integer optional المعرف الافتراضي للإجابة المختارة (في حالة أسئلة الاختيار من متعدد). Example: 512
     * @bodyParam user_answer string optional نص الإجابة في حال كان السؤال مقالياً ويتطلب كتابة. Example: الحل هو 4
     * 
     * @group Gamification / Answer Cycle (دورة الإجابة والتلعيب)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{is_correct: bool, score_earned: int, correct_answer?: int}}
     * @response 401 array{status: int, message: string}
     * @response 422 array{status: int, message: string, errors: array} - المدخلات غير صالحة أو السؤال غير موجود
     */
    public function submit(SubmitAnswerRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse(__('messages.unauthenticated', [], 'ar') ?? 'غير مصرح للوصول', 401);
        }

        $question = Question::with('type')->findOrFail($request->question_id);
        $student = $user->student;

        // Trial users may only answer questions in the allowed sample category.
        if ($student && !app(\App\Services\TrialEntitlementService::class)->canAccessQuestion($student, $question)) {
            return $this->errorResponse(
                \App\Services\TrialEntitlementService::LOCKED_MESSAGE,
                Response::HTTP_FORBIDDEN,
                ['code' => \App\Services\TrialEntitlementService::LOCKED_REASON]
            );
        }

        // Wallet charge: deduct a flat cost the FIRST time a student answers a
        // given question. Active subscribers answer free; payForContent is
        // idempotent so re-answering the same question is never charged twice.
        $cost = (int) config('learning.question_answer_cost', 0);
        if ($student && $cost > 0 && !$student->hasUnlimitedAccess()) {
            $paid = $this->walletService->payForContent($student, $question, $cost, 'question_answer');
            if (!$paid) {
                return $this->errorResponse(
                    'رصيدك غير كافٍ للإجابة على هذا السؤال. يرجى شحن رصيدك أو الاشتراك للوصول غير المحدود.',
                    Response::HTTP_PAYMENT_REQUIRED
                );
            }
        }

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

        // Save or Update Answer. student_id MUST be set — revision metrics and
        // quiz "wrong/unanswered" pools read StudentAnswer by student_id; a
        // null here makes answers invisible to the whole revision page.
        $studentAnswer = StudentAnswer::updateOrCreate(
            [
                'user_id' => $user->id,
                'question_id' => $question->id,
            ],
            [
                'student_id' => $user->student?->id,
                'answer_id' => $request->answer_id,
                'user_answer' => $request->user_answer,
                'is_correct' => $isCorrect,
                'score_earned' => $scoreEarned,
            ]
        );

        // Award Gamification Score (league points) once per question, only on
        // the first correct answer.
        if ($isCorrect && $studentAnswer->wasRecentlyCreated && $user->student) {
            $this->scoreService->addScore($user->student, $scoreEarned, 'question_correct', $question);
        }

        $responseData = [
            'is_correct' => $isCorrect,
            'score_earned' => $scoreEarned,
            // Live point totals so the UI can update both counters instantly:
            // wallet balance (spent) and league score (earned).
            'balance' => $student ? $this->walletService->getBalance($student) : null,
            'total_score' => $student ? (int) $student->refresh()->current_score : null,
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
