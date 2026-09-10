<?php

namespace App\Http\Controllers\Facilities;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Transfusion;
use App\Models\BloodComponent;
use App\Models\BloodRequest;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TransfusionController extends Controller
{
    public function recordTransfusion(Request $request)
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
                'patient_uuid' => [
                    'required',
                    'uuid',
                    'exists:patients,uuid',
                ],

                'blood_request_uuid' => [
                    'nullable',
                    'uuid',
                    'exists:blood_request,uuid',
                ],

                'blood_component_uuid' => [
                    'required',
                    'uuid',
                    'exists:blood_components,uuid',
                ],

                'start_time' => [
                    'required',
                    'date',
                ],

                'proposed_end_time' => [
                    'nullable',
                    'date',
                    'after:start_time',
                ],

                // 'assigned_staff_uuid' => [
                //     'nullable',
                //     'uuid',
                    //'exists:users,uuid',
                //],
                'assigned_staff_name' => [
                    'nullable',
                    'string',
                    //'exists:users,uuid',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:1000',
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



            $component = BloodComponent::where('uuid', $validated['blood_component_uuid'])->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            // if ($component->status !== 'Issued') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Only issued blood components can be used for transfusion.',
            //         'status' => $component->status,
            //     ], 422);
            // }

            $bloodRequest = null;

            if (!empty($validated['blood_request_uuid'])) {

                $bloodRequest = BloodRequest::where('uuid',$validated['blood_request_uuid'])
                    ->where('doctor_uuid', $user->DoctorProfile->uuid)
                    ->first();

                if (!$bloodRequest) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Blood request not found or does not belong to this doctor.',
                    ], 404);
                }
            }


            $existingTransfusion = Transfusion::where('blood_component_uuid',$component->uuid)
                ->whereIn('status', [
                    'in_progress',
                    'completed',
                    'reaction',
                ])
                ->first();

            if ($existingTransfusion) {
                return response()->json([
                    'success' => false,
                    'message' => 'A transfusion has already been recorded for this blood component.',
                ], 409);
            }

            $transfusion = DB::transaction(function () use ($validated,$user,$patient,$component,$bloodRequest) 
            {

                $transfusionNumber = 'TRF-' .now()->format('YmdHis') .'-' .strtoupper(Str::random(4));

                return Transfusion::create([
                    'uuid' => (string) Str::uuid(),

                    'patient_uuid' => $patient->uuid,

                    'doctor_uuid' => $user->DoctorProfile->uuid,

                    'blood_request_uuid' =>$bloodRequest?->uuid,

                    'blood_component_uuid' =>$component->uuid,

                    'transfusion_number' =>$transfusionNumber,

                    'blood_group' =>$component->blood_group,

                    'component_type' =>$component->component_type,

                    'blood_unit_id' =>$component->component_id,

                    'start_time' =>$validated['start_time'],

                    'proposed_end_time' =>$validated['proposed_end_time'] ?? null,

                    'actual_end_time' =>null,

                    'assigned_staff_uuid' =>$validated['assigned_staff_uuid'] ?? null,

                    'assigned_staff_name' =>$validated['assigned_staff_name'] ?? null,

                    'status' =>'in_progress',

                    'notes' =>$validated['notes'] ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Transfusion recorded successfully.',
                'data' => [
                    'transfusion' => $transfusion->load([
                        'patient',
                        'doctor',
                        'bloodRequest',
                        'bloodComponent',
                        //'assignedStaff',
                    ]),
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to record transfusion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllTransfusions(Request $request)
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
                'search' => 'nullable|string|max:100',
                'status' => 'nullable|string|max:50',
                'component_type' => 'nullable|string|max:100',
                'blood_group' => 'nullable|string|max:10',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Transfusion::where('doctor_uuid',$user->DoctorProfile->uuid)->with([
                            'patient',
                            'bloodComponent',
                            'bloodRequest',
                            'assignedStaff',
            ]);

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($query) use ($search) {

                    $query->where('transfusion_number', 'like', "%{$search}%")
                        ->orWhere('blood_unit_id', 'like', "%{$search}%")
                        ->orWhere('blood_group', 'like', "%{$search}%")
                        ->orWhere('component_type', 'like', "%{$search}%")
                        ->orWhereHas('patient', function ($patientQuery) use ($search) {
                            $patientQuery->where(
                                'full_name',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'patient_number',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            }

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['component_type'])) {
                $query->where(
                    'component_type',
                    $validated['component_type']
                );
            }

            if (!empty($validated['blood_group'])) {
                $query->where(
                    'blood_group',
                    $validated['blood_group']
                );
            }

            $perPage = $validated['per_page'] ?? 20;

            $transfusions = $query->latest('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Transfusions retrieved successfully.',
                'data' => $transfusions->items(),
                'pagination' => [
                    'current_page' => $transfusions->currentPage(),
                    'last_page' => $transfusions->lastPage(),
                    'per_page' => $transfusions->perPage(),
                    'total' => $transfusions->total(),
                    'from' => $transfusions->firstItem(),
                    'to' => $transfusions->lastItem(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve transfusions.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewTransfusion(Request $request, string $uuid)
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

            $transfusion = Transfusion::where('uuid', $uuid)->where('doctor_uuid', $user->DoctorProfile->uuid)
                ->with([
                    'patient',
                    'doctor',
                    'bloodRequest',
                    'bloodComponent',
                    'assignedStaff',
                ])
                ->first();

            if (!$transfusion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfusion not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transfusion retrieved successfully.',
                'data' => $transfusion,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to view transfusion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTransfusion(Request $request, string $uuid)
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

            $transfusion = Transfusion::where('uuid', $uuid)->where('doctor_uuid', $user->DoctorProfile->uuid)->first();

            if (!$transfusion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transfusion not found.',
                ], 404);
            }

            $validated = $request->validate([
                'status' => [
                    'required',
                    'in:in_progress,completed,reaction',
                ],

                'actual_end_time' => [
                    'nullable',
                    'date',
                ],

                'assigned_staff_name' => [
                    'nullable',
                    'string',
                    //'exists:users,uuid',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


            if (in_array($validated['status'], [
                    'completed',
                    'reaction',
                ])
                && empty($validated['actual_end_time'])
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Actual end time is required when completing or ending a transfusion.',
                ], 422);
            }

            $transfusion->update([
                'status' => $validated['status'],
                'actual_end_time' =>$validated['actual_end_time']?? $transfusion->actual_end_time,
                'assigned_staff_uuid' =>$validated['assigned_staff_uuid']?? $transfusion->assigned_staff_uuid,
                'notes' =>$validated['notes']?? $transfusion->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfusion updated successfully.',
                'data' => $transfusion->fresh([
                    'patient',
                    'bloodRequest',
                    'bloodComponent',
                    'assignedStaff',
                ]),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update transfusion.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function transfusionHistory(Request $request)
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
                'status' => 'nullable|in:completed,reaction',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Transfusion::where('doctor_uuid', $user->DoctorProfile->uuid)->whereIn('status', [ 'completed', 'reaction',])
            ->with([
                'patient',
                'bloodComponent',
                'bloodRequest',
                //'assignedStaff',
            ]);

            if (!empty($validated['status'])) {
                $query->where('status',$validated['status']);
            }

            $perPage = $validated['per_page'] ?? 20;

            $history = $query->latest('actual_end_time')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Transfusion history retrieved successfully.',
                'data' => $history->items(),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'from' => $history->firstItem(),
                    'to' => $history->lastItem(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve transfusion history.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    Public function deleteTransfusionRecord(Request $request, string $uuid)
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

            $transfusion = Transfusion::where('uuid', $uuid)->where('doctor_uuid', $doctor->uuid)->first();

            if (!$transfusion) {
                return response()->json([
                    'success' => false,
                    'message' => 'transfusion record not found or does not belong to this doctor.',
                ], 404);
            }

            DB::transaction(function () use ($transfusion) {

                $transfusion->delete();

            });

            return response()->json([
                'success' => true,
                'message' => 'Transfusion record deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete transfusion record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}