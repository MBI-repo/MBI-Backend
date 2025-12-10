<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SentController extends Controller
{
    public function view(Request $request)
    {
        try {
            $authUser = Auth::user();

            $connections = Connection::where('sender_id', $authUser->id)
                ->where('status', 'pending')
                ->with('receiver:id,full_name,email,specialisation,institution,category,image')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($connection) {
                    $r = $connection->receiver;
                    $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";
                    return [
                        'connection_id' => $connection->id,
                        'receiver' => [
                            'id' => $r->id,
                            'full_name' => $r->full_name,
                            'email' => $r->email,
                            'specialisation' => $r->specialisation,
                            'institution' => $r->institution,
                            'category' => $r->category,
                            'profile_photo_path' => $base_url . $r->image,
                        ],
                        'sent_at' => $connection->created_at ? $connection->created_at->toDateTimeString() : null,
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Sent invitations fetched.',
                'data' => $connections
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /network/sent/cancel/{connection_id}
     * Withdraw a pending invitation previously sent by the auth user.
     * We delete the row to remove the pending invite; adjust if you prefer 'rejected' status instead.
     */
    public function cancel($connection_id)
    {
        try {
            $authUser = Auth::user();

            $connection = Connection::where('id', $connection_id)
                ->where('sender_id', $authUser->id)
                ->where('status', 'pending')
                ->first();

            if (! $connection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pending invitation not found or already handled.'
                ], 404);
            }

            // Keep receiver's details for UI update after deletion
            $receiver = $connection->receiver()->select('id','full_name','email','specialisation','institution','category','image')->first();

            // Delete the pending invitation (withdraw)
            $connection->delete();

            return response()->json([
                'status' => true,
                'message' => 'Invitation cancelled.',
                'data' => [
                    'connection_id' => (int) $connection_id,
                    'user' => $receiver,
                    'connection_status' => 'cancelled'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
