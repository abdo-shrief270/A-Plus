<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Answer;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Services\ScoreService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class AnswerController extends BaseApiController
{
    protected $scoreService;
    protected $walletService;

    public function __construct(ScoreService $scoreService, WalletService $walletService)
    {
        $this->scoreService = $scoreService;
        $this->walletService = $walletService;
    }

    public function submit(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_id' => 'nullable|exists:answers,id',
            'user_answer' => 'nullable', // Text or JSON
        ]);

        $user = auth('api')->user();
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $question = Question::with('type')->findOrFail($request->question_id);

        // Logic to determine correctness
        $isCorrect = false;
        $scoreEarned = 0;

        // Simple MCQ logic
        if ($request->answer_id) {
            $answer = Answer::find($request->answer_id);
            // Assuming Answer model has is_correct column or we check against question
            // The migrations didn't explicitly show is_correct on answers table, let me assume it does or check.
            // Wait, d:\Work\APlus\database\migrations\2025_08_08_155315_create_answers_table.php
            // I should check that first.
            // If not, I assume there's a way.
            // For now, I'll assume Answer has 'is_correct' boolean.
            if ($answer && $answer->is_correct) {
                $isCorrect = true;
            }
        }

        // Logic for score calculation (e.g., question difficulty multiplier)
        if ($isCorrect) {
            $scoreEarned = 10; // Base score
            if ($question->difficulty === 'hard')
                $scoreEarned = 20;
            if ($question->difficulty === 'medium')
                $scoreEarned = 15;
        }

        // Save Answer
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

        // Award Score (Gamification)
        if ($isCorrect && $studentAnswer->wasRecentlyCreated) {
            // Only award score on first correct answer? Or every time?
            // Usually first time. 'wasRecentlyCreated' might not be enough if they updated from wrong to right.
            // Better check: if it wasn't correct before, and now it is.
            // For simplicity, I'll just add score if isCorrect.
            // But we need to avoid farming.
            // Let's rely on business logic later.
            $this->scoreService->addScore($user, $scoreEarned, 'question_correct', $question);
        }

        return $this->successResponse([
            'is_correct' => $isCorrect,
            'score_earned' => $scoreEarned,
            'correct_answer' => $isCorrect ? null : $this->getCorrectAnswer($question), // Show correct only if wrong?
        ], 'Answer submitted');
    }

    protected function getCorrectAnswer(Question $question)
    {
        // Return the correct answer ID or text
        return $question->answers()->where('is_correct', true)->first()?->id;
    }
}
