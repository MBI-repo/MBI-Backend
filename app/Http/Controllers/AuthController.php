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
            'license_number' => 'required|string|max:255|unique:users',
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

        // Optional image upload: store and return URL (supports environments without storage symlink)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $symlinkExists = file_exists(public_path('storage')) || is_link(public_path('storage'));
            $customPublicDir = public_path('mybridge-internation-files/profile_images');

            if ($symlinkExists) {
                $path = $file->storeAs('profile_images', $filename, 'public');
                $fullUrl = url(Storage::url($path));
            } else {
                File::ensureDirectoryExists($customPublicDir);
                $file->move($customPublicDir, $filename);
                $fullUrl = url('/mybridge-internation-files/profile_images/' . $filename);
            }

            $user->image = $fullUrl;
            $user->save();
        }

        $token = $user->createToken('auth_token')->plainTextToken;

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

        // Ensure image field is a full URL in response
        if ($user->image && !Str::startsWith($user->image, ['http://', 'https://'])) {
            $user->setAttribute('image', url(Storage::url($user->image)));
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
        if ($user && $user->image && !Str::startsWith($user->image, ['http://', 'https://'])) {
            $user->setAttribute('image', url(Storage::url($user->image)));
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

            // Optional image upload: delete existing file then store and persist FULL URL
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $symlinkExists = file_exists(public_path('storage')) || is_link(public_path('storage'));

                // Attempt to delete old file if it exists
                if (!empty($user->image)) {
                    $existingPath = null;
                    if (Str::startsWith($user->image, ['http://', 'https://'])) {
                        $urlPath = parse_url($user->image, PHP_URL_PATH);
                        if ($urlPath) {
                            if (Str::startsWith($urlPath, '/storage/')) {
                                $existingPath = ltrim(Str::replaceFirst('/storage/', '', $urlPath), '/');
                            } elseif (Str::startsWith($urlPath, '/mybridge-internation-files/')) {
                                $existingPath = ltrim(Str::replaceFirst('/mybridge-internation-files/', '', $urlPath), '/');
                            }
                        }
                    } else {
                        $existingPath = ltrim($user->image, '/');
                    }
                    if ($existingPath) {
                        if ($symlinkExists) {
                            if (Storage::disk('public')->exists($existingPath)) {
                                Storage::disk('public')->delete($existingPath);
                            }
                        } else {
                            $publicStorageFile = public_path('storage/' . $existingPath);
                            $publicCustomFile = public_path('mybridge-internation-files/' . $existingPath);
                            if (file_exists($publicStorageFile)) {
                                @unlink($publicStorageFile);
                            } elseif (file_exists($publicCustomFile)) {
                                @unlink($publicCustomFile);
                            }
                        }
                    }
                }

                // Store new image and persist full URL
                $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
                if ($symlinkExists) {
                    $path = $file->storeAs('profile_images', $filename, 'public');
                    $user->image = url(Storage::url($path));
                } else {
                    $customPublicDir = public_path('mybridge-internation-files/profile_images');
                    File::ensureDirectoryExists($customPublicDir);
                    $file->move($customPublicDir, $filename);
                    $user->image = url('/mybridge-internation-files/profile_images/' . $filename);
                }
            }

            $user->save();

            // Ensure full URL in response
            if ($user->image && !Str::startsWith($user->image, ['http://', 'https://'])) {
                $user->setAttribute('image', url(Storage::url($user->image)));
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
}
