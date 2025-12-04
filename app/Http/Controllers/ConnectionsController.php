<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConnectionsController extends Controller
{
    /**
     * Fetch all accepted connections for the authenticated user.
     * GET /network/connections
     */
    public function fetch(Request $request)
    {
        try {
            $authUser = Auth::user();

            $perPage = (int) $request->get('per_page', 20);

            $connections = Connection::accepted()
                ->where(function ($query) use ($authUser) {
                    $query->where('sender_id', $authUser->id)
                          ->orWhere('receiver_id', $authUser->id);
                })
                ->with([
                    'sender:id,uuid,full_name,email,specialisation,institution,category,image',
                    'receiver:id,uuid,full_name,email,specialisation,institution,category,image'   
                ])
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage);

            // Transform paginator items to return "other" user only
            $connections->getCollection()->transform(function ($connection) use ($authUser) {
                $user = $connection->sender_id === $authUser->id ? $connection->receiver : $connection->sender;
                
                $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";

                return [
                    'id' => $user->id,
                    'uuid'=>$user->uuid,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'specialisation' => $user->specialisation,
                    'institution' => $user->institution,
                    'category' => $user->category,
                    'profile_photo_path' => $base_url. $user->image,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Connections fetched successfully.',
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
     * Open or create a message thread with a connected user.
     * GET /network/connections/message/{user_id}
     */
    public function create($user_id)
    {
        try {
            $authUser = Auth::user();

            // ensure target user exists
            $user = User::select('id','uuid','full_name','email','specialisation','institution','category','image')
                        ->find($user_id);

            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            $connection = Connection::accepted()
                ->where(function ($query) use ($authUser, $user_id) {
                    $query->where('sender_id', $authUser->id)
                          ->where('receiver_id', $user_id);
                })
                ->orWhere(function ($query) use ($authUser, $user_id) {
                    $query->where('sender_id', $user_id)
                          ->where('receiver_id', $authUser->id);
                })
                ->first();

            if (! $connection) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not connected with this user.'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'Chat session ready.',
                'data' => [
                    'user' => $user
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
