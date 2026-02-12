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
                    'id'     => $item->id,
                    'name'   => $item->sender? $item->sender->full_name: 'System',
                    'message' => $item->message,
                    'time'   => $this->formatTime($item->created_at),
                    'unread' => ! $item->is_read,
                    'avatar' => $this->resolveUserImageUrl(
                        optional($item->sender)->image
                    ),
                    'type'   => $item->type,
                ];
            });
            return response()->json([
                'status'  => true,
                'message' => 'Notifications fetched successfully',
                'data'    => $data,
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
                return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
            }
            $notification->delete();
            return response()->json(['success' => true, 'message' => 'Notification deleted.'], 200);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    private function formatTime($date)
    {
        return Carbon::parse($date)->diffForHumans(['short' => true,]);
    }

    private function resolveUserImageUrl(?string $storedPath): string
    {
        $baseUrl = rtrim(
            'https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/',
            '/'
        );
        if (! empty($storedPath)
            && str_starts_with($storedPath, '/storage/profile_photos/')
        ) {
            $relative = ltrim(
                str_replace('/storage/', '', $storedPath),
                '/'
            );
            return $baseUrl . '/' . $relative;
        }
        

        // Default avatar
        $publicBase = rtrim(
            'https://api.mybridgeinternational.org/mybridge-backend-files/public',
            '/'
        );
        return $publicBase . '/defaults/default-avatar.png';
    }
}
