<?php

namespace App\Services;

use App\Models\Question;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates a clear, student-friendly Arabic explanation for a question using
 * the OpenAI Chat Completions API, then caches it on the question so the API
 * is called at most once per question.
 */
class AiExplanationService
{
    public function enabled(): bool
    {
        return filled(config('ai.openai_key'));
    }

    /**
     * Return the question's AI explanation, generating + caching it on first
     * request. Returns null if the feature is disabled or generation fails.
     */
    public function explain(Question $question): ?string
    {
        if (filled($question->ai_explanation)) {
            return $question->ai_explanation;
        }

        if (!$this->enabled()) {
            return null;
        }

        $explanation = $this->generate($question);
        if (!$explanation) {
            return null;
        }

        $question->forceFill([
            'ai_explanation' => $explanation,
            'ai_explanation_generated_at' => now(),
        ])->save();

        return $explanation;
    }

    private function generate(Question $question): ?string
    {
        $question->loadMissing('answers');
        $answers = $question->answers
            ->map(fn ($a) => '- ' . $a->text . ($a->is_correct ? '  (الإجابة الصحيحة)' : ''))
            ->implode("\n");

        $prompt = "أنت معلّم خبير. اشرح للطالب لماذا الإجابة الصحيحة صحيحة بخطوات واضحة ومبسّطة بالعربية، "
            . "دون ذكر أنك ذكاء اصطناعي. ركّز على طريقة الوصول للحل.\n\n"
            . "السؤال:\n{$question->text}\n\nالخيارات:\n{$answers}\n"
            . ($question->explanation_text ? "\nشرح مرجعي مختصر:\n{$question->explanation_text}\n" : '')
            . "\nاكتب الشرح فقط.";

        try {
            $response = Http::withToken(config('ai.openai_key'))
                ->timeout(config('ai.request_timeout'))
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('ai.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => 'مدرّس يشرح بالعربية الفصحى المبسّطة.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.4,
                    'max_tokens' => 600,
                ]);

            if (!$response->successful()) {
                Log::warning('OpenAI explanation failed', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $text = $response->json('choices.0.message.content');

            return $text ? trim($text) : null;
        } catch (\Throwable $e) {
            Log::warning('OpenAI explanation exception: ' . $e->getMessage());

            return null;
        }
    }
}
