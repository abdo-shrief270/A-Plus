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
     * Create Single Student (إضافة حساب طالب واحد)
     * 
     * يتيح للمستخدم (مدير المدرسة أو ولي الأمر) إنشاء حساب طالب جديد وإضافته إلى شبكته (Network) تلقائياً.
     * يجب توفير بيانات الطالب الأساسية.
     *
     * @bodyParam name string required اسم الطالب بالكامل. Example: أحمد محمد
     * @bodyParam user_name string required اسم المستخدم الفريد. Example: ahmed2024
     * @bodyParam password string required كلمة المرور. Example: SecurePass123!
     * @bodyParam password_confirmation string required تأكيد كلمة المرور. Example: SecurePass123!
     * @bodyParam gender string required نوع الجنس (`male` أو `female`). Example: male
     * @bodyParam exam_id integer required معرف المرحلة الدراسية/الامتحان. Example: 2
     *
     * @group Dashboard / Users Management (إدارة المستخدمين)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array}
     * @response 400 array{status: int, message: string}
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
     * Bulk Create Students (JOSN) (إضافة مجموعة طلاب كبيانات JSON)
     * 
     * ينشئ حسابات عدة طلاب دفعة واحدة عبر تمرير مصفوفة `students` تضم كائنات (Objects) ببيانات كل طالب.
     * مفيد إذا كانت الواجهة الأمامية تقوم بقراءة ملف إكسل وتحويله إلى JSON قبل الإرسال.
     * سيرجع النظام استجابة توضح عدد من تم قبولهم وإنشاؤهم وعدد من فشلت محاولة إنشائهم (بسبب تكرار اسم المستخدم مثلاً).
     *
     * @bodyParam students array required مصفوفة تضم كائنات الطلاب.
     *
     * @group Dashboard / Users Management (إدارة المستخدمين)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{total_created: int, total_failed: int}}
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

        return $this->successResponse($result, $message);
    }

    /**
     * Import Students from Excel/CSV (استيراد الطلاب من ملفات الجداول)
     * 
     * يقوم بتحليل ملف بصيغة `.xls`, `.xlsx`, أو `.csv` مُرفق بالطب واستخراج بيانات الطلاب وإنشاء حساباتهم.
     * الملف يجب أن يكون مُنسقاً بأعمدة متعارف عليها (Name, UserName, Password, ExamID, Gender).
     * 
     * @bodyParam file file required ملف الجداول المُراد استيراده.
     *
     * @group Dashboard / Users Management (إدارة المستخدمين)
     * @unauthenticated false
     *
     * @response 200 array{status: int, message: string, data: array{total_created: int, total_failed: int}}
     * @response 400 array{status: int, message: string}
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
