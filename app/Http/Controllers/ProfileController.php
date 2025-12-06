<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\ProfessionalProfile;




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
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'full_name'    => ['nullable', 'string', 'max:255'],
                'old_password' => ['required_with:password', 'string'],
                'password'     => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }
            $validated = $validator->validated();
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
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

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
            $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";

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

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function viewProfile()
    {
        try {
            $user = User::find(Auth::id());
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            $profile = $user->professionalProfile;

            //dd($profile);

            return response()->json([
                'success' => true,
                'message' => 'Professional profile fetched successfully.',
                'data'    => [
                    'bio'              => $profile?->bio,
                    'areasOfExpertise' => $profile?->areas_of_expertise ?? [],
                    'educations'       => $profile?->educations ?? [],
                    'experiences'      => $profile?->experiences ?? [],
                    'certifications'   => $profile?->certifications ?? [],
                    'publications'     => $profile?->publications ?? [],
                    'memberships'      => $profile?->memberships ?? [],
                    'awards'           => $profile?->awards ?? [],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'professionalProfile' => ['required', 'array'],
                'professionalProfile.bio' => ['nullable', 'string'],
                'professionalProfile.areasOfExpertise' => ['nullable', 'array'],
                'professionalProfile.areasOfExpertise.*' => ['string'],
                'professionalProfile.educations' => ['nullable', 'array'],
                'professionalProfile.experiences' => ['nullable', 'array'],
                'professionalProfile.certifications' => ['nullable', 'array'],
                'professionalProfile.publications' => ['nullable', 'array'],
                'professionalProfile.memberships' => ['nullable', 'array'],
                'professionalProfile.awards' => ['nullable', 'array'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $data  = $request->input('professionalProfile', []);
            $uuid  = $user->uuid;

            // get or create profile
            $profile = ProfessionalProfile::firstOrCreate(
                ['user_uuid' => $uuid],
                []
            );

            $profile->bio               = $data['bio'] ?? $profile->bio;
            $profile->areas_of_expertise = $data['areasOfExpertise'] ?? [];
            $profile->educations        = $data['educations'] ?? [];
            $profile->experiences       = $data['experiences'] ?? [];
            $profile->certifications    = $data['certifications'] ?? [];
            $profile->publications      = $data['publications'] ?? [];
            $profile->memberships       = $data['memberships'] ?? [];
            $profile->awards            = $data['awards'] ?? [];
            $profile->save();

            return response()->json([
                'success' => true,
                'message' => 'Professional profile updated successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

}
