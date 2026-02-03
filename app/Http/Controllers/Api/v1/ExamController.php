<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\SectionCategory;
use App\Models\User;
// use App\Traits\ApiResponse; // Removed
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends BaseApiController
{
    // use ApiResponse; // Removed
    public function index()
    {

        try {
            $exams = Exam::active()->get();
            return $this->successResponse([
                'exams' => $exams->makeHidden(['created_at', 'updated_at'])
            ], 'Exams Returned Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Exams Returning failed: ' . $e->getMessage(), 500);
        }
    }


    public function categories()
    {
        try {
            $user = auth('api')->user() ?: auth('schools')->user();
            $exam = $user?->student?->exam;

            if (!$exam) {
                return $this->errorResponse('Exam Data Not Found', 404);
            }

            if ($exam->subjects->count() > 0 && $exam->sections->count() > 0) {
                return $this->successResponse([
                    'subjects' => $exam->subjects,
                    'sections' => $exam->sectionsCategories
                ], 'Exam Data Returned Successfully');
            } elseif ($exam->subjects->count() > 0) {
                return $this->successResponse([
                    'subjects' => $exam->subjects
                ], 'Exam Subjects Returned Successfully');
            } else {
                return $this->successResponse([
                    'sections' => $exam->sectionsCategories
                ], 'Exam Sections Returned Successfully');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Exams Returning failed: ' . $e->getMessage(), 500);
        }
    }
    public function subjectData(ExamSubject $subject)
    {

        try {
            return $this->successResponse([
                'questions' => $subject->questions->select('id', 'text', 'image_path')
            ], 'Questions Returned Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Questions Returning failed: ' . $e->getMessage(), 500);
        }
    }

    public function categoryData(SectionCategory $category)
    {

        try {
            return $this->successResponse([
                'questions' => $category->questions->select('id', 'text', 'image_path')
            ], 'Questions Returned Successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Questions Returning failed: ' . $e->getMessage(), 500);
        }
    }
}
