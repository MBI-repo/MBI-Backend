<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NetworkController extends Controller
{
    /**
     * GET /network
     * Dashboard/summary of a user's network activity.
     */
    public function index(Request $request)
    {
        try {
            $authUser = Auth::user();

            // --- 1. Accepted connections ---
            $acceptedConnectionsCount = Connection::where(function ($q) use ($authUser) {
                $q->where('sender_id', $authUser->id)
                    ->orWhere('receiver_id', $authUser->id);
            })
                ->where('status', 'accepted')
                ->count();

            // --- 2. Invitations RECEIVED (pending) ---
            $receivedCount = Connection::where('receiver_id', $authUser->id)
                ->where('status', 'pending')
                ->count();

            // --- 3. Invitations SENT (pending) ---
            $sentCount = Connection::where('sender_id', $authUser->id)
                ->where('status', 'pending')
                ->count();

            // --- 4. Discoverable users (not connected, not pending) ---
            $pendingSent = Connection::where('sender_id', $authUser->id)
                ->where('status', 'pending')
                ->pluck('receiver_id')
                ->toArray();

            $pendingReceived = Connection::where('receiver_id', $authUser->id)
                ->where('status', 'pending')
                ->pluck('sender_id')
                ->toArray();

            $acceptedMutual = Connection::where(function ($q) use ($authUser) {
                $q->where('sender_id', $authUser->id)
                    ->orWhere('receiver_id', $authUser->id);
            })
                ->where('status', 'accepted')
                ->get()
                ->flatMap(function ($c) use ($authUser) {
                    return $c->sender_id === $authUser->id ? [$c->receiver_id] : [$c->sender_id];
                })
                ->toArray();

            // users to exclude from discover list
            $excludeIds = array_unique(array_merge(
                [$authUser->id],
                $pendingSent,
                $pendingReceived,
                $acceptedMutual
            ));

            $discoverableCount = User::whereNotIn('id', $excludeIds)->count();
            return true;

            // Return response
            return response()->json([
                'status' => true,
                'message' => 'Network dashboard data loaded.',
                'data' => [
                    'connections_total' => $acceptedConnectionsCount,
                    'received_invitations_total' => $receivedCount,
                    'sent_invitations_total' => $sentCount,
                    'discoverable_users_total' => $discoverableCount,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
