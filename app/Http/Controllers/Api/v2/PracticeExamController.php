<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\v2\PracticeExamIndexRequest;
use App\Http\Resources\v2\PracticeExamResource;
use App\Models\PracticeExam;
use App\Services\PracticeExamService;
use Illuminate\Http\JsonResponse;

class PracticeExamController extends BaseApiController
{
    protected PracticeExamService $practiceExamService;

    public function __construct(PracticeExamService $practiceExamService)
    {
        $this->practiceExamService = $practiceExamService;
    }

    /**
     * Get Practice Exams
     * 
     * Retrieve a list of practice exams available for students to test their knowledge.
     * By default, this does not paginate unless explicitly requested.
     *
     * @unauthenticated false
     * @param PracticeExamIndexRequest $request
     * @return JsonResponse
     */
    public function index(PracticeExamIndexRequest $request): JsonResponse
    {
        try {
            $filters = $request->validated();
            $filters['paginate'] = $filters['paginate'] ?? false; // Don't paginate by default for practice exams

            $practiceExams = $this->practiceExamService->getAllPracticeExams($filters);

            if ($practiceExams instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                return $this->successResponse([
                    'practice_exams' => PracticeExamResource::collection($practiceExams->items()),
                    'pagination' => [
                        'current_page' => $practiceExams->currentPage(),
                        'per_page' => $practiceExams->perPage(),
                        'total' => $practiceExams->total(),
                        'last_page' => $practiceExams->lastPage(),
                    ]
                ], 'Practice exams retrieved successfully');
            }

            return $this->successResponse([
                'practice_exams' => PracticeExamResource::collection($practiceExams)
            ], 'Practice exams retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve practice exams: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Practice Exam Details
     * 
     * Retrieve a specific practice exam along with all of its associated questions and choices.
     * Used typically when a student actually starts a practice exam session.
     *
     * @unauthenticated false
     * @param PracticeExam $practiceExam
     * @return JsonResponse
     */
    public function show(PracticeExam $practiceExam): JsonResponse
    {
        try {
            $practiceExamWithDetails = $this->practiceExamService->getPracticeExamDetails($practiceExam);

            return $this->successResponse([
                'practice_exam' => new PracticeExamResource($practiceExamWithDetails)
            ], 'Practice exam details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve practice exam details: ' . $e->getMessage(), 500);
        }
    }
}
