<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\v2\UpdateStudentRequest;
use App\Http\Resources\v2\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends BaseApiController
{
    public function __construct(
        protected StudentService $studentService
    ) {
    }

    /**
     * List students ordered by newest first.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $filters = $request->only(['search', 'league_id', 'exam_id', 'per_page']);
        $students = $this->studentService->list($user, $filters);

        return $this->successResponse(
            StudentResource::collection($students)->response()->getData(true),
            'Students retrieved successfully'
        );
    }

    /**
     * Get a single student.
     *
     * @param Student $student
     * @return JsonResponse
     */
    public function show(Student $student): JsonResponse
    {
        $student = $this->studentService->show($student->id);

        if (!$student) {
            return $this->errorResponse('Student not found', 404);
        }

        return $this->successResponse(
            new StudentResource($student),
            'Student retrieved successfully'
        );
    }

    /**
     * Update a student.
     *
     * @param UpdateStudentRequest $request
     * @param Student $student
     * @return JsonResponse
     */
    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $updated = $this->studentService->update($student->id, $request->validated());

        if (!$updated) {
            return $this->errorResponse('Student not found', 404);
        }

        return $this->successResponse(
            new StudentResource($updated),
            'Student updated successfully'
        );
    }

    /**
     * Request deletion of a student (requires admin approval).
     *
     * @param Request $request
     * @param Student $student
     * @return JsonResponse
     */
    public function destroy(Request $request, Student $student): JsonResponse
    {
        $user = auth('api')->user();

        $deletionRequest = $this->studentService->requestDeletion(
            $student->id,
            $user->id,
            $request->input('reason')
        );

        return $this->successResponse(
            [
                'deletion_request_id' => $deletionRequest->id,
                'status' => 'pending',
                'message' => 'Deletion request submitted for admin approval',
            ],
            'Deletion request submitted successfully'
        );
    }
}
