<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\Seller\SellerNotificationResource;
use App\Models\SellerNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = SellerNotification::query()
            ->where('user_id', $user->id)
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->query('type'));
            })
            ->when($request->filled('is_read'), function ($query) use ($request) {
                $isRead = $request->query('is_read') === 'true';
                $query->where('is_read', $isRead);
            })
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => [
                'notifications' => SellerNotificationResource::collection($notifications->getCollection()),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
                'summary' => [
                    'unread_count' => SellerNotification::where('user_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                ],
            ],
        ]);
    }

    public function markAsRead(Request $request, SellerNotification $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'code' => 'UNAUTHORIZED',
            ], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => [
                'notification' => SellerNotificationResource::make($notification),
            ],
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updated = SellerNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data' => [
                'marked_count' => $updated,
            ],
        ]);
    }

    public function destroy(Request $request, SellerNotification $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($notification->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
                'code' => 'UNAUTHORIZED',
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }
}
