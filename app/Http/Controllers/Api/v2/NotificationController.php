<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends BaseApiController
{
    /**
     * List the authenticated user's recent notifications + unread count.
     *
     * @group Notifications (الإشعارات)
     * @unauthenticated false
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));

        $query = $user->notifications()->orderByDesc('created_at');
        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $page = $query->paginate($perPage);

        return $this->successResponse([
            'unread_count' => (int) $user->unreadNotifications()->count(),
            'notifications' => collect($page->items())->map(fn ($n) => $this->serialize($n)),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
        ], 'Notifications retrieved');
    }

    /**
     * Mark one notification as read.
     *
     * @group Notifications (الإشعارات)
     * @unauthenticated false
     */
    public function markRead(string $id): JsonResponse
    {
        $user = auth('api')->user();
        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return $this->errorResponse('Notification not found', 404);
        }
        if (!$notification->read_at) {
            $notification->markAsRead();
        }
        return $this->successResponse(
            $this->serialize($notification->fresh()),
            'Marked as read'
        );
    }

    /**
     * Mark every notification as read.
     *
     * @group Notifications (الإشعارات)
     * @unauthenticated false
     */
    public function markAllRead(): JsonResponse
    {
        $user = auth('api')->user();
        $user->unreadNotifications->markAsRead();
        return $this->successResponse(
            ['unread_count' => 0],
            'All marked as read'
        );
    }

    private function serialize($notification): array
    {
        $data = $notification->data ?? [];
        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? '—',
            'description' => $data['description'] ?? null,
            'link' => $data['link'] ?? null,
            'color' => $data['color'] ?? 'info',
            'icon' => $data['icon'] ?? 'i-heroicons-bell',
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
