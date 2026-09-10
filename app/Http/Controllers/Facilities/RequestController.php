<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\DoctorProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\OtpVerification;
use App\Models\Donor;
use App\Services\SendSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetOtpMail;
use App\Models\BloodRequest;

class RequestController extends Controller
{


    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            if (!$user->bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $requestPending = $user->bloodBank->requests()->where('status', 'pending')->count();
            $requestUrgent = $user->bloodBank->requests()->where('priority', 'urgent')->count();
            $requestToday = $user->bloodBank->requests()->whereDate('created_at', today())->count();
            $recentRequests = $user->bloodBank->requests()->orderBy('id', 'desc')->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Requests count retrieved successfully.',
                'requestPending' => $requestPending,
                'requestUrgent' => $requestUrgent,
                'requestToday' => $requestToday,    
                'recentRequests' => $recentRequests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving requests count.',
                'error' => $e->getMessage(),
            ], 500);
        }
      
    }


    public function getAllRequests(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $query = $bloodBank->requests();

            $validated = $request->validate([
            'request_number' => 'nullable|string|max:100',
            ]);

            $query = $bloodBank->requests();

            // Search by request number
            if (!empty($validated['request_number'])) {
                $query->where(
                    'request_number',
                    'like',
                    '%' . $validated['request_number'] . '%'
                );
            }

            $requests = $query->orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Requests retrieved successfully.',
                'requests' => $requests,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving requests.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewRequest(Request $request, string $uuid)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            if (!$user->bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $request = BloodRequest::where('uuid', $uuid)->first();

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Request retrieved successfully.',
                'request' => $request,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function acceptRequest(Request $request, string $uuid)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }


            $bloodRequest = $user->bloodBank->requests()
                ->where('uuid', $uuid)
                ->whereIn('priority', ['low','normal', 'urgent', 'emergency'])
                ->where('status', 'pending')
                ->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pending blood request not found.',
                ], 404);
            }

            $bloodRequest->update([
                'status' => 'accepted',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request accepted successfully.',
                'request' => $bloodRequest->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error accepting request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function rejectRequest(Request $request, string $uuid)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $bloodRequest = $user->bloodBank->requests()
                ->where('uuid', $uuid)
                ->whereIn('priority', ['low','normal', 'urgent', 'emergency'])
                ->where('status', 'pending')
                ->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pending blood request not found.',
                ], 404);
            }

            $bloodRequest->update([
                'status' => 'rejected',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request rejected successfully.',
                'request' => $bloodRequest->fresh(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteRequest(Request $request, string $uuid)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            if (!$user->bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $request = $user->bloodBank->requests()->where('uuid', $uuid)->first();

            if (!$request) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found.',
                ], 404);
            }

            $request->delete();

            return response()->json([
                'success' => true,
                'message' => 'Request deleted successfully.',
                //'request' => $request,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
