<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivedController extends Controller
{
        public function index(Request $request)
    {
        try {
            $authUser = Auth::user();

            $connections = Connection::where('receiver_id', $authUser->id)
                ->where('status', 'pending')
                ->with('sender:id,full_name,email,specialisation,institution,category,profile_photo_path')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($connection) {
                    $s = $connection->sender;
                    return [
                        'connection_id' => $connection->id,
                        'sender' => [
                            'id' => $s->id,
                            'full_name' => $s->full_name,
                            'email' => $s->email,
                            'specialisation' => $s->specialisation,
                            'institution' => $s->institution,
                            'category' => $s->category,
                            'profile_photo_path' => $s->profile_photo_path,
                        ],
                        'sent_at' => $connection->created_at ? $connection->created_at->toDateTimeString() : null,
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Received invitations fetched.',
                'data' => $connections
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function accept($connection_id)
    {
        try {
            $authUser = Auth::user();

            $connection = Connection::where('id', $connection_id)
                ->where('receiver_id', $authUser->id)
                ->where('status', 'pending')
                ->first();

            if (! $connection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invitation not found or already handled.'
                ], 404);
            }

            DB::transaction(function () use ($connection) {
                $connection->update(['status' => 'accepted']);
            });

            $sender = $connection->sender()->select('id','full_name','email','specialisation','institution','category','profile_photo_path')->first();

            return response()->json([
                'status' => true,
                'message' => 'Invitation accepted.',
                'data' => [
                    'connection_id' => $connection->id,
                    'user' => $sender,
                    'connection_status' => 'connected'
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reject($connection_id)
    {
        try {
            $authUser = Auth::user();

            $connection = Connection::where('id', $connection_id)
                ->where('receiver_id', $authUser->id)
                ->where('status', 'pending')
                ->first();

            if (! $connection) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invitation not found or already handled.'
                ], 404);
            }

            DB::transaction(function () use ($connection) {
                $connection->update(['status' => 'rejected']);
            });
            // $connection->update(['status' => 'rejected']);

            $sender = $connection->sender()->select('id','full_name','email','specialisation','institution','category','profile_photo_path')->first();

            return response()->json([
                'status' => true,
                'message' => 'Invitation declined.',
                'data' => [
                    'connection_id' => $connection->id,
                    'user' => $sender,
                    'connection_status' => 'rejected'
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
