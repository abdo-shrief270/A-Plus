<?php

namespace Tests\Feature\Ai;

use App\Services\AiExplanationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class AiExplanationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    private AiExplanationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AiExplanationService::class);
    }

    private function aQuestion()
    {
        $student = $this->makeStudent();
        $cat = $this->makeCategory($student->exam);

        return $this->makeQuestions($cat, 1)->first();
    }

    public function test_disabled_when_no_key_and_explain_returns_null(): void
    {
        config(['ai.openai_key' => null]);
        Http::fake(); // assert no call leaks out

        $this->assertFalse($this->service->enabled());
        $this->assertNull($this->service->explain($this->aQuestion()));
        Http::assertNothingSent();
    }

    public function test_generates_and_caches_explanation_when_enabled(): void
    {
        config(['ai.openai_key' => 'sk-test', 'ai.model' => 'gpt-4o-mini', 'ai.request_timeout' => 10]);
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'لأن الناتج صحيح.']]],
            ], 200),
        ]);

        $question = $this->aQuestion();
        $first = $this->service->explain($question);

        $this->assertSame('لأن الناتج صحيح.', $first);
        $this->assertSame('لأن الناتج صحيح.', $question->fresh()->ai_explanation);

        // Second call must be served from the cached column — no extra HTTP.
        $second = $this->service->explain($question->fresh());
        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_returns_null_and_does_not_cache_on_api_failure(): void
    {
        config(['ai.openai_key' => 'sk-test', 'ai.model' => 'gpt-4o-mini', 'ai.request_timeout' => 10]);
        Http::fake(['api.openai.com/*' => Http::response('error', 500)]);

        $question = $this->aQuestion();
        $this->assertNull($this->service->explain($question));
        $this->assertNull($question->fresh()->ai_explanation);
    }
}
