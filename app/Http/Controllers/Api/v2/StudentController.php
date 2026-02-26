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
     * List Students (قائمة الطلاب)
     * 
     * يجلب قائمة مقسمة بصفحات (Paginated) ببيانات الطلاب. 
     * هذه النهاية الطرفية آمنة ومدعمة بالصلاحيات التلقائية:
     * - إذا كان المستخدم (ولي أمر)، سيرى فقط أبناءه المسجلين.
     * - إذا كان المستخدم (مدرسة/مدير)، سيرى طلاب مدرسته فقط.
     * من خلال هذا المسار يمكن للواجهة الأمامية بناء جداول الفلترة والبحث للطلاب.
     *
     * @queryParam search string optional كلمة بحث للبحث التلقائي بالاسم، الهاتف، أو إيميل الطالب. Example: ahmed
     * @queryParam league_id integer optional للفلترة بناءً على الدوري أو المستوى الذي وصله الطالب. Example: 2
     * @queryParam exam_id integer optional للفلترة بناءً على المرحلة الدراسية المحددة للطالب. Example: 1
     * @queryParam per_page integer optional عدد العناصر في الصفحة الواحدة (الافتراضي 15). Example: 10
     *
     * @group Dashboard / Students (الطلاب)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{data: array, meta: array}}
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
     * Get Student Profile (بيانات الطالب)
     * 
     * يجلب ملف البيانات الكامل لطالب معين. سيتم إرفاق علاقات الجدول الخاصة بالطالب مثل المدرسة، الدوري (League)، و المرحلة الدراسية (`exam`).
     * يستخدم هذا المسار عادة في صفحة (عرض تفاصيل حساب الطالب).
     *
     * @pathParam student integer required المعرف الفريد للطالب (ID). Example: 5
     *
     * @group Dashboard / Students (الطلاب)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array}
     * @response 404 array{status: int, message: string} - الطالب غير موجود
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
     * Update Student Profile (تعديل بيانات الطالب)
     * 
     * يتطلب هذا المسار صلاحيات وصول كافية (مثل كون المستخدم هو ولي الأمر الخاص بالطالب أو آدمن).
     * يمكن من خلاله تحديث بيانات المستخدم الأساسية (اسم، هاتف، بريد) بالإضافة لبيانات الطالب (الصف المدرسي `exam_id`).
     *
     * @pathParam student integer required المعرف الفريد للطالب المطلوب تعديله. Example: 5
     * 
     * @bodyParam exam_id integer optional الصف/المرحلة الدراسية للطالب. Example: 1
     * @bodyParam id_number string optional الرقم القومي أو المعرف المدني. Example: 2990101010101
     * @bodyParam name string optional الاسم الكامل للتحديث. Example: أحمد حسن
     * @bodyParam email string optional عنوان البريد الإلكتروني. Example: user@example.com
     * @bodyParam phone string optional رقم الهاتف. Example: 01012345678
     *
     * @group Dashboard / Students (الطلاب)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array}
     * @response 404 array{status: int, message: string}
     * @response 422 array{status: int, message: string, errors: array} - البيانات المدخلة غير صحيحة
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
     * Request Student Deletion (طلب حذف حساب طالب)
     * 
     * لأغراض الحماية وعدم مسح بيانات مالية أو أرصدة بالخطأ، هذا المسار لا يقوم فعليًا بمسح الـ Record من الداتابيز،
     * بل يقدم (Deletion Request) طلب حذف لإدارة الدعم لمراجعته. سيتم تعليق الحساب ريثما تتم الموافقة.
     *
     * @pathParam student integer required المعرف الفريد للطالب المُراد تقديم طلب بحذفه. Example: 5
     * 
     * @bodyParam reason string optional سبب الرغبة في حذف هذا الحساب (لأجل قسم الجودة/الدعم). Example: الطالب تخرج العام الماضي ولم يعد بحاجة للمنصة.
     *
     * @group Dashboard / Students (الطلاب)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{deletion_request_id: int, status: string, message: string}}
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
