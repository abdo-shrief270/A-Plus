<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentDeletionRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentService
{
    /**
     * List students with pagination, ordered by newest first.
     *
     * @param User|null $user For scoping by school/parent
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function list(?User $user = null, array $filters = []): LengthAwarePaginator
    {
        $query = Student::query()
            ->with(['user', 'league', 'scores', 'wallet'])
            ->orderByDesc('created_at');

        // Scope by user type
        if ($user) {
            if ($user->type === 'school') {
                $schoolId = $user->studentSchool?->school_id;
                if ($schoolId) {
                    $query->whereHas('studentSchool', fn($q) => $q->where('school_id', $schoolId));
                }
            } elseif ($user->type === 'parent') {
                $studentIds = $user->studentParent()->pluck('student_id')->toArray();
                $query->whereIn('id', $studentIds);
            }
        }

        // Apply optional filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['league_id'])) {
            $query->where('current_league_id', $filters['league_id']);
        }

        if (!empty($filters['exam_id'])) {
            $query->where('exam_id', $filters['exam_id']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a single student with related data.
     *
     * @param int $studentId
     * @return Student|null
     */
    public function show(int $studentId): ?Student
    {
        return Student::with(['user', 'league', 'scores', 'wallet', 'exam'])
            ->find($studentId);
    }

    /**
     * Update a student's data.
     *
     * @param int $studentId
     * @param array $data
     * @return Student|null
     */
    public function update(int $studentId, array $data): ?Student
    {
        $student = Student::find($studentId);

        if (!$student) {
            return null;
        }

        // Update user data if provided
        if (isset($data['name']) || isset($data['email']) || isset($data['phone'])) {
            $student->user->update(array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]));
        }

        // Update student-specific data
        $student->update(array_filter([
            'exam_id' => $data['exam_id'] ?? null,
            'exam_date' => $data['exam_date'] ?? null,
            'id_number' => $data['id_number'] ?? null,
        ]));

        return $student->fresh(['user', 'league', 'scores', 'wallet']);
    }

    /**
     * Request deletion of a student (requires admin approval).
     *
     * @param int $studentId
     * @param int $requestedBy User ID who requested deletion
     * @param string|null $reason
     * @return StudentDeletionRequest
     */
    public function requestDeletion(int $studentId, int $requestedBy, ?string $reason = null): StudentDeletionRequest
    {
        return StudentDeletionRequest::create([
            'student_id' => $studentId,
            'requested_by' => $requestedBy,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }
}
