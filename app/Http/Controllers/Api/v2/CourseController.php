<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends BaseApiController
{
    public function __construct(
        protected CourseService $courseService
    ) {
    }

    /**
     * List Courses (قائمة الكورسات)
     *
     * يجلب قائمة مقسمة بصفحات (Paginated) بالكورسات النشطة على المنصة. يدعم البحث والفلترة بحسب المرحلة الدراسية والمستوى.
     *
     * @queryParam search string optional كلمة بحث في عنوان أو وصف الكورس. Example: رياضيات
     * @queryParam exam_id integer optional فلترة الكورسات حسب الاختبار (المرحلة) المُرتبط بها. Example: 1
     * @queryParam level string optional مستوى الكورس (`beginner`, `intermediate`, `advanced`). Example: beginner
     * @queryParam per_page integer optional عدد العناصر في الصفحة (الافتراضي 12). Example: 12
     *
     * @group Dashboard / Courses (الكورسات)
     * @unauthenticated false
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'exam_id', 'level', 'per_page']);
        $courses = $this->courseService->list($filters);

        return $this->successResponse(
            CourseResource::collection($courses)->response()->getData(true),
            'Courses retrieved successfully'
        );
    }

    /**
     * Get Course Detail (تفاصيل كورس)
     *
     * يجلب بيانات تفصيلية لكورس محدد، مع المراحل (الاختبارات) المُرتبطة به وعدد المسجلين.
     *
     * @pathParam course integer required المعرف الفريد للكورس. Example: 3
     *
     * @group Dashboard / Courses (الكورسات)
     * @unauthenticated false
     */
    public function show(int $course): JsonResponse
    {
        $found = $this->courseService->show($course);

        if (!$found) {
            return $this->errorResponse('Course not found', 404);
        }

        return $this->successResponse(
            new CourseResource($found),
            'Course retrieved successfully'
        );
    }
}
