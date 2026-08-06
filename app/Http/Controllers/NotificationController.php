<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }
            $notifications = Notification::with('sender')->where('receiver_id', $user->uuid)->latest()->limit(50)->get();
             $data = $notifications->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'name' => $item->sender ? $item->sender->full_name : 'System',
                    'message' => $item->message,
                    'time' => $this->formatTime($item->created_at),
                    'created_at' => $item->created_at,
                    'unread' => ! $item->is_read,
                    'avatar' => $this->resolveUserImageUrl(
                        optional($item->sender)->image
                    ),
                    'type' => $item->type,
                    'reference_id' => $item->reference_id,
                    'reference_type' => $item->reference_type,
                ];
            });

            $unreadCount = Notification::where('receiver_id', $user->uuid)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'status' => true,
                'message' => 'Notifications fetched successfully',
                'unread_count' => $unreadCount,
                'data' => $data,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead(Request $request, $id)
    {
        try {
            $user = $request->user();
            $notification = Notification::where('id', $id)->where('receiver_id', $user->uuid)->firstOrFail();
            // echo dd($notification);
            $notification->update(['is_read' => true]);
            return response()->json([
                'status'  => true,
                'message' => 'Notification marked as read',
            ]);
         } 
         catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
         }
    }

    public function markAllAsRead(Request $request)
    {
        try {
            $user = $request->user();
            Notification::where('receiver_id', $user->uuid)->where('is_read', false)->update(['is_read' => true,]);
            
            return response()->json([
                'status' => true,
                'message' => 'All notifications marked as read',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $notification = Notification::where('id', $id)->where('receiver_id', $user->uuid)->first();
            if (! $notification) {
                return response()->json(['status' => false, 'message' => 'Notification not found.'], 404);
            }
            $notification->delete();
            return response()->json(['status' => true, 'message' => 'Notification deleted.'], 200);

        } catch (\Throwable $e) {
            return response()->json(['status' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    private function formatTime($date)
    {
        return Carbon::parse($date)->diffForHumans(['short' => true,]);
    }

    private function resolveUserImageUrl(?string $storedPath): ?string
    {
        if (empty($storedPath)) {
            return null;
        }

        if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
            return $storedPath;
        }

        $relative = ltrim(str_replace('/storage/', '', $storedPath), '/');

        if ($storageBase = env('STORAGE_BASE_URL')) {
            return rtrim($storageBase, '/') . '/' . $relative;
        }

        if (request()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/') . '/storage/' . $relative;
        }

        return rtrim(env('APP_URL', 'http://127.0.0.1:8000'), '/') . '/storage/' . $relative;
    }
}
