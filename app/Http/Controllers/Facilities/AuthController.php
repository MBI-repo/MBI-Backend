<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\DoctorProfile;
use App\Models\Role;
use App\Models\User;
use App\Models\OtpVerification;
use App\Services\SendSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\PasswordResetOtpMail;




class AuthController extends Controller
{
   

    public function __construct(
        
        protected SendSmsService $sendSmsService)
    {}


    public function registerDoctor(Request $request)
    {
        try {
            $validated = $request->validate([
                'full_name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email','max:255','unique:users,email',],
                'phone' => ['required','string','max:30','unique:users,phone',],
                'password' => [ 'required','string','min:8','confirmed',],
            ]);

            if(!$validated){
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $request->errors(),
                ], 400);
            }

        

            $role = Role::where('slug', 'doctor')->where('is_active', true)->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor role is not configured.',
                ], 500);
            }

            $data = DB::transaction(function () use ($validated, $role) {

                $user = User::create([
                    'uuid' => (string) Str::uuid(),
                    'role_id' => $role->id,

                    'full_name' => $validated['full_name'],

                    'email' => $validated['email'],
                    'phone' => $validated['phone'],

                    'password' => Hash::make($validated['password']),

                    'registration_step' => 1,
                    'registration_status' => 'incomplete',

                    'status' => 'active',
                ]);

                DoctorProfile::create([
                    'uuid' => (string) Str::uuid(),
                    'user_uuid' => $user->uuid,
                ]);

                return $user;
            });

            return response()->json([
                'success' => true,
                'message' => 'Doctor registration started successfully.',
                'data' => [
                    'uuid' => $data->uuid,
                    'registration_step' => $data->registration_step,
                    'registration_status' => $data->registration_status,
                    'next_step' => 2,
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to start doctor registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function doctorStepTwo(Request $request)
    {
        try {
        $validated = $request->validate([
            'user_uuid' => ['required','uuid','exists:users,uuid',],
            'license_number' => ['required','string','max:100',],
            'specialization' => ['required','string','max:150',],
            'qualification' => ['required','string','max:255',],
            'years_of_experience' => ['nullable','string','max:50',],
        ]);

         if(!$validated){
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $request->errors(),
                ], 400);
            }

        

            $user = User::where('uuid', $validated['user_uuid'])->whereHas('role', function ($query) 
            {
                $query->where('slug', 'doctor');

            })->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor account not found.',
                ], 404);
            }

            $profile = DoctorProfile::where('user_uuid',$user->uuid)->first();

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor profile not found.',
                ], 404);
            }

            $profile->update([
                'license_number' => $validated['license_number'],
                'specialization' => $validated['specialization'],
                'qualification' => $validated['qualification'],
                'years_of_experience' =>$validated['years_of_experience'] ?? null,
            ]);

            $user->update([
                'registration_step' => 2,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Doctor professional information saved.',
                'data' => [
                    'uuid' => $user->uuid,
                    'registration_step' => 2,
                    'next_step' => 3,
                ],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to save professional information.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function completeDoctorRegistration(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_uuid' => ['required','uuid','exists:users,uuid',],
            ]);
            
             if(!$validated){
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $request->errors(),
                ], 400);
            }

       

            $user = User::where('uuid', $validated['user_uuid'])->whereHas('role', function ($query) 
            {
                $query->where('slug', 'doctor');
            })->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor account not found.',
                ], 404);
            }

            $user->update(['registration_status' => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Doctor registration completed successfully.',
                'data' => [
                    'uuid' => $user->uuid,
                    'registration_status' => 'completed',
                ],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function registerFacility(Request $request)
    {
        try {

            $validated = $request->validate([
                'facility_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'facility_email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email',
                ],

                'phone' => [
                    'required',
                    'string',
                    'max:30',
                    'unique:users,phone',
                ],

                'facility_address' => [
                    'required',
                    'string',
                    'max:255',
                ],
            ]);

            $role = Role::where('slug', 'hospital')
                ->where('is_active', true)
                ->first();

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hospital/Clinic role is not configured.',
                ], 500);
            }

            $data = DB::transaction(function () use ($validated, $role) {

                $user = User::create([
                    'uuid' => (string) Str::uuid(),

                    'role_id' => $role->id,

                    'full_name' => $validated['facility_name'],

                    'email' => $validated['facility_email'],

                    'phone' => $validated['phone'],

                    'password' => bcrypt('123456'),

                    'registration_step' => 1,

                    'registration_status' => 'incomplete',

                    'status' => 'active',
                ]);

                $facility = Facility::create([
                    'uuid' => (string) Str::uuid(),

                    'user_uuid' => $user->uuid,

                    'facility_name' => $validated['facility_name'],

                    'contact_email' => $validated['facility_email'],

                    'contact_phone' => $validated['phone'],

                    'address' => $validated['facility_address'],

                    'registration_status' => 'incomplete',
                ]);

                return [
                    'user' => $user,
                    'facility' => $facility,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Hospital/Clinic registration started successfully.',

                'data' => [
                    'user_uuid' => $data['user']->uuid,
                    'facility_uuid' => $data['facility']->uuid,

                    'registration_step' => $data['user']->registration_step,

                    'registration_status' =>$data['user']->registration_status,

                    'next_step' => 2,
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to start Hospital/Clinic registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function facilityStepTwo(Request $request)
    {
        try {

            $validated = $request->validate([
                'user_uuid' => [
                    'required',
                    'uuid',
                    'exists:users,uuid',
                ],

                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);

            $user = User::where('uuid', $validated['user_uuid'])
                ->whereHas('role', function ($query) {
                    $query->where('slug', 'hospital');
                })->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hospital/Clinic account not found.',
                ], 404);
            }

            $user->update([
                'password' => Hash::make($validated['password']),
                'registration_step' => 2,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password saved successfully.',

                'data' => [
                    'user_uuid' => $user->uuid,
                    'registration_step' => 2,
                    'next_step' => 3,
                ],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to save password.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function facilityStepThree(Request $request)
    {
        try {

            $validated = $request->validate([
                'user_uuid' => [
                    'required',
                    'uuid',
                    'exists:users,uuid',
                ],

                'facility_type' => [
                    'required',
                    'string',
                    'in:hospital,clinic',
                ],
            ]);

            $user = User::where('uuid', $validated['user_uuid'])
                ->whereHas('role', function ($query) {
                    $query->where('slug', 'hospital');
                })->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hospital/Clinic account not found.',
                ], 404);
            }

            $facility = Facility::where('user_uuid',$user->uuid)->first();

            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility profile not found.',
                ], 404);
            }

            $facility->update([
                'facility_type' => $validated['facility_type'],
            ]);

            $user->update([
                'registration_step' => 3,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Facility type saved successfully.',

                'data' => [
                    'user_uuid' => $user->uuid,
                    'facility_uuid' => $facility->uuid,
                    'registration_step' => 3,
                    'next_step' => 4,
                ],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to save facility type.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function completeFacilityRegistration(Request $request)
    {
        try {

            $validated = $request->validate([
                'user_uuid' => [
                    'required',
                    'uuid',
                    'exists:users,uuid',
                ],

                'registration_number' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'license_number' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'license_expiry_date' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:today',
                ],
            ]);

            $user = User::where('uuid', $validated['user_uuid'])
                ->whereHas('role', function ($query) {
                    $query->where('slug', 'hospital');
                })->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hospital/Clinic account not found.',
                ], 404);
            }

            $facility = Facility::where('user_uuid',$user->uuid)->first();

            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility profile not found.',
                ], 404);
            }

            $facility->update([
                'registration_number' =>$validated['registration_number'],

                'license_number' => $validated['license_number'],

                'license_expiry_date' => $validated['license_expiry_date'],

                'registration_status' => 'completed',
            ]);

            $user->update([
                'registration_step' => 4,
                'registration_status' => 'completed',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hospital/Clinic registration completed successfully.',

                'data' => [
                    'user_uuid' => $user->uuid,
                    'facility_uuid' => $facility->uuid,

                    'registration_step' =>$user->registration_step,

                    'registration_status' =>$user->registration_status,
                ],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to complete registration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function login(Request $request)
    {
        try {


            $validated = $request->validate([
                'identifier' => [
                    'required',
                    'string',
                ],
                'password' => [
                    'required',
                    'string',
                ],
            ]);
            if(!$validated){
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $request->errors(),
                ], 400);
            }

    

        $user = User::with([
                        'role',
                        'doctorProfile',
                        'facility',
                    ])->where(function ($query) use ($validated) {
                        $query->where('email', $validated['identifier'])
                            ->orWhere('phone', $validated['identifier']);
                    })->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials.',
                ], 401);
            }

            if ($user->status !== 'active') {

                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not active.',
                ], 403);
            }

            // if ($user->registration_status !== 'completed') {

            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Your registration is not yet completed.',
            //         'data' => [
            //             'user_uuid' => $user->uuid,
            //             'registration_step' => $user->registration_step,
            //             'registration_status' => $user->registration_status,
            //         ],
            //     ], 403);
            // }

            $user->update([
                'last_login_at' => now(),
            ]);

            $token = $user->createToken('api-token')->plainTextToken;

            $role = $user->role->slug;

            $profile = match ($role) {
                'doctor' => [
                    'uuid' => $user->doctorProfile?->uuid,
                    'license_number' => $user->doctorProfile?->license_number,
                    'specialization' => $user->doctorProfile?->specialization,
                ],

                'hospital' => [
                    'uuid' => $user->facility?->uuid,
                    'name' => $user->facility?->facility_name,
                    'type' => $user->facility?->facility_type,
                ],

                'blood_bank' => [
                    'uuid' => $user->bloodBank?->uuid,
                    'name' => $user->bloodBank?->name,
                    'registration_number' => $user->bloodBank?->registration_number,
                ],

                default => null,
            };

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                        'uuid' => $user->uuid,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role->slug,
                        'profile' =>  $profile,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to login at this time.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function logout(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Access token is missing or invalid.',
                ], 401);
            }

            $token = $user->currentAccessToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access token not found.',
                ], 401);
            }

            $token->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to logout at this time.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function forgotPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'identifier' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'type' => [
                    'required',
                    'string',
                    'in:password_reset',
                ],
            ]);

            $identifier = trim($validated['identifier']);

            /*
            |--------------------------------------------------------------------------
            | Find User
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->where('email', $identifier)
                ->orWhere('phone', $identifier)
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Do not reveal whether the account exists
            |--------------------------------------------------------------------------
            */

            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'If the account exists, an OTP has been sent.',
                ], 200);
            }

            /*
            |--------------------------------------------------------------------------
            | Remove Existing Password Reset OTPs
            |--------------------------------------------------------------------------
            */

            OtpVerification::query()
                ->where('user_uuid', $user->uuid)
                ->where('type', 'password_reset')
                ->delete();

            /*
            |--------------------------------------------------------------------------
            | Generate OTP
            |--------------------------------------------------------------------------
            */

            $otp = random_int(100000, 999999);

            /*
            |--------------------------------------------------------------------------
            | Store Hashed OTP
            |--------------------------------------------------------------------------
            */

            OtpVerification::create([
                'uuid' => (string) Str::uuid(),
                'user_uuid' => $user->uuid,
                'identifier' => $identifier,
                'type' => 'password_reset',
                'otp_hash' => Hash::make((string) $otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'last_sent_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Send OTP
            |--------------------------------------------------------------------------
            */

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {

                Mail::to($user->email)->send(
                    new PasswordResetOtpMail(
                        user: $user,
                        otp: (string) $otp,
                        type: 'otp',
                    )
                );

            } else {

                $this->sendSmsService->sendSms(
                    $user->phone,
                    $otp
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'If the account exists, an OTP has been sent.',
            ], 200);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process password reset request.',
            ], 500);
        }
    }


    public function verifyOtp(Request $request)
    {
        try {
            $validated = $request->validate([
                'identifier' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'otp' => [
                    'required',
                    'digits:6',
                ],
                'type' => [
                    'required',
                    'string',
                    'in:password_reset',
                ],
            ]);

            $identifier = trim($validated['identifier']);

            /*
            |--------------------------------------------------------------------------
            | Find OTP
            |--------------------------------------------------------------------------
            |
            | No authentication is required here.
            | The user is in the password-reset flow and therefore may not
            | have a valid access token.
            |
            */

            $otpVerification = OtpVerification::query()
                ->where('identifier', $identifier)
                ->where('type', 'password_reset')
                ->whereNull('verified_at')
                ->latest()
                ->first();

            if (!$otpVerification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired OTP.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Expiration
            |--------------------------------------------------------------------------
            */

            if ($otpVerification->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new OTP.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Maximum Attempts
            |--------------------------------------------------------------------------
            */

            if ($otpVerification->attempts >= 5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many invalid OTP attempts. Please request a new OTP.',
                ], 429);
            }

            /*
            |--------------------------------------------------------------------------
            | Verify OTP
            |--------------------------------------------------------------------------
            */

            if (!Hash::check(
                (string) $validated['otp'],
                $otpVerification->otp_hash
            )) {

                $otpVerification->increment('attempts');

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Mark OTP as Verified
            |--------------------------------------------------------------------------
            */

            $otpVerification->update([
                'verified_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Get User
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->where('uuid', $otpVerification->user_uuid)
                ->first();

            if (!$user) {
                /*
                | This should normally never happen because the OTP belongs
                | to an existing user.
                */

                $otpVerification->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify password reset request.',
                ], 500);
            }

            /*
            |--------------------------------------------------------------------------
            | Send Verification Confirmation
            |--------------------------------------------------------------------------
            */

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {

                Mail::to($user->email)->send(
                    new PasswordResetOtpMail(
                        user: $user,
                        type: 'verified',
                    )
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully.',
            ], 200);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process OTP verification request.',
            ], 500);
        }
    }


    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'identifier' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ],
            ]);

            $identifier = trim($validated['identifier']);

            /*
            |--------------------------------------------------------------------------
            | Find User
            |--------------------------------------------------------------------------
            */

            $user = User::query()
                ->where('email', $identifier)
                ->orWhere('phone', $identifier)
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to complete password reset request.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Verified Password Reset OTP
            |--------------------------------------------------------------------------
            */

            $otpVerification = OtpVerification::query()
                ->where('identifier', $identifier)
                ->where('type', 'password_reset')
                ->where('user_uuid', $user->uuid)
                ->whereNotNull('verified_at')
                ->latest('verified_at')
                ->first();

            if (!$otpVerification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your OTP before resetting your password.',
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | Make Sure Verification Has Not Expired
            |--------------------------------------------------------------------------
            */

            if ($otpVerification->isExpired()) {

                $otpVerification->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Password reset verification has expired. Please request a new OTP.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | Change Password
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use (
                $user,
                $otpVerification,
                $validated
            ) {

                $user->update([
                    'password' => Hash::make($validated['password']),
                ]);

                /*
                | Delete the OTP so it cannot be reused.
                */
                $otpVerification->delete();

                /*
                | Revoke all existing Sanctum access tokens.
                |
                | The user must login again using the new password.
                */
                $user->tokens()->delete();
            });

            /*
            |--------------------------------------------------------------------------
            | Send Password Changed Notification
            |--------------------------------------------------------------------------
            */

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {

                Mail::to($user->email)->send(
                    new PasswordResetOtpMail(
                        user: $user,
                        type: 'password_changed',
                    )
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully. Please login with your new password.',
            ], 200);

        } catch (\Throwable $e) {

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to reset password at this time.',
            ], 500);
        }
    }











}