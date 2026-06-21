<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\v2\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends BaseApiController
{
    /**
     * List Published Pages (الصفحات الثابتة)
     *
     * @group Pages (الصفحات)
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $pages = Page::published()
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'icon']);

        return $this->successResponse(
            $pages,
            'Pages retrieved successfully'
        );
    }

    /**
     * Get Page by Slug (محتوى صفحة)
     *
     * @pathParam slug string required Example: about
     *
     * @group Pages (الصفحات)
     * @unauthenticated
     */
    public function show(string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->first();

        if (!$page) {
            return $this->errorResponse('Page not found', 404);
        }

        return $this->successResponse(
            new PageResource($page),
            'Page retrieved successfully'
        );
    }
}
