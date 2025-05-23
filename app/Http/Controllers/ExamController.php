<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Traits\ApiResponse;
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
}
