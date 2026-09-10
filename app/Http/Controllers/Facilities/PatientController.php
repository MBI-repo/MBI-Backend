<?php
namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class PatientController extends Controller
{
    public function getAllPatients(Request $request)
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
                'patient_number' => [
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
                    'in:A+,A-,B+,B-,AB+,AB-,O+,O-',
                ],
                'department' => [
                    'nullable',
                    'string',
                ],
            ]);

            $query = Patient::where('doctor_uuid',$user->DoctorProfile->uuid);

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($query) use ($search) {

                    $query->where(
                        'patient_number',
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
                        'address',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'department',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(  
                        'status',
                        'like',
                        "%{$search}%"
                    );

                });
            }

            if (!empty($validated['patient_number'])) {

                $query->where('patient_number',$validated['patient_number']);
            }

            if (!empty($validated['gender'])) {

                $query->where('gender',$validated['gender']);
            }

            if (!empty($validated['blood_group'])) {

                $query->where('blood_group',$validated['blood_group']);
            }
            if (!empty($validated['department'])) {

                $query->where('department',$validated['department']);
            }

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $perPage = $validated['per_page'] ?? 20;

            $patients = $query->latest('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Patients retrieved successfully.',
                'data' => $patients->items(),

                'pagination' => [
                    'current_page' => $patients->currentPage(),
                    'last_page' => $patients->lastPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                    'from' => $patients->firstItem(),
                    'to' => $patients->lastItem(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve patients.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewPatient(Request $request, string $uuid)
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

            $patient = Patient::where('uuid', $uuid)->where('doctor_uuid', $user->DoctorProfile->uuid)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Patient retrieved successfully.',
                'data' => $patient,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve patient.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createPatient(Request $request)
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

            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'contact' => 'required|string|unique:patients|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|string|max:255',
                'blood_group' => [
                    'nullable',
                    'string',
                    'max:10',
                ],
                'genotype' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:255',
                'department' => 'required|string|max:255',
            ]);

            $validated['patient_number'] = 'PAT-' . strtoupper(Str::random(8));
            $validated['user_uuid'] = $user->uuid;
            $validated['facility_uuid'] = $user->DoctorProfile->facility_uuid;
            $validated['doctor_uuid'] = $user->DoctorProfile->uuid;

            $existingPatient = Patient::where('doctor_uuid', $user->DoctorProfile->uuid)
                    ->where('patient_number', $validated['patient_number'])
                    ->where('contact', $validated['contact'])
                    ->first();

                if ($existingPatient) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This patient has already been registered.',
                    ], 409);
                }
        

            $patient = DB::transaction(function () use ($validated) {

                return Patient::create([
                    'uuid'=> (string) Str::uuid(),
                    'patient_number' => $validated['patient_number'],
                    'user_uuid' => $validated['user_uuid'],
                    'facility_uuid' => $validated['facility_uuid'],
                    'doctor_uuid' => $validated['doctor_uuid'],
                    'full_name' => $validated['full_name'],
                    'contact' => $validated['contact'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'gender' => $validated['gender'],
                    'blood_group' => $validated['blood_group'],
                    'genotype' => $validated['genotype'],
                    'email' => $validated['email'],
                    'address' => $validated['address'],
                    'department' => $validated['department'],
                    
                    //...$validated,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Patient created successfully.',
                'data' => $patient,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to create patient.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function updatePatient(Request $request, string $uuid)
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

            $validated = $request->validate([
                'full_name'     => 'sometimes|string|max:255',
                'contact'       => 'sometimes|string|max:255',
                'date_of_birth' => 'sometimes|date',
                'gender'        => 'sometimes|string|max:255',
                'blood_group'   => 'sometimes|string|max:255',
                'genotype'      => 'sometimes|string|max:255',
                'email'         => 'sometimes|email|max:255',
                'address'       => 'sometimes|string|max:255',
                'department'    => 'sometimes|string|max:255',
            ]);

            if (empty($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data to update.',
                ], 400);
            }

            $patient = Patient::where('uuid', $uuid)
                ->where('doctor_uuid', $user->DoctorProfile->uuid)
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.',
                ], 404);
            }

            DB::transaction(function () use ($patient, $validated) {
                $patient->update($validated);
            });

            return response()->json([
                'success' => true,
                'message' => 'Patient updated successfully.',
                'data' => $patient->refresh(),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to update patient.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function deletePatient(Request $request, string $uuid)
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

            $patient = Patient::where('uuid', $uuid)->where('doctor_uuid', $user->DoctorProfile->uuid)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient not found.',
                ], 404);
            }

            DB::transaction(function () use ($patient) {

                $patient->delete();

            });

           

            return response()->json([
                'success' => true,
                'message' => 'Patient deleted successfully.',

            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete patient.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}