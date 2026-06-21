<?php

namespace Tests\Feature\Quiz;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\BuildsQuizWorld;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;
    use BuildsQuizWorld;

    public function test_world_builder_boots_and_links_questions(): void
    {
        $category = $this->makeCategory();
        $questions = $this->makeQuestions($category, 3);

        $this->assertCount(3, $questions);
        $this->assertDatabaseCount('category_questions', 3);
        $this->assertEquals(1, $questions->first()->answers->where('is_correct', true)->count());
    }
}
