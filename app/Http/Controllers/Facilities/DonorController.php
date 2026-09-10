<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use App\Models\Donor;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailDonorMail;
use App\Services\CallService;


class DonorController extends Controller
{
    public function __construct(
        
        protected CallService $callService)
    {}


    public function createDonor(Request $request,string $donor_type,string $donor_category) 
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

            if (!in_array($donor_type, [
                'external-supply',
                'walk-in',
            ], true)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid donor type.',
                ], 400);
            }

            /*
            |--------------------------------------------------------------------------
            | External supply
            |--------------------------------------------------------------------------
            */

            if ($donor_type === 'external-supply') {

                // External supply does not require a donor category.
                if ($donor_category !== 'supply') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid donor category for external supply.',
                    ], 400);
                }

                $validated = $request->validate([

                    'blood_type' => [
                        'required',
                        'in:A,B,C,D,E,F,G',
                    ],

                    'quantity' => [
                        'required',
                        'integer',
                        'min:1',
                    ],

                    'collection_date' => [
                        'required',
                        'date',
                    ],

                    'source' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'batch_number' => [
                        'required',
                        'string',
                        'unique:donors,batch_number',
                        'max:100',
                    ],


                ]);

                $existingSupply = Donor::where('blood_bank_uuid', $user->bloodBank->uuid)
                    ->where('donor_type', 'external-supply')
                    ->where('batch_number', $validated['batch_number'])
                    ->first();

                if ($existingSupply) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This external blood supply batch has already been registered.',
                    ], 409);
                }

                $validated['donor_type'] = 'external-supply';
                //$validated['donor_category'] = 'supply';

            }

            elseif ($donor_type === 'walk-in' && $donor_category === 'new_donor') 
            {

                $validated = $request->validate([

                    'full_name' => [
                        'required',
                        'string',
                        'max:100',
                    ],

                    'contact' => [
                        'required',
                        'string',
                        'unique:donors,contact',
                        'max:100',
                    ],

                    'email' => [
                        'required',
                        'email',
                        'unique:donors,email',
                        'max:100',
                    ],

                    'date_of_birth' => [
                        'required',
                        'date_format:Y-m-d',
                    ],

                    'gender' => [
                        'required',
                        'in:male,female',
                    ],

                    'existing_condition' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'allergies' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'medical_history' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                ]);

                $existingDonor = Donor::where('blood_bank_uuid', $user->bloodBank->uuid)
                    ->where('donor_type', 'new_donor')
                    ->whereDate('date_of_birth', $validated['date_of_birth'])
                    ->where('full_name', $validated['full_name'])
                    ->where(function ($query) use ($validated) {
                        $query->where('contact', $validated['contact'])
                            ->orWhere('email', $validated['email']);
                    })->first();

                if ($existingDonor) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This donor is already registered with this blood bank.',
                    ], 409);
                }

                $validated['source'] = null;
                $validated['donor_type'] = 'walk-in';
                $validated['donor_category'] = 'new_donor';

            }


            elseif ($donor_type === 'walk-in' && $donor_category === 'existing_mbi_patient') 
            {

                $validated = $request->validate([

                    'patient_id' => [
                        'required',
                        'string',
                        'unique:donors,patient_id',
                        'max:100',
                    ],

                ]);

                $existingDonor = Donor::where('blood_bank_uuid', $user->bloodBank->uuid)
                        ->where('patient_id', $validated['patient_id'])
                        ->first();

                    if ($existingDonor) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This patient is already registered as a donor.',
                            //'donor' => $existingDonor,
                        ], 409);
                    }

                $validated['source'] = null;
                $validated['donor_type'] = 'walk-in';
                $validated['donor_category'] = 'existing_mbi_patient';

            }


            else {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid donor category.',
                ], 400);
            }


            /*
            |--------------------------------------------------------------------------
            | Create donor
            |--------------------------------------------------------------------------
            */

            $donor = DB::transaction(function () use ($user,$validated) {

                $donorNumber = 'DO-' . strtoupper(Str::random(10));
                
                $donor = Donor::create([

                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $user->bloodBank->uuid,

                    'donor_number' => $donorNumber,

                    'full_name' => $validated['full_name'] ?? null,

                    'contact' => $validated['contact'] ?? null,

                    'email' => $validated['email'] ?? null,

                    'date_of_birth' => $validated['date_of_birth'] ?? null,

                    'gender' => $validated['gender'] ?? null,

                    'blood_type' => $validated['blood_type'] ?? null,

                    'blood_group' => $validated['blood_group'] ?? null,//Blood group

                    'medical_history' => $validated['medical_history'] ?? null,

                    'quantity' => $validated['quantity'] ?? null,

                    'collection_date' => $validated['collection_date'] ?? null,

                    'patient_id' => $validated['patient_id'] ?? null,

                    'source' => $validated['source'] ?? null,

                    'batch_number' => $validated['batch_number'] ?? null,

                    'donor_type' => $validated['donor_type'],

                    'existing_condition' => $validated['existing_condition'] ?? null,

                    'allergies' => $validated['allergies'] ?? null,

                    'donor_category' => $validated['donor_category'] ?? null,

                    'eligibility' => $validated['eligibility'] ?? null,

                ]);

                if (!$donor) {
                    throw new \Exception('Donor not created.');
                }

                return $donor;
            });

            //dd($donor);

            return response()->json([
                'success' => true,
                'message' => 'Donor created successfully.',
                'donor' => $donor,
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to create donor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateDonor(Request $request,string $donor_type,string $donor_category,string $uuid) 
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

            $donor = Donor::where('uuid', $uuid)->where('blood_bank_uuid', $user->bloodBank->uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }


            if (!in_array($donor_type, [
                'external-supply',
                'walk-in',
            ], true)) {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid donor type.',
                ], 400);
            }


            if ($donor_type === 'external-supply') {

                if ($donor_category !== 'supply') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid donor category for external supply.',
                    ], 400);
                }

                $validated = $request->validate([

                    'blood_type' => [
                        'sometimes',
                        'in:A,B,C,D,E,F,G',
                    ],

                    'quantity' => [
                        'sometimes',
                        'integer',
                        'min:1',
                    ],

                    'collection_date' => [
                        'sometimes',
                        'date',
                    ],

                    'source' => [
                        'sometimes',
                        'string',
                        'max:255',
                    ],

                    'batch_number' => [
                        'required',
                        'string',
                        'max:100',
                    ],

                ]);



                $duplicate = Donor::where('blood_bank_uuid',$user->bloodBank->uuid)
                    ->where('donor_type', 'external-supply')
                    ->where('batch_number', $validated['batch_number'])
                    ->where('uuid', '!=', $donor->uuid)
                    ->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This external supply batch has already been registered.',
                    ], 409);
                }

                $validated['donor_type'] = 'external-supply';
                $validated['source'] = $validated['source'];
            }


            elseif ( $donor_type === 'walk-in' &&$donor_category === 'new_donor') 
                {

                $validated = $request->validate([

                    'full_name' => [
                        'sometimes',
                        'string',
                        'max:100',
                    ],

                    'contact' => [
                        'sometimes',
                        'string',
                        'max:100',
                    ],

                    'email' => [
                        'required',
                        'email',
                        'max:100',
                    ],

                    'date_of_birth' => [
                        'required',
                        'date_format:Y-m-d',
                    ],

                    'gender' => [
                        'sometimes',
                        'in:male,female',
                    ],

                    'medical_history' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                ]);


                $duplicate = Donor::where('blood_bank_uuid',$user->bloodBank->uuid)
                    ->where('donor_type', 'new_donor')
                    ->where('full_name', $validated['full_name'])
                    ->whereDate('date_of_birth',$validated['date_of_birth'])
                    ->where('uuid', '!=', $donor->uuid)
                    ->where(function ($query) use ($validated) {

                        $query->where(
                            'contact',
                            $validated['contact']
                        )
                        ->orWhere(
                            'email',
                            $validated['email']
                        );

                    })->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This donor is already registered.',
                    ], 409);
                }

                $validated['source'] = null;
                $validated['donor_type'] = 'new_donor';
            }


            elseif ($donor_type === 'walk-in' && $donor_category === 'existing_mbi_patient') 
                {

                $validated = $request->validate([

                    'patient_id' => [
                        'required',
                        'string',
                        'max:100',
                    ],

                ]);

                $duplicate = Donor::where('blood_bank_uuid',$user->bloodBank->uuid)
                    ->where('patient_id', $validated['patient_id'])
                    ->where('uuid', '!=', $donor->uuid)
                    ->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This patient is already registered as a donor.',
                    ], 409);
                }

                $validated['source'] = null;
                $validated['donor_type'] = 'existing_mbi_patient';
            }

            else {

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid donor category.',
                ], 400);
            }


            $donor = DB::transaction(function () use ($donor,$validated) 
            {

                $donor->update([

                    'full_name' => $validated['full_name'] ?? null,

                    'contact' => $validated['contact']?? null,

                    'email' => $validated['email']?? null,

                    'date_of_birth' => $validated['date_of_birth']?? null,

                    'gender' => $validated['gender']?? null,

                    'blood_type' => $validated['blood_type']?? null,

                    'medical_history' => $validated['medical_history']?? null,

                    'quantity' => $validated['quantity']?? null,

                    'collection_date' => $validated['collection_date']?? null,

                    'patient_id' => $validated['patient_id']?? null,

                    'source' => $validated['source']?? null,

                    'batch_number' => $validated['batch_number']?? $donor->batch_number,

                    'donor_type' => $validated['donor_type']?? $donor->donor_type,

                    'donor_category' => $validated['donor_category']?? $donor->donor_category,
                ]);

                return $donor->fresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Donor updated successfully.',
                'donor' => $donor,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update donor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getAllDonors(Request $request)
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

            $validated = $request->validate([
                'search' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'per_page' => [
                    'nullable',
                    'integer',
                    'min:1',
                    'max:100',
                ],

                'page' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'donor_type' => [
                    'nullable',
                    'string',
                    'in:new_donor,existing_mbi_patient,external-supply',
                ],

                'gender' => [
                    'nullable',
                    'string',
                    'in:male,female',
                ],

                'blood_type' => [
                    'nullable',
                    'string',
                    'in:A,B,C,D,E,F,G',
                ],
            ]);

            $query = Donor::where('blood_bank_uuid',$user->bloodBank->uuid);

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($query) use ($search) {

                    $query->where(
                        'donor_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'full_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'contact',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'patient_id',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'source',
                        'like',
                        "%{$search}%"
                    );

                });
            }

            if (!empty($validated['donor_type'])) {

                $query->where('donor_type',$validated['donor_type']);
            }

            if (!empty($validated['gender'])) {

                $query->where('gender',$validated['gender']);
            }

            if (!empty($validated['blood_type'])) {

                $query->where('blood_type',$validated['blood_type']);
            }

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $perPage = $validated['per_page'] ?? 20;

            $donors = $query->latest('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Donors retrieved successfully.',
                'data' => $donors->items(),

                'pagination' => [
                    'current_page' => $donors->currentPage(),
                    'last_page' => $donors->lastPage(),
                    'per_page' => $donors->perPage(),
                    'total' => $donors->total(),
                    'from' => $donors->firstItem(),
                    'to' => $donors->lastItem(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve donors.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewDonor(Request $request, string $uuid)
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

            $donor = Donor::where('uuid', $uuid)->where('blood_bank_uuid', $user->bloodBank->uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Donor retrieved successfully.',
                'donor' => $donor,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve donor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteDonor(Request $request, string $uuid)
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

            $donor = Donor::where('uuid', $uuid)->where('blood_bank_uuid', $user->bloodBank->uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            DB::transaction(function () use ($donor) {

                $donor->delete();

            });

            return response()->json([
                'success' => true,
                'message' => 'Donor deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete donor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function emailDonor(Request $request, string $uuid)
    {
        try{
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
            $donor = Donor::where('uuid', $uuid)->where('blood_bank_uuid', $user->bloodBank->uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            if (!$donor->email) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email is required.',
                ], 400);
            }

            Mail::to($donor->email) ->send(new EmailDonorMail($donor, $donor->email));

            return response()->json([
                'success' => true,
                'message' => ' Email Sent to Donor successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to send email to Donor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function callDonor(Request $request, string $uuid)
    {
        try{

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
            $donor = Donor::where('uuid', $uuid)->where('blood_bank_uuid', $user->bloodBank->uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            $phone = $donor->contact;

            if (!$phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Phone is required.',
                ], 400);
            }

            $response = $this->callService->sendCall($phone);
        
            if (!$response['message']) {
                return response()->json([
                    'success' => false,
                    'message' => $response['message'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => ' Call to Donor successfully.',
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to call Donor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    




}
