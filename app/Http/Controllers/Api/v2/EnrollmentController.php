<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\v2\CreateEnrollmentsRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSchool;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            ->with(['course', 'user.student'])
            ->orderByDesc('created_at');

        if ($user) {
            if ($user->type === 'school') {
                $schoolId = $user->school?->id;
                if ($schoolId) {
                    $query->whereHas('user.student.studentSchool', function ($q) use ($schoolId) {
                        $q->where('school_id', $schoolId);
                    });
                } else {
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
            $enrollments->toArray(),
            'Enrollments retrieved successfully'
        );
    }

    /**
     * Enroll Students in a Course (اشتراك الطلاب في كورس)
     *
     * يقوم بإنشاء عدة اشتراكات (Enrollment) دفعة واحدة لمجموعة من الطلاب في كورس محدد.
     * يقتصر السماح على الطلاب التابعين للمستخدم (أبناء ولي الأمر، أو طلاب المدرسة).
     * يتم تخطي الطلاب المسجلين مسبقاً في الكورس مع إرجاع تفاصيل العملية.
     *
     * @bodyParam course_id integer required معرف الكورس. Example: 3
     * @bodyParam student_ids array required مصفوفة بمعرفات الطلاب المراد اشتراكهم.
     *
     * @group Dashboard / Enrollments (الاشتراكات)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{total_created: int, total_skipped: int, created: array, skipped: array}}
     * @response 403 array{status: int, message: string} - بعض الطلاب خارج صلاحية المستخدم
     */
    public function store(CreateEnrollmentsRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $courseId = (int) $request->input('course_id');
        $studentIds = array_map('intval', $request->input('student_ids', []));

        $accessibleIds = $this->scopedStudentIds($user);
        $unauthorized = array_diff($studentIds, $accessibleIds);
        if (!empty($unauthorized)) {
            return $this->errorResponse('Some students are not under your account', 403);
        }

        $course = Course::find($courseId);
        if (!$course) {
            return $this->errorResponse('Course not found', 404);
        }

        $userIdMap = Student::whereIn('id', $studentIds)
            ->pluck('user_id', 'id')
            ->all();

        return DB::transaction(function () use ($user, $course, $studentIds, $userIdMap) {
            $created = [];
            $skipped = [];

            foreach ($studentIds as $sid) {
                $uid = $userIdMap[$sid] ?? null;
                if (!$uid) {
                    $skipped[] = ['student_id' => $sid, 'reason' => 'student not found'];
                    continue;
                }

                $hasActive = Enrollment::where('user_id', $uid)
                    ->where('course_id', $course->id)
                    ->where('status', 'active')
                    ->exists();
                if ($hasActive) {
                    $skipped[] = ['student_id' => $sid, 'reason' => 'already enrolled'];
                    continue;
                }

                $enrollment = Enrollment::create([
                    'user_id' => $uid,
                    'course_id' => $course->id,
                    // Pending until payment is confirmed. Free courses are activated immediately.
                    'status' => $course->price > 0 ? 'pending' : 'active',
                    'enrolled_at' => now(),
                    'created_by' => $user->type ?? 'system',
                ]);
                $created[] = ['student_id' => $sid, 'enrollment_id' => $enrollment->id];
            }

            $payment = null;
            $totalAmount = (float) $course->price * count($created);

            if (!empty($created) && $totalAmount > 0) {
                $payment = Payment::create([
                    'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                    'user_id' => $user->id,
                    'enrollment_id' => $created[0]['enrollment_id'],
                    'amount' => $totalAmount,
                    'description' => 'اشتراك في كورس: ' . $course->title,
                    'currency' => 'SAR',
                    'payment_method' => 'pending',
                    'status' => 'pending',
                    'metadata' => [
                        'kind' => 'enrollment',
                        'course_id' => $course->id,
                        'enrollment_ids' => array_column($created, 'enrollment_id'),
                    ],
                ]);
            }

            return $this->successResponse([
                'total_created' => count($created),
                'total_skipped' => count($skipped),
                'created' => $created,
                'skipped' => $skipped,
                'payment' => $payment ? [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                ] : null,
                'requires_payment' => $payment !== null,
            ], 'Enrollment processed');
        });
    }

    private function scopedStudentIds($user): array
    {
        if (!$user) return [];

        if ($user->type === 'parent') {
            return $user->studentParent()->pluck('student_id')->toArray();
        }

        if ($user->type === 'school') {
            $schoolId = $user->school?->id;
            if (!$schoolId) return [];
            return StudentSchool::where('school_id', $schoolId)
                ->pluck('student_id')
                ->toArray();
        }

        if ($user->type === 'student') {
            return $user->student?->id ? [$user->student->id] : [];
        }

        return [];
    }
}
