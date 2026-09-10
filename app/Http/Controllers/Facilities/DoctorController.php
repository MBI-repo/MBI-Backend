<?php
namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\BloodBank;
use App\Models\BloodRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



class DoctorController extends Controller
{
    public function createRequest(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            if (!$user->DoctorProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'MBI Blood Bank not found.',
                ], 404);
            }

            $validated = $request->validate([

                'patient_uuid' => [
                    'required',
                    'uuid',
                    'exists:patients,uuid',
                ],

                'request_type' => [
                    'nullable',
                    'string',
                    'in:patient,emergency,elective,stock_replenishment',
                ],

                'priority' => [
                    'required',
                    'string',
                    'in:low,normal,urgent,emergency',
                ],

                'component_type' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'unit_needed' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'department' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'note' => [
                    'nullable',
                    'string',
                ],

                'request_date' => [
                    'nullable',
                    'date',
                ],

                'request_time' => [
                    'nullable',
                    'date_format:H:i',
                ],

                'blood_group' => [
                    'nullable',
                    'string',
                    'max:10',
                ],

                'clinical_reason' => [
                    'nullable',
                    'string',
                ],

                'required_at' => [
                    'nullable',
                    'date',
                ],
            ]);

            $patient = Patient::where('uuid', $validated['patient_uuid'])
                ->where('doctor_uuid', $user->DoctorProfile->uuid)
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found or does not belong to this doctor.',
                ], 404);
            }

            if (
                !empty($validated['blood_group']) &&
                $validated['blood_group'] !== $patient->blood_group
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requested blood group does not match the patient blood group.',
                    'patient_blood_group' => $patient->blood_group,
                ], 422);
            }
            

            $requestNumber = 'REQ-' .now()->format('YmdHis') .'-' .strtoupper(Str::random(4));

            $bloodRequest = DB::transaction(function () use ( $validated,$user,$patient,$bloodBank,$requestNumber) 
            {

                return BloodRequest::create([

                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $bloodBank->uuid,

                    'facility_uuid' => $user->DoctorProfile->facility_uuid,

                    'doctor_uuid' => $user->DoctorProfile->uuid,

                    'patient_uuid' => $patient->uuid,

                    'full_name' => $validated['full_name'] ?? $patient->full_name,

                    'request_number' => $requestNumber,

                    'request_type' =>$validated['request_type'] ?? 'patient',

                    'priority' =>$validated['priority'],

                    'component_type' =>$validated['component_type'],

                    'unit_needed' =>$validated['unit_needed'],

                    'department' =>$validated['department'],

                    'note' =>$validated['note'] ?? null,

                    'request_date' =>$validated['request_date'] ?? now()->toDateString(),

                    'request_time' =>$validated['request_time'] ?? now()->format('H:i'),

                    'blood_group' =>$patient->blood_group,

                    'clinical_reason' =>$validated['clinical_reason'] ?? null,

                    'required_at' =>$validated['required_at'] ?? null,

                    'status' => 'pending',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Blood request created successfully.',
                'data' => [
                    'blood_request' => $bloodRequest->load([
                        'patient',
                        'doctor',
                    ]),
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to create blood request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getHistory(Request $request)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $doctor = $user->doctorProfile;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'MBI Blood Bank not found.',
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

                'component_type' => [
                    'nullable',
                    'string',
                ],

                'department' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'gender' => [
                    'nullable',
                    'string',
                    'in:male,female',
                ],

                'blood_group' => [
                    'nullable',
                    'string',
                    'in:A,B,C,D,E,F,G',
                ],
            ]);

            $query = BloodRequest::where('doctor_uuid',$doctor->uuid);

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($query) use ($search) {

                    $query->where(
                        'request_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'full_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'priority',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'blood_group',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'status',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'department',
                        'like',
                        "%{$search}%"
                    );

                });
            }

            if (!empty($validated['component_type'])) {

                $query->where(
                    'component_type',
                    $validated['component_type']
                );
            }

            if (!empty($validated['department'])) {

                $query->where(
                    'department',
                    $validated['department']
                );
            }

            if (!empty($validated['gender'])) {

                $query->where(
                    'gender',
                    $validated['gender']
                );
            }

            if (!empty($validated['blood_group'])) {

                $query->where(
                    'blood_group',
                    $validated['blood_group']
                );
            }

            $perPage = $validated['per_page'] ?? 20;

            $requests = $query->latest('created_at')->paginate($perPage,['*'],'page',$validated['page'] ?? 1);

            return response()->json([
                'success' => true,
                'message' => 'Request history retrieved successfully.',
                'data' => $requests->items(),

                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                    'from' => $requests->firstItem(),
                    'to' => $requests->lastItem(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve request history.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateRequest(Request $request, string $uuid)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $doctor = $user->DoctorProfile;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'MBI Blood Bank not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([

                'patient_uuid' => [
                    'sometimes',
                    'uuid',
                    'exists:patients,uuid',
                ],

                'request_type' => [
                    'sometimes',
                    'string',
                    'in:patient,emergency,elective,stock_replenishment',
                ],

                'priority' => [
                    'sometimes',
                    'string',
                    'in:low,normal,urgent,emergency',
                ],

                'component_type' => [
                    'sometimes',
                    'string',
                    'max:100',
                ],

                'unit_needed' => [
                    'sometimes',
                    'integer',
                    'min:1',
                ],

                'department' => [
                    'sometimes',
                    'string',
                    'max:255',
                ],

                'note' => [
                    'sometimes',
                    'nullable',
                    'string',
                ],

                'request_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],

                'request_time' => [
                    'sometimes',
                    'nullable',
                    'date_format:H:i',
                ],

                'blood_group' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:10',
                ],

                'clinical_reason' => [
                    'sometimes',
                    'nullable',
                    'string',
                ],

                'required_at' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Find Blood Request
            |--------------------------------------------------------------------------
            */

            $bloodRequest = BloodRequest::where('uuid', $uuid)->where('doctor_uuid', $doctor->uuid)->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found or does not belong to this doctor.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Resolve Patient
            |--------------------------------------------------------------------------
            */

            $patient = null;

            if (isset($validated['patient_uuid'])) {

                $patient = Patient::where('uuid',$validated['patient_uuid'])->first();

                if (!$patient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Patient not found.',
                    ], 404);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Request
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use ($bloodRequest,$validated,$patient) 
            {

                $data = [];

                if (array_key_exists('patient_uuid', $validated)) {

                    $data['patient_uuid'] = $patient->uuid;

                    // If blood group is patient-based, update it as well.
                    $data['blood_group'] = $patient->blood_group;
                }

                if (array_key_exists('request_type', $validated)) {
                    $data['request_type'] = $validated['request_type'];
                }

                if (array_key_exists('priority', $validated)) {
                    $data['priority'] = $validated['priority'];
                }

                if (array_key_exists('component_type', $validated)) {
                    $data['component_type'] = $validated['component_type'];
                }

                if (array_key_exists('unit_needed', $validated)) {
                    $data['unit_needed'] = $validated['unit_needed'];
                }

                if (array_key_exists('department', $validated)) {
                    $data['department'] = $validated['department'];
                }

                if (array_key_exists('note', $validated)) {
                    $data['note'] = $validated['note'];
                }

                if (array_key_exists('request_date', $validated)) {
                    $data['request_date'] = $validated['request_date'];
                }

                if (array_key_exists('request_time', $validated)) {
                    $data['request_time'] = $validated['request_time'];
                }

                if (array_key_exists('blood_group', $validated)) {
                    $data['blood_group'] = $validated['blood_group'];
                }

                if (array_key_exists('clinical_reason', $validated)) {
                    $data['clinical_reason'] = $validated['clinical_reason'];
                }

                if (array_key_exists('required_at', $validated)) {
                    $data['required_at'] = $validated['required_at'];
                }

                $bloodRequest->update($data);
            });

            return response()->json([
                'success' => true,
                'message' => 'Blood request updated successfully.',
                'data' => [
                    'blood_request' => $bloodRequest->fresh()->load([
                        'patient',
                        'doctor',
                    ]),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update blood request.',
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

            $doctor = $user->DoctorProfile;

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $bloodRequest = BloodRequest::where('uuid', $uuid)
                ->where('doctor_uuid', $doctor->uuid)
                ->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found or does not belong to this doctor.',
                ], 404);
            }

            DB::transaction(function () use ($bloodRequest) {

                $bloodRequest->delete();

            });

            return response()->json([
                'success' => true,
                'message' => 'Request deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete request.',
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

            if (!$user->DoctorProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Doctor not found.',
                ], 404);
            }

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'MBI Blood Bank not found.',
                ], 404);
            }

            $bloodRequest = BloodRequest::where('uuid', $uuid)
                ->where('doctor_uuid', $user->DoctorProfile->uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->with([
                    'patient',
                    'doctor',
                ])
                ->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood request not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Blood request retrieved successfully.',
                'data' => $bloodRequest,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to view blood request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}