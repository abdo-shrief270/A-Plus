<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\v2\CreateStudentRequest;
use App\Http\Requests\Api\v2\BulkCreateStudentsRequest;
use App\Http\Requests\Api\v2\ImportStudentsFileRequest;
use App\Http\Resources\v2\StudentResource;
use App\Services\StudentImportService;
use Illuminate\Http\JsonResponse;

class StudentImportController extends BaseApiController
{
    public function __construct(
        protected StudentImportService $studentImportService
    ) {
    }

    /**
     * Create a single student.
     *
     * @param CreateStudentRequest $request
     * @return JsonResponse
     */
    public function store(CreateStudentRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        try {
            $student = $this->studentImportService->importSingle(
                $request->validated(),
                $user
            );

            return $this->successResponse(
                new StudentResource($student),
                'Student created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create student: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Create multiple students from JSON array.
     *
     * @param BulkCreateStudentsRequest $request
     * @return JsonResponse
     */
    public function bulkStore(BulkCreateStudentsRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        $result = $this->studentImportService->importBulk(
            $request->input('students'),
            $user
        );

        $message = sprintf(
            'Bulk import completed: %d created, %d failed',
            $result['total_created'],
            $result['total_failed']
        );
        dump($result); // DEBUG

        return $this->successResponse($result, $message);
    }

    /**
     * Import students from uploaded file.
     *
     * @param ImportStudentsFileRequest $request
     * @return JsonResponse
     */
    public function importFile(ImportStudentsFileRequest $request): JsonResponse
    {
        $user = auth('api')->user();

        try {
            $result = $this->studentImportService->importFromFile(
                $request->file('file'),
                $user
            );

            $message = sprintf(
                'File import completed: %d created, %d failed',
                $result['total_created'],
                $result['total_failed']
            );

            return $this->successResponse($result, $message);
        } catch (\Exception $e) {
            return $this->errorResponse('File import failed: ' . $e->getMessage(), 400);
        }
    }
}
