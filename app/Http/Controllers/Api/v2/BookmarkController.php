<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\QuestionDetailResource;
use App\Models\Bookmark;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags الإشارات المرجعية (Bookmarks)
 */
class BookmarkController extends BaseApiController
{
    /**
     * List My Bookmarks (قائمة المحفوظات)
     *
     * يعيد الأسئلة التي قام الطالب بحفظها (إشارة مرجعية).
     *
     * @queryParam per_page integer optional Default 15
     *
     * @group Bookmarks (المحفوظات)
     */
    public function index(Request $request): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Bookmarks are only available for students', Response::HTTP_FORBIDDEN);
        }

        $perPage = (int) $request->input('per_page', 15);

        $questions = $student->bookmarkedQuestions()
            ->with(['answers' => fn ($q) => $q->orderBy('order'), 'categories.section.exam', 'articles.category.section.exam', 'type'])
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse([
            'questions' => QuestionDetailResource::collection($questions->items()),
            'pagination' => [
                'current_page' => $questions->currentPage(),
                'per_page' => $questions->perPage(),
                'total' => $questions->total(),
                'last_page' => $questions->lastPage(),
            ],
        ], 'Bookmarks retrieved successfully');
    }

    /**
     * Toggle Bookmark (إضافة / إزالة إشارة مرجعية)
     *
     * يضيف الإشارة إن لم تكن موجودة، أو يزيلها إن كانت. يعيد حالة جديدة عبر `bookmarked`.
     *
     * @pathParam question integer required Example: 1
     *
     * @group Bookmarks (المحفوظات)
     *
     * @response 200 array{status: int, message: string, data: array{bookmarked: bool}}
     */
    public function toggle(Question $question): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Bookmarks are only available for students', Response::HTTP_FORBIDDEN);
        }

        $existing = Bookmark::where('student_id', $student->id)
            ->where('question_id', $question->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return $this->successResponse(['bookmarked' => false], 'Bookmark removed');
        }

        Bookmark::create([
            'student_id' => $student->id,
            'question_id' => $question->id,
        ]);

        return $this->successResponse(['bookmarked' => true], 'Bookmark added');
    }

    /**
     * Remove Bookmark (إزالة إشارة)
     *
     * @pathParam question integer required Example: 1
     *
     * @group Bookmarks (المحفوظات)
     */
    public function destroy(Question $question): JsonResponse
    {
        $student = $this->student();
        if (!$student) {
            return $this->errorResponse('Bookmarks are only available for students', Response::HTTP_FORBIDDEN);
        }

        Bookmark::where('student_id', $student->id)
            ->where('question_id', $question->id)
            ->delete();

        return $this->successResponse(['bookmarked' => false], 'Bookmark removed');
    }

    private function student()
    {
        $user = auth('api')->user();
        return $user?->student;
    }
}
