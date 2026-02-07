<?php

namespace Tests\Feature\Api\v2;

use App\Models\Answer;
use App\Models\ExamSubject;
use App\Models\Question;
use App\Models\SectionCategory;
use App\Models\ExamSection;
use App\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_get_trending_questions()
    {
        // Create trending (new) questions
        Question::factory()->count(5)->create(['is_new' => true]);
        // Create non-trending questions
        Question::factory()->count(3)->create(['is_new' => false]);

        $response = $this->getJson('/api/v2/questions/trending');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'questions' => [
                        '*' => ['id', 'text', 'difficulty', 'is_new', 'answers']
                    ],
                    'pagination'
                ]
            ]);

        $this->assertCount(5, $response->json('data.questions'));
    }

    /** @test */
    public function it_can_filter_trending_questions_by_difficulty()
    {
        Question::factory()->count(2)->create(['is_new' => true, 'difficulty' => 'easy']);
        Question::factory()->count(3)->create(['is_new' => true, 'difficulty' => 'hard']);

        $response = $this->getJson('/api/v2/questions/trending?difficulty=easy');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.questions'));
    }

    /** @test */
    public function it_can_show_question_details()
    {
        $question = Question::factory()->create();
        Answer::factory()->count(4)->create(['question_id' => $question->id]);

        $response = $this->getJson("/api/v2/questions/{$question->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'question' => [
                        'id',
                        'text',
                        'difficulty',
                        'is_new',
                        'explanation' => ['text', 'image_path', 'video_url'],
                        'answers' => [
                            '*' => ['id', 'text', 'is_correct', 'order']
                        ]
                    ]
                ]
            ]);

        $this->assertCount(4, $response->json('data.question.answers'));
    }

    /** @test */
    public function it_can_get_questions_by_subject()
    {
        $exam = Exam::factory()->create();
        $subject = ExamSubject::factory()->create(['exam_id' => $exam->id]);
        $questions = Question::factory()->count(5)->create();

        foreach ($questions as $question) {
            $question->subjects()->attach($subject->id);
            Answer::factory()->count(4)->create(['question_id' => $question->id]);
        }

        $response = $this->getJson("/api/v2/subjects/{$subject->id}/questions");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'subject' => ['id', 'name', 'description'],
                    'questions' => [
                        '*' => ['id', 'text', 'answers']
                    ],
                    'pagination'
                ]
            ]);

        $this->assertEquals($subject->id, $response->json('data.subject.id'));
        $this->assertCount(5, $response->json('data.questions'));
    }

    /** @test */
    public function it_can_get_questions_by_category()
    {
        $exam = Exam::factory()->create();
        $section = ExamSection::factory()->create(['exam_id' => $exam->id]);
        $category = SectionCategory::factory()->create(['exam_section_id' => $section->id]);
        $questions = Question::factory()->count(3)->create();

        foreach ($questions as $question) {
            $question->categories()->attach($category->id);
            Answer::factory()->count(4)->create(['question_id' => $question->id]);
        }

        $response = $this->getJson("/api/v2/categories/{$category->id}/questions");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'category' => ['id', 'name', 'description'],
                    'questions' => [
                        '*' => ['id', 'text', 'answers']
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data.questions'));
    }

    /** @test */
    public function it_can_search_questions()
    {
        Question::factory()->create(['text' => 'What is the capital of France?']);
        Question::factory()->create(['text' => 'What is the capital of Germany?']);
        Question::factory()->create(['text' => 'What is 2 + 2?']);

        $response = $this->getJson('/api/v2/questions/search?q=capital');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'search_query',
                    'questions' => [
                        '*' => ['id', 'text']
                    ],
                    'pagination'
                ]
            ]);

        $this->assertEquals('capital', $response->json('data.search_query'));
        $this->assertCount(2, $response->json('data.questions'));
    }

    /** @test */
    public function it_validates_search_query_requirement()
    {
        $response = $this->getJson('/api/v2/questions/search');

        $response->assertStatus(200)
            ->assertJson(['status' => 422])
            ->assertJsonValidationErrors(['q']);
    }

    /** @test */
    public function it_can_get_recent_questions()
    {
        Question::factory()->count(3)->create([
            'is_new' => true,
            'created_at' => now()->subDays(1)
        ]);
        Question::factory()->count(2)->create([
            'is_new' => false,
            'created_at' => now()
        ]);

        $response = $this->getJson('/api/v2/questions/recent');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'questions',
                    'pagination'
                ]
            ]);

        $this->assertCount(3, $response->json('data.questions'));
    }

    /** @test */
    public function it_can_filter_questions_by_subject_and_difficulty()
    {
        $exam = Exam::factory()->create();
        $subject = ExamSubject::factory()->create(['exam_id' => $exam->id]);

        $easyQuestions = Question::factory()->count(2)->create(['difficulty' => 'easy']);
        $hardQuestions = Question::factory()->count(3)->create(['difficulty' => 'hard']);

        foreach ($easyQuestions->merge($hardQuestions) as $question) {
            $question->subjects()->attach($subject->id);
        }

        $response = $this->getJson("/api/v2/subjects/{$subject->id}/questions?difficulty=easy");

        $response->assertOk();
        $this->assertCount(2, $response->json('data.questions'));
    }
}
