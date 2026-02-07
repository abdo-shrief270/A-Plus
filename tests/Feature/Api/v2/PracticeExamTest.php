<?php

namespace Tests\Feature\Api\v2;

use App\Models\Answer;
use App\Models\PracticeExam;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeExamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_practice_exams()
    {
        $practiceExam1 = PracticeExam::factory()->create(['is_active' => true]);
        $practiceExam2 = PracticeExam::factory()->create(['is_active' => true]);
        $practiceExam3 = PracticeExam::factory()->create(['is_active' => false]);

        // Create questions for practice exams
        Question::factory()->count(3)->create(['practice_exam_id' => $practiceExam1->id]);
        Question::factory()->count(5)->create(['practice_exam_id' => $practiceExam2->id]);

        $response = $this->getJson('/api/v2/practice-exams');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'practice_exams' => [
                        '*' => [
                            'id',
                            'title',
                            'is_active',
                            'questions_count',
                            'questions' => [
                                '*' => ['id', 'text', 'answers']
                            ]
                        ]
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data.practice_exams'));
    }

    /** @test */
    public function it_can_filter_practice_exams_by_active_status()
    {
        PracticeExam::factory()->count(2)->create(['is_active' => true]);
        PracticeExam::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v2/practice-exams?is_active=1');

        $response->assertOk();
        $data = $response->json('data.practice_exams');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function it_can_show_practice_exam_with_questions()
    {
        $practiceExam = PracticeExam::factory()->create(['is_active' => true]);
        $questions = Question::factory()->count(10)->create(['practice_exam_id' => $practiceExam->id]);

        foreach ($questions as $question) {
            Answer::factory()->count(4)->create(['question_id' => $question->id]);
        }

        $response = $this->getJson("/api/v2/practice-exams/{$practiceExam->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'practice_exam' => [
                        'id',
                        'title',
                        'is_active',
                        'questions_count',
                        'questions' => [
                            '*' => [
                                'id',
                                'text',
                                'difficulty',
                                'is_new',
                                'explanation',
                                'answers' => [
                                    '*' => ['id', 'text', 'is_correct', 'order']
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->assertEquals(10, $response->json('data.practice_exam.questions_count'));
        $this->assertCount(10, $response->json('data.practice_exam.questions'));
    }

    /** @test */
    public function it_returns_404_for_non_existent_practice_exam()
    {
        $response = $this->getJson('/api/v2/practice-exams/999');

        $response->assertStatus(200)
            ->assertJson(['status' => 404]);
    }

    /** @test */
    public function practice_exam_includes_all_question_details()
    {
        $practiceExam = PracticeExam::factory()->create();
        $question = Question::factory()->create([
            'practice_exam_id' => $practiceExam->id,
            'text' => 'Sample question',
            'difficulty' => 'medium',
            'is_new' => true,
            'explanation_text' => 'Sample explanation'
        ]);
        Answer::factory()->count(4)->create(['question_id' => $question->id]);

        $response = $this->getJson("/api/v2/practice-exams/{$practiceExam->id}");

        $response->assertOk();

        $questionData = $response->json('data.practice_exam.questions.0');
        $this->assertEquals('Sample question', $questionData['text']);
        $this->assertEquals('medium', $questionData['difficulty']);
        $this->assertTrue($questionData['is_new']);
        $this->assertEquals('Sample explanation', $questionData['explanation']['text']);
        $this->assertCount(4, $questionData['answers']);
    }
}
