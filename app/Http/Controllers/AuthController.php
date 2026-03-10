<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\Event;
use App\Models\Message;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'category' => 'nullable|string|max:255',
            'specialisation' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:255|unique:users',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'category' => $request->category,
            'specialisation' => $request->specialisation,
            'institution' => $request->institution,
            'license_number' => $request->license_number,
            'approval_status' => 'pending',
            'status' => 'active',
        ]);

        // Optional image upload: store on public disk and persist PATH only
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            // Save into storage/app/public/profile_photos
            Storage::disk('public')->putFileAs('profile_photos', $file, $filename);
            // Persist the public URL path (served via /storage symlink)
            $user->image = '/storage/profile_photos/' . $filename;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Ensure image field is an absolute URL in response
        if (!empty($user->image)) {
            $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
        }

        // Send welcome email (non-blocking for response)
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));
        } catch (\Throwable $mailException) {
            // Swallow mail exceptions to avoid blocking registration
        }

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive'
            ], 403);
        }

        // Ensure image field is an absolute URL in response
        if (!empty($user->image)) {
            $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        //$user->isseller = $user->category === 'Manufacturing, Supply & Logistics';

        $user->isseller  =
        $user->category === 'Manufacturing, Supply & Logistics' &&
        $user->corporate_registration_papers &&
        $user->product_approval &&
        $user->warranty_policy_document &&
        $user->export_capability_statement &&
        $user->incoterms_preference;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Logout user (Revoke the token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get the authenticated User
     */
    public function user(Request $request)
    {
        $user = $request->user();
        if ($user) {
            // Total published events count (simple overall metric)
            $eventCount = Event::count();

            // Unread messages: messages in conversations the user participates in, not sent by the user, with null read_at
            $messageCount = Message::whereNull('read_at')
                ->where('sender_id', '!=', $user->id)
                ->whereHas('conversation', function ($q) use ($user) {
                    $q->whereHas('participants', function ($pq) use ($user) {
                        $pq->where('users.id', $user->id);
                    });
                })
                ->count();

            // Network count: accepted connections where user is sender or receiver
            $networkCount = Connection::accepted()
                ->where(function ($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                })
                ->count();

            $user->setAttribute('message_count', $messageCount);
            $user->setAttribute('network_count', $networkCount);
            $user->setAttribute('event_count', $eventCount);

             $user->isseller = $user->category === 'seller';

            if (!empty($user->image)) {
                $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update the authenticated user's profile details
     */
    public function update(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|string|max:255',
                'category' => 'sometimes|string|max:255',
                'specialisation' => 'sometimes|string|max:255',
                'institution' => 'sometimes|string|max:255',
                'license_number' => 'sometimes|string|max:255|unique:users,license_number,' . ($user ? $user->id : 'NULL'),
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'facility_name' => 'sometimes|string|max:255',
                'country' => 'sometimes|string|max:255',
                'medical_licence' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif,webp|max:5120',
                // email and phone are intentionally excluded from updates
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if (array_key_exists('full_name', $data)) {
                $user->full_name = $data['full_name'];
            }
            if (array_key_exists('category', $data)) {
                $user->category = $data['category'];
            }
            if (array_key_exists('specialisation', $data)) {
                $user->specialisation = $data['specialisation'];
            }
            if (array_key_exists('institution', $data)) {
                $user->institution = $data['institution'];
            }
            if (array_key_exists('license_number', $data)) {
                $user->license_number = $data['license_number'];
            }
            if (array_key_exists('facility_name', $data)) {
                $user->facility_name = $data['facility_name'];
            }
            if (array_key_exists('country', $data)) {
                $user->country = $data['country'];
            }

            // Optional image upload: delete existing file then store with Storage and persist '/storage' PATH
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                // Attempt to delete previous image stored via '/storage' path
                if (!empty($user->image)) {
                    $prev = $user->image;
                    // convert '/storage/dir/file.jpg' => 'dir/file.jpg'
                    $relative = ltrim(Str::replaceFirst('/storage/', '', $prev), '/');
                    if (!empty($relative) && Storage::disk('public')->exists($relative)) {
                        Storage::disk('public')->delete($relative);
                    }
                }
                // Store new image: storage/app/public/profile_photos
                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('profile_photos', $file, $filename);
                // Persist '/storage/...' path for frontend access via symlink
                $user->image = '/storage/profile_photos/' . $filename;
            }

            // Optional medical_licence upload: store on public disk under medical_personnel
            if ($request->hasFile('medical_licence')) {
                $file = $request->file('medical_licence');
                // Delete old medical_licence if present
                if (!empty($user->medical_licence)) {
                    $prev = $user->medical_licence;
                    $relative = ltrim(Str::replaceFirst('/storage/', '', $prev), '/');
                    if (!empty($relative) && Storage::disk('public')->exists($relative)) {
                        Storage::disk('public')->delete($relative);
                    }
                }
                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                Storage::disk('public')->putFileAs('medical_personnel', $file, $filename);
                $user->medical_licence = '/medical_personnel/' . $filename;
            }

            $user->save();

            // Ensure absolute URL in response
            if (!empty($user->image)) {
                $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
            }
            if (!empty($user->medical_licence)) {
                $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";
                $user->setAttribute('medical_licence', $base_url . ltrim($user->medical_licence, '/'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build absolute URL for stored path.
     */
    private function toAbsoluteUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }
        return rtrim('https://api.mybridgeinternational.org/mybridge-backend-files/public', '/') . $path;
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'email', 'exists:users,email'],
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => true,
                    'message' => 'We have emailed your password reset link!', //__($status), 
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to send reset link. Please try again.', //__($status)
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'token'    => ['required'],
                'email'    => ['required', 'email'],
                'password' => ['required', 'confirmed', 'min:8'],
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                    ])->save();

                    // optional: log user out of all devices
                    $user->tokens()->delete(); // if using Sanctum personal access tokens

                    // optional: auto-login after reset
                    // Auth::login($user);
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => true,
                    'message' => __($status), // "Your password has been reset!"
                ], 200);
            }

            return response()->json([
                'status' => false,
                'message' => __($status),
            ], 400);
        } catch (ValidationException $ve) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'User is not authenticated.',
                ], 401);
            }
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status'  => true,
                'message' => 'User logged out successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Logout failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
