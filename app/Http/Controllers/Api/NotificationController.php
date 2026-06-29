<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

#[OA\Tag(name: 'Notifications', description: 'Notification management endpoints')]
class NotificationController extends Controller
{
    #[OA\Get(
        path: '/api/notifications',
        tags: ['Notifications'],
        summary: 'Get all notifications for authenticated user',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Notifications retrieved successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Notification::where('user_id', $user->id);

        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        $notifications = $query->latest()->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => $notifications
        ]);
    }

    #[OA\Put(
        path: '/api/notifications/{id}/read',
        tags: ['Notifications'],
        summary: 'Mark a notification as read',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification marked as read'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function markAsRead($id): JsonResponse
    {
        $user = Auth::user();
        $notification = Notification::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $notification->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    #[OA\Put(
        path: '/api/notifications/read-all',
        tags: ['Notifications'],
        summary: 'Mark all notifications as read',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'All notifications marked as read'),
        ]
    )]
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'data' => null
        ]);
    }

    #[OA\Delete(
        path: '/api/notifications/{id}',
        tags: ['Notifications'],
        summary: 'Delete a notification',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification deleted successfully'),
            new OA\Response(response: 404, description: 'Notification not found'),
        ]
    )]
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $notification = Notification::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
            'data' => null
        ]);
    }
}
