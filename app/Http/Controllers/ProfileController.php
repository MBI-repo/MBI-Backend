<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Notification;
use App\Models\ProfessionalProfile;




class ProfileController extends Controller
{
    public function showAccount(Request $request)
    {
        try {
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            // Resolve image URL (returns uploaded image full URL OR default image full URL)
            $imageUrl = $this->resolveUserImageUrl($user->image);

            return response()->json([
                'status'  => true,
                'message' => 'Account information fetched successfully.',
                'data'    => [
                    'uuid'      => $user->uuid,
                    'full_name' => $user->full_name,
                    'email'     => $user->email,
                    'image'     => $imageUrl,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    // public function showAccount()
    // {
    //     try {
    //         $user = Auth::user();
    //         $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";
    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Account information fetched successfully.',
    //             'data'    => [
    //                 'uuid'                => $user->uuid,
    //                 'full_name'           => $user->full_name,
    //                 'email'               => $user->email,
    //                 'image'               =>  $base_url . $user->image,
    //             ],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

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

            $updatedFields = [];

            if (!empty($validated['full_name'])) {
                $updatedFields[] = 'name';
            }

            if (!empty($validated['password'])) {
                $updatedFields[] = 'password';
            }

            if (!empty($updatedFields)) {
                Notification::create([
                    'receiver_id' => $user->uuid,
                    'sender_id' => $user->uuid,
                    'title' => 'Account updated',
                    'message' => 'Your account ' . implode(' and ', $updatedFields) . ' has been updated successfully.',
                    'type' => 'update',
                    'is_read' => false,
                    'reference_id' => $user->id,
                    'reference_type' => 'user',
                ]);
            }


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
            $user = $request->user();
            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation errors',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $file = $request->file('avatar');

            // Delete previous file if it follows the storage pattern: /storage/profile_photos/...
            if (! empty($user->image) && str_starts_with($user->image, '/storage/profile_photos/')) {
                $oldRelative = ltrim(str_replace('/storage/', '', $user->image), '/'); // profile_photos/xxx.jpg
                if (! empty($oldRelative) && Storage::disk('public')->exists($oldRelative)) {
                    Storage::disk('public')->delete($oldRelative);
                }
            }

            // Store new file under public/profile_photos with a UUID filename
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $relativePath = 'profile_photos/' . $filename;
            Storage::disk('public')->putFileAs('profile_photos', $file, $filename);

            // Persist DB path as '/storage/profile_photos/filename.ext'
            $user->image = '/storage/' . $relativePath;
            $user->save();

            

            Notification::create([
                'receiver_id' => $user->uuid,
                'sender_id' => $user->uuid,
                'title' => 'Profile picture updated',
                'message' => 'Your profile picture has been updated successfully.',
                'type' => 'update',
                'is_read' => false,
                'reference_id' => $user->id,
                'reference_type' => 'user',
            ]);


            // Build full public URL for frontend (base must match live server asset location)
            $Base_Url = rtrim('https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/', '/');
            $fullUrl = $Base_Url . '/' . $relativePath;

            return response()->json([
                'status'  => true,
                'message' => 'Profile picture updated successfully.',
                'data'    => [
                    'uuid'      => $user->uuid,
                    'full_name' => $user->full_name,
                    'email'     => $user->email,
                    'image'     => $fullUrl,
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Use this helper when returning user profiles elsewhere.
     * It returns the absolute URL for the user's image, or the default image URL if none exists.
     */
    private function resolveUserImageUrl(?string $storedPath): string
    {
        // Storage-served images
        $Base_Url = rtrim('https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/', '/');

        if (! empty($storedPath) && str_starts_with($storedPath, '/storage/profile_photos/')) {
            // storedPath = "/storage/profile_photos/xxx.jpg" -> relative = profile_photos/xxx.jpg
            $relative = ltrim(str_replace('/storage/', '', $storedPath), '/');
            return $Base_Url . '/' . $relative;
        }

        // Default image located in public/defaults/default-avatar.png
        $publicBase = rtrim('https://api.mybridgeinternational.org/mybridge-backend-files/public', '/');
        return $publicBase . '/defaults/default-avatar.png';
    }



    //public function updateAvatar(Request $request)
    // {
    //     try {
    //         $user = User::find(Auth::id());
    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Authenticated user not found',
    //             ], 401);
    //         }
    //         $validator = Validator::make($request->all(), [
    //             'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:2048'],
    //         ]);
    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Validation errors',
    //                 'errors'  => $validator->errors(),
    //             ], 422);
    //         }

    //         $file   = $request->file('avatar');

    //         $path = $file->store('profile_photos', 'public');

    //         if ($user->image && !filter_var($user->image, FILTER_VALIDATE_URL)) {
    //             $oldPath = $user->image;
    //             if (Storage::disk('public')->exists($oldPath)) {
    //                 Storage::disk('public')->delete($oldPath);
    //             }
    //         }

    //         $user->image = $path;
    //         $user->save();
    //         $base_url = "https://api.mybridgeinternational.org/mybridge-backend-files/storage/app/public/";

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'Profile picture updated successfully.',
    //             'data'    => [
    //                 'uuid'                 => $user->uuid,
    //                 'full_name'          => $user->full_name,
    //                 'email'              => $user->email,
    //                 'image' =>  $base_url . $user->image,
    //             ],
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Error: ' . $e->getMessage(),
    //         ], 500);
    //     }
    //}

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

            $updatedSections = [];

            if (array_key_exists('bio', $data)) {
                $updatedSections[] = 'bio';
            }

            if (array_key_exists('areasOfExpertise', $data)) {
                $updatedSections[] = 'areas of expertise';
            }

            if (array_key_exists('educations', $data)) {
                $updatedSections[] = 'educations';
            }

            if (array_key_exists('experiences', $data)) {
                $updatedSections[] = 'experiences';
            }

            if (array_key_exists('certifications', $data)) {
                $updatedSections[] = 'certifications';
            }

            if (array_key_exists('publications', $data)) {
                $updatedSections[] = 'publications';
            }

            if (array_key_exists('memberships', $data)) {
                $updatedSections[] = 'memberships';
            }

            if (array_key_exists('awards', $data)) {
                $updatedSections[] = 'awards';
            }

            if (! empty($updatedSections)) {
                $verb = count($updatedSections) === 1 ? 'has' : 'have';

                Notification::create([
                'receiver_id' => $user->uuid,
                'sender_id' => $user->uuid,
                'title' => 'Professional profile updated',
                'message' => 'Your ' . implode(', ', $updatedSections) . " {$verb} been updated successfully.",
                'type' => 'update',
                'is_read' => false,
                'reference_id' => $profile->id,
                'reference_type' => 'professional_profile',
            ]);
        }

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



    public function destroy(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User account deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function profileCompletion(Request $request)
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authenticated user not found',
                ], 401);
            }

            $user->load('professionalProfile');

            $completion = $this->buildProfileCompletion($user);

            return response()->json([
                'success' => true,
                'message' => 'Profile completion fetched successfully.',
                'data' => $completion,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildProfileCompletion(User $user): array
    {
        $profile = $user->professionalProfile;
        $isSeller = (bool) $user->isSeller || $user->category === 'seller';

        $steps = [
            [
                'step' => 1,
                'key' => 'registration',
                'title' => 'Registration',
                'completed' => $this->hasRequiredValues([
                    $user->full_name,
                    $user->email,
                    $user->phone,
                    $user->category,
                ]),
                'missing' => $this->missingFields([
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'category' => $user->category,
                ]),
            ],
            [
                'step' => 2,
                'key' => 'bio',
                'title' => 'Bio',
                'completed' => ! empty($profile?->bio),
                'missing' => empty($profile?->bio) ? ['bio'] : [],
            ],
            [
                'step' => 3,
                'key' => 'education_qualification',
                'title' => 'Education Qualification',
                'completed' => ! empty($profile?->educations),
                'missing' => empty($profile?->educations) ? ['educations'] : [],
            ],
            [
                'step' => 4,
                'key' => 'professional_experience',
                'title' => 'Professional Experience',
                'completed' => ! empty($profile?->experiences),
                'missing' => empty($profile?->experiences) ? ['experiences'] : [],
            ],
            [
                'step' => 5,
                'key' => 'certifications_and_licenses',
                'title' => 'Certification and Licenses',
                'completed' => ! empty($profile?->certifications)
                    || ! empty($user->license_number)
                    || ! empty($user->medical_licence),
                'missing' => $this->missingFields([
                    'certifications' => $profile?->certifications,
                    'license_number' => $user->license_number,
                    'medical_licence' => $user->medical_licence,
                ]),
            ],
        ];

        if ($isSeller) {
            $steps[] = [
                'step' => 6,
                'key' => 'seller_kyc_documents',
                'title' => 'Marketplace KYC and Document Uploads',
                'completed' => $this->hasRequiredValues([
                    $user->kyc_verified_at,
                    $user->corporate_registration_papers ?? null,
                    $user->warranty_policy_document ?? null,
                ]),
                'missing' => $this->missingFields([
                    'kyc_verified_at' => $user->kyc_verified_at,
                    'corporate_registration_papers' => $user->corporate_registration_papers ?? null,
                    'warranty_policy_document' => $user->warranty_policy_document ?? null,
                ]),
            ];
        }

        $totalSteps = count($steps);
        $completedSteps = collect($steps)->where('completed', true)->count();
        $remainingSteps = $totalSteps - $completedSteps;

        $nextStep = collect($steps)->firstWhere('completed', false);

        $isComplete = $completedSteps === $totalSteps;

        return [
            'is_seller' => $isSeller,
            'total_steps' => $totalSteps,
            'completed_steps' => $completedSteps,
            'remaining_steps' => $remainingSteps,
            'completion_percentage' => $totalSteps > 0
                ? round(($completedSteps / $totalSteps) * 100)
                : 0,
            'is_complete' => $isComplete,
            'show_on_dashboard' => ! $isComplete,
            'next_step' => $nextStep ? [
                'step' => $nextStep['step'],
                'key' => $nextStep['key'],
                'title' => $nextStep['title'],
                'missing' => $nextStep['missing'],
            ] : null,
            'steps' => $steps,
        ];
    }

    private function hasRequiredValues(array $values): bool
    {
        foreach ($values as $value) {
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    private function missingFields(array $fields): array
    {
        return collect($fields)
            ->filter(fn ($value) => empty($value))
            ->keys()
            ->values()
            ->all();
    }

}
