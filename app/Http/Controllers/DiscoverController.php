<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoverController extends Controller
{
    public function index()
    {
        $authUser = Auth::user();

        // 1. Pending requests SENT BY auth user
        $sentPending = Connection::where('sender_id', $authUser->id)
            ->where('status', 'pending')
            ->pluck('receiver_id')
            ->toArray();

        // 2. Pending requests RECEIVED BY auth user
        $receivedPending = Connection::where('receiver_id', $authUser->id)
            ->where('status', 'pending')
            ->pluck('sender_id')
            ->toArray();

        // 3. Accepted connections (mutual)
        $accepted = Connection::where(function ($q) use ($authUser) {
                $q->where('sender_id', $authUser->id)
                  ->orWhere('receiver_id', $authUser->id);
            })
            ->where('status', 'accepted')
            ->get()
            ->flatMap(function ($connection) use ($authUser) {
                return $connection->sender_id === $authUser->id
                    ? [$connection->receiver_id]
                    : [$connection->sender_id];
            })->toArray();

        // Combine all IDs to EXCLUDE
        $excludeIds = array_unique(array_merge(
            [$authUser->id],   // yourself
            $sentPending,
            $receivedPending,
            $accepted
        ));

        // Discover = every user NOT in excludeIds
        $users = User::whereNotIn('id', $excludeIds)
            ->select('id','uuid','full_name','email','specialisation','institution','category','image')
            ->orderBy('full_name')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Discover list.',
            'data' => $users
        ]);
    }


    public function show($user_id)
    {
        $user = User::select(
                'id','uuid','full_name','email','phone','category','specialisation',
                'institution','license_number','approval_status','status','image'
            )->find($user_id);

        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'User profile retrieved.',
            'data' => $user
        ]);
    }


    public function add($user_id)
    {
        $authUser = Auth::user();

        if ($authUser->id == (int) $user_id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot connect with yourself.'
            ], 400);
        }

        $target = User::find($user_id);
        if (! $target) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Check for existing connection (any direction)
        $exists = Connection::where(function ($q) use ($authUser, $user_id) {
                $q->where('sender_id', $authUser->id)
                  ->where('receiver_id', $user_id);
            })
            ->orWhere(function ($q) use ($authUser, $user_id) {
                $q->where('sender_id', $user_id)
                  ->where('receiver_id', $authUser->id);
            })
            ->first();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Connection already exists.'
            ], 409);
        }

        // Create new pending invitation
        Connection::create([
            'sender_id' => $authUser->id,
            'receiver_id' => $user_id,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Connection request sent.',
            'data' => ['connection_status' => 'pending_sent']
        ]);
    }
}
