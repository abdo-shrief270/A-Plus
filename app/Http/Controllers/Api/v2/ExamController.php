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
     * Get All Exams
     * 
     * Retrieve a paginated list of all active exams on the platform.
     * Use this endpoint to list exams available for students to browse or enroll in.
     *
     * @unauthenticated false
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
     * Get Exam Details
     * 
     * Retrieve detailed information about a specific exam, including its structure
     * but excluding the vast array of questions.
     *
     * @unauthenticated false
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
     * Get Exam Subjects
     * 
     * Retrieve a list of all subjects associated with a specific exam.
     * Useful for building subject-filtering UIs.
     *
     * @unauthenticated false
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
     * Get Exam Sections
     * 
     * Retrieve a list of all hierarchical sections associated with a specific exam.
     *
     * @unauthenticated false
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
