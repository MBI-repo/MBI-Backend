<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;


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
            'category' => 'required|string|max:255',
            'specialisation' => 'required|string|max:255',
            'institution' => 'required|string|max:255',
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

        // Optional image upload: store PATH only; return absolute URL in response
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Prefer Hostinger path under mybridge-backend-files/public if present
            $basePublicRoot = public_path('mybridge-backend-files/public');
            $targetDir = is_dir($basePublicRoot)
                ? $basePublicRoot . '/storage/profile_images'
                : public_path('storage/profile_images');

            File::ensureDirectoryExists($targetDir);
            $file->move($targetDir, $filename);

            // Persist PATH only
            $user->image = '/storage/profile_images/' . $filename;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Ensure image field is an absolute URL in response
        if (!empty($user->image)) {
            $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
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
        if ($user && !empty($user->image)) {
            $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
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

            // Optional image upload: delete existing file then store and persist PATH only
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $basePublicRoot = public_path('mybridge-backend-files/public');

                // Attempt to delete old file if it exists
                if (!empty($user->image)) {
                    $urlPath = parse_url($user->image, PHP_URL_PATH) ?: $user->image;
                    if ($urlPath && Str::startsWith($urlPath, '/')) {
                        $existingPath = ltrim($urlPath, '/');
                        // Try delete from both possible locations
                        $publicStorageFile = public_path($existingPath);
                        $publicBackendFile = is_dir($basePublicRoot)
                            ? $basePublicRoot . '/' . $existingPath
                            : null;
                        if (file_exists($publicStorageFile)) {
                            @unlink($publicStorageFile);
                        } elseif ($publicBackendFile && file_exists($publicBackendFile)) {
                            @unlink($publicBackendFile);
                        }
                    }
                }

                // Store new image and persist PATH only
                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                $targetDir = is_dir($basePublicRoot)
                    ? $basePublicRoot . '/storage/profile_images'
                    : public_path('storage/profile_images');
                File::ensureDirectoryExists($targetDir);
                $file->move($targetDir, $filename);
                $user->image = '/storage/profile_images/' . $filename;
            }

            $user->save();

            // Ensure absolute URL in response
            if (!empty($user->image)) {
                $user->setAttribute('image', $this->toAbsoluteUrl($user->image));
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
                    'message' =>'We have emailed your password reset link!', //__($status), 
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

}
