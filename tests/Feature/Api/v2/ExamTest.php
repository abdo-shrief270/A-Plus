<?php

namespace Tests\Feature\Api\v2;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\SectionCategory;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_exams()
    {
        Exam::factory()->count(3)->create(['active' => true]);
        Exam::factory()->create(['active' => false]);

        $response = $this->getJson('/api/v2/exams');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'exams' => [
                        '*' => ['id', 'name', 'active']
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_exams_by_active_status()
    {
        Exam::factory()->count(2)->create(['active' => true]);
        Exam::factory()->create(['active' => false]);

        $response = $this->getJson('/api/v2/exams?active=1');

        $response->assertOk();
        $data = $response->json('data.exams');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function it_can_show_exam_with_subject_based_structure()
    {
        $exam = Exam::factory()->create();
        $subject = ExamSubject::factory()->create(['exam_id' => $exam->id]);
        Question::factory()->count(5)->create()->each(function ($question) use ($subject) {
            $question->subjects()->attach($subject->id);
        });

        $response = $this->getJson("/api/v2/exams/{$exam->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'exam' => [
                        'id',
                        'name',
                        'active',
                        'type',
                        'structure' => [
                            'subjects' => [
                                '*' => ['id', 'name', 'description', 'questions_count']
                            ]
                        ]
                    ]
                ]
            ]);

        $this->assertEquals('subject-based', $response->json('data.exam.type'));
        $this->assertEquals(5, $response->json('data.exam.structure.subjects.0.questions_count'));
    }

    /** @test */
    public function it_can_show_exam_with_section_based_structure()
    {
        $exam = Exam::factory()->create();
        $section = ExamSection::factory()->create(['exam_id' => $exam->id]);
        $category = SectionCategory::factory()->create(['exam_section_id' => $section->id]);
        Question::factory()->count(3)->create()->each(function ($question) use ($category) {
            $question->categories()->attach($category->id);
        });

        $response = $this->getJson("/api/v2/exams/{$exam->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'exam' => [
                        'id',
                        'name',
                        'type',
                        'structure' => [
                            'sections' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'categories' => [
                                        '*' => ['id', 'name', 'description', 'questions_count']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $this->assertEquals('section-based', $response->json('data.exam.type'));
    }

    /** @test */
    public function it_can_get_exam_subjects()
    {
        $exam = Exam::factory()->create();
        ExamSubject::factory()->count(3)->create(['exam_id' => $exam->id]);

        $response = $this->getJson("/api/v2/exams/{$exam->id}/subjects");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'subjects' => [
                        '*' => ['id', 'name', 'description', 'questions_count']
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('data.subjects'));
    }

    /** @test */
    public function it_can_get_exam_sections()
    {
        $exam = Exam::factory()->create();
        $section = ExamSection::factory()->create(['exam_id' => $exam->id]);
        SectionCategory::factory()->count(2)->create(['exam_section_id' => $section->id]);

        $response = $this->getJson("/api/v2/exams/{$exam->id}/sections");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'sections' => [
                        '*' => [
                            'id',
                            'name',
                            'categories' => [
                                '*' => ['id', 'name', 'description', 'questions_count']
                            ]
                        ]
                    ]
                ]
            ]);
    }
}
