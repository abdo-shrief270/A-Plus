<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamSubject;
use App\Models\SectionCategory;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
    use ApiResponse;
    public function index()
    {

        try {
            $exams =Exam::get();
            return $this->apiResponse(200, 'Exams Returned Successfully', null, [
                'exams'=>$exams->makeHidden(['created_at','updated_at'])
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Exams Returning failed: ' . $e->getMessage());
        }
    }


    public function categories()
    {
        try {
            $exam = Auth::user()?->student?->exam;
            if($exam->subjects->count()>0 && $exam->sections->count()>0  ){
                return $this->apiResponse(200, 'Exam Data Returned Successfully', null, [
                    'subjects'=>$exam->subjects,
                    'sections'=>$exam->sectionsCategories
                ]);
            }elseif($exam->subjects->count()>0){
                return $this->apiResponse(200, 'Exam Subjects Returned Successfully', null, [
                    'subjects'=>$exam->subjects
                ]);
            }else{
                return $this->apiResponse(200, 'Exam Sections Returned Successfully', null, [
                    'sections'=>$exam->sectionsCategories
                ]);
            }

        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Exams Returning failed: ' . $e->getMessage());
        }
    }
    public function subjectData(ExamSubject $subject)
    {

        try {
            return $this->apiResponse(200, 'Questions Returned Successfully', null, [
                'questions'=>$subject->questions->select('id','text','image_path')
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Questions Returning failed: ' . $e->getMessage());
        }
    }

    public function categoryData(SectionCategory $category)
    {

        try {
            return $this->apiResponse(200, 'Questions Returned Successfully', null, [
                'questions'=>$category->questions->select('id','text','image_path')
            ]);
        } catch (\Exception $e) {
            return $this->apiResponse(500, 'Questions Returning failed: ' . $e->getMessage());
        }
    }
}
