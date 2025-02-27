<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\ClientDatabaseManager;
use App\Models\ClientDBUser;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }

        try {
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)->first();

            // Validate request
            $validated = $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:client.notifications,id'
            ]);

            // Update notifications
            Notification::whereIn('id', $validated['notification_ids'])
                ->where('client_dbusers_id', $clientDbUser->id)
                ->update(['is_read' => true]);

            return response()->json([
                'data' => null,
                'status_code' => 200,
                'message' => 'Notifications marked as read successfully',
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to mark notifications as read',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAllAsRead(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }

        try {
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)->first();

            // Update all unread notifications for the user
            Notification::where('client_dbusers_id', $clientDbUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return response()->json([
                'data' => null,
                'status_code' => 200,
                'message' => 'All notifications of ' . $clientDbUser->name . ' marked as read',
                'success' => true,

            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to mark all notifications as read',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // You should also modify your existing fetchNotifications method to include read status
    public function fetchNotifications()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'data' => null,
                'status_code' => 401,
                'message' => 'Unauthorized',
                'success' => false,
            ], 401);
        }

        try {
            ClientDatabaseManager::setConnection($user->database_name);
            $clientDbUser = ClientDBUser::where('email', $user->email)->first();

            $notifications = Notification::where('client_dbusers_id', $clientDbUser->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'message' => $notification->message,
                        'is_read' => (bool) $notification->is_read,
                        'created_at' => $notification->created_at,
                    ];
                });

            $unreadCount = $notifications->where('is_read', false)->count();

            return response()->json([
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unreadCount
                ],
                'status_code' => 200,
                'message' => 'Notifications fetched successfully',
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'status_code' => 500,
                'message' => 'Failed to fetch notifications',
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 