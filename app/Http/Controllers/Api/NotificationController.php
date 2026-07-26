<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\UserDevice;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponse;

    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'device_type' => 'required|in:android,ios',
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->first());
        }

        $user = $request->user();

        // Update or create device
        UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'device_id' => $request->device_id,
                'device_type' => $request->device_type,
                'fcm_token' => $request->fcm_token,
            ]
        );

        return $this->successWithoutDataResponse('Device registered successfully');
    }

    public function getNotifications(Request $request)
    {
        $user = auth()->user();
        $page = $request->page ?? 1;
        $limit = $request->limit ?? 15;

        $notifications = Notification::where('for', 'user')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($limit, ['*'], 'page', $page);

        $unreadCount = Notification::where('for', 'user')
            ->where('user_id', $user->id)
            ->where('read', 0)
            ->count();

        return $this->successResponse(data: NotificationResource::collection($notifications), message: 'Notifications fetched successfully', meta: [
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'nullable|exists:notifications,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if ($request->notification_id === null) {
            $user = auth()->user();
            Notification::where('for', 'user')
                ->where('user_id', $user->id)
                ->where('read', 0)
                ->update(['read' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'All notifications marked as read',
            ]);
        }

        $user = auth()->user();
        $notification = Notification::where('id', $request->notification_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found',
            ], 404);
        }

        $notification->update(['read' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read',
        ]);
    }
}
