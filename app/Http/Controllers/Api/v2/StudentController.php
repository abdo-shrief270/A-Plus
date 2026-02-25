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
     * List Students
     * 
     * Retrieve a paginated list of students. Automatically scopes the data:
     * - Parents see only their own children.
     * - Schools see only their enrolled students.
     * Supports filtering by search term (name/email/phone), league_id, or exam_id.
     *
     * @unauthenticated false
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
     * Get Student Profile
     * 
     * Retrieve the detailed profile of a specific student, including their linked attributes,
     * current score, and statistics.
     *
     * @unauthenticated false
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
     * Update Student Profile
     * 
     * Modify the details of a specific student's profile. Validates and updates
     * basic user data (name, email, phone) as well as student-specific data (exam_id, id_number).
     *
     * @unauthenticated false
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
     * Request Student Deletion
     * 
     * Submits a formal request to delete a student account. This does not immediately
     * delete the student; instead, it creates a `DeletionRequest` that site administrators
     * must approve in the central admin panel.
     *
     * @unauthenticated false
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
