<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\v2\EnrollmentResource;

class EnrollmentController extends BaseApiController
{
    /**
     * Get Enrollments List (قائمة الاشتراكات بالدورات)
     *
     * يجلب قائمة بجميع الدورات المشترك بها الطلاب بداخل النظام.
     * يتم فلترة هذه الاستجابة تلقائياً لتكون آمنة:
     * - ولي الأمر سيشاهد فقط دورات أبنائه.
     * - المدرسة ستشاهد فقط دورات طلابها.
     * - الطالب سيشاهد الاشتراكات الخاصة به.
     *
     * @queryParam per_page integer optional عدد العناصر في الصفحة. Default: 15
     *
     * @group Dashboard / Enrollments (الاشتراكات)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array}
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $query = Enrollment::query()
            ->with(['course', 'user']) // Load the course and the student user
            ->orderByDesc('created_at');

        if ($user) {
            if ($user->type === 'school') {
                $schoolId = $user->studentSchool?->school_id;
                if ($schoolId) {
                    $query->whereHas('user.student.studentSchool', function ($q) use ($schoolId) {
                        $q->where('school_id', $schoolId);
                    });
                } else {
                    // Fallback to empty if school has no valid ID
                    $query->whereRaw('1 = 0');
                }
            } elseif ($user->type === 'parent') {
                $studentIds = $user->studentParent()->pluck('student_id')->toArray();
                $query->whereHas('user.student', function ($q) use ($studentIds) {
                    $q->whereIn('id', $studentIds);
                });
            } elseif ($user->type === 'student') {
                $query->where('user_id', $user->id);
            }
        }

        $perPage = $request->input('per_page', 15);
        $enrollments = $query->paginate($perPage);

        return $this->successResponse(
            $enrollments->toArray(), // Returns raw paginated array for simpler parsing on frontend as a table
            'Enrollments retrieved successfully'
        );
    }
}
