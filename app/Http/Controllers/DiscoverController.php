<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Connection;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiscoverController extends Controller
{
    
    public function view(Request $request)
    {
        try {
            $authUser = User::find(Auth::id());
            if (! $authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            $authId = $authUser->id;

            // 1) IDs of users the auth user has SENT pending requests to
            $sentPending = Connection::where('sender_id', $authId)
                ->where('status', 'pending')
                ->pluck('receiver_id')
                ->toArray();

            // 2) IDs of users who SENT pending requests to the auth user
            $receivedPending = Connection::where('receiver_id', $authId)
                ->where('status', 'pending')
                ->pluck('sender_id')
                ->toArray();

            // 3) IDs of accepted connections (other party ids)
            $accepted = Connection::where(function ($q) use ($authId) {
                    $q->where('sender_id', $authId)
                      ->orWhere('receiver_id', $authId);
                })
                ->where('status', 'accepted')
                ->get()
                ->flatMap(function ($connection) use ($authId) {
                    return $connection->sender_id === $authId
                        ? [$connection->receiver_id]
                        : [$connection->sender_id];
                })->toArray();

            // Combine all ids to exclude: yourself + sent pending + received pending + accepted
            $excludeIds = array_unique(array_merge(
                [$authId],
                $sentPending,
                $receivedPending,
                $accepted
            ));

            // Discover = users not in excludeIds
            // Select minimal fields needed by UI (including uuid & image)
            $users = User::whereNotIn('id', $excludeIds)
                ->select('id', 'uuid', 'full_name', 'email', 'specialisation', 'institution', 'category', 'image')
                ->orderBy('full_name', 'asc')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Discover list.',
                'data' => $users
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /network/discover/view-profile/{uuid}
     * Show public profile of a user by uuid
     */
    public function show($uuid)
    {
        try {
            $user = User::select(
                    'id','uuid','full_name','email','phone','category','specialisation',
                    'institution','license_number','approval_status','status','image'
                )->where('uuid', $uuid)->first();

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
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /network/discover/connect/{uuid}
     * Send a connection request using the target user's UUID (frontend uses uuid).
     */
    public function add($uuid)
    {
        try {
            $authUser = Auth::user();
            if (! $authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            // Find the target user by UUID
            $target = User::where('uuid', $uuid)->first();
            if (! $target) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            // Prevent sending request to self (compare by id)
            if ($authUser->id === $target->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot connect with yourself.'
                ], 400);
            }

            $senderId = $authUser->id;
            $receiverId = $target->id;

            // Check for existing connection (either direction)
            $exists = Connection::where(function ($q) use ($senderId, $receiverId) {
                    $q->where('sender_id', $senderId)->where('receiver_id', $receiverId);
                })
                ->orWhere(function ($q) use ($senderId, $receiverId) {
                    $q->where('sender_id', $receiverId)->where('receiver_id', $senderId);
                })
                ->first();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Connection already exists or pending.'
                ], 409);
            }

            // Create new pending invitation inside a transaction
           $connection = DB::transaction(function () use ($senderId, $receiverId, $authUser, $target) {
                $connection = Connection::create([
                    'sender_id'   => $senderId,
                    'receiver_id' => $receiverId,
                    'status'      => 'pending',
                ]);

                Notification::create([
                    'receiver_id' => $target->uuid,
                    'sender_id' => $authUser->uuid,
                    'title' => 'New connection request',
                    'message' => "{$authUser->full_name} sent you a connection request.",
                    'type' => 'connection',
                    'is_read' => false,
                    'reference_id' => $connection->id,
                    'reference_type' => 'connection_request',
                ]);

                return $connection;
            });
            return response()->json([
                'status' => true,
                'message' => 'Connection request sent.',
                'data' => [
                    'connection_id' => $connection->id,
                    'connection_status' => 'pending_sent']
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
