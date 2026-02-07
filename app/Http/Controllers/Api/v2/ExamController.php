<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\ExamIndexRequest;
use App\Http\Resources\v2\ExamDetailResource;
use App\Http\Resources\v2\ExamResource;
use App\Http\Resources\v2\SectionResource;
use App\Http\Resources\v2\SubjectResource;
use App\Models\Exam;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;

class ExamController extends BaseApiController
{
    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * Get all exams
     * GET /api/v2/exams
     *
     * @param ExamIndexRequest $request
     * @return JsonResponse
     */
    public function index(ExamIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $exams = $this->examService->getAllExams($filters);

            return $this->successResponse([
                'exams' => ExamResource::collection($exams)
            ], 'Exams retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exams: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get exam details with unified structure (without questions)
     * GET /api/v2/exams/{exam}
     *
     * @param Exam $exam
     * @return JsonResponse
     */
    public function show(Exam $exam): JsonResponse
    {
        try {
            $examDetails = $this->examService->getExamDetails($exam);

            return $this->successResponse([
                'exam' => new ExamDetailResource($examDetails)
            ], 'Exam details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exam details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all subjects for an exam
     * GET /api/v2/exams/{exam}/subjects
     *
     * @param Exam $exam
     * @return JsonResponse
     */
    public function subjects(Exam $exam): JsonResponse
    {
        try {
            $subjects = $this->examService->getExamSubjects($exam);

            return $this->successResponse([
                'subjects' => SubjectResource::collection($subjects)
            ], 'Exam subjects retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exam subjects: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all sections for an exam
     * GET /api/v2/exams/{exam}/sections
     *
     * @param Exam $exam
     * @return JsonResponse
     */
    public function sections(Exam $exam): JsonResponse
    {
        try {
            $sections = $this->examService->getExamSections($exam);

            return $this->successResponse([
                'sections' => SectionResource::collection($sections)
            ], 'Exam sections retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve exam sections: ' . $e->getMessage(), 500);
        }
    }
}
