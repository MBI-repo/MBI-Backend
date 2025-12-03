<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;




class ProfileController extends Controller
{
    public function showAccount()
    {
        try {
            $user = Auth::user();
            $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";
            return response()->json([
                'status'  => true,
                'message' => 'Account information fetched successfully.',
                'data'    => [
                    'uuid'                => $user->uuid,
                    'full_name'           => $user->full_name,
                    'email'               => $user->email,
                    'image'               =>  $base_url . $user->image,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateAccount(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'full_name'   => ['nullable', 'string', 'max:255'],
                'old_password' => ['required_with:password', 'string'],
                'password'    => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
            if (!empty($validated['full_name'])) {
                $user->full_name = $validated['full_name'];
            }
            if (!empty($validated['password'])) {
                if (! Hash::check($validated['old_password'], $user->password)) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'The old password you entered is incorrect.',
                    ], 422);
                }
                $user->password = Hash::make($validated['password']);
            }
            $user->save();
            $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";

            return response()->json([
                'status'  => true,
                'message' => 'Account information updated successfully.',
                'data'    => [
                    'uuid'               => $user->uuid,
                    'full_name'          => $user->full_name,
                    'email'              => $user->email,
                    'image' =>  $base_url . $user->image,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateAvatar(Request $request)
    {
        try {
            $user = Auth::user();

            $request->validate([
                'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            ]);

            $file   = $request->file('avatar');

            $path = $file->store('profile_photos', 'public');

            if ($user->image && !filter_var($user->image, FILTER_VALIDATE_URL)) {
                $oldPath = $user->image;
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $user->image = $path;
            $user->save();
            $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/"

            return response()->json([
                'status'  => true,
                'message' => 'Profile picture updated successfully.',
                'data'    => [
                    'uuid'                 => $user->uuid,
                    'full_name'          => $user->full_name,
                    'email'              => $user->email,
                    'image' =>  $base_url . $user->image,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $ve->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
