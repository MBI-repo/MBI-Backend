<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use App\Models\Donor;
use App\Models\DonorScreening;
use App\Models\BloodCollection;
use App\Models\LabTest;
use App\Models\BloodComponent;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class ScreenController extends Controller
{

    public function index(Request $request, string $uuid)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $data = Donor::query()
                ->where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->whereIn('donor_category', [
                    'new_donor',
                    'existing_mbi_patient',
                ])
                ->first();

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor details not found or not a walk-in donor.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Donor details have been retrieved successfully.',
                'data' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving donor details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function screenRecord(Request $request, string $uuid)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $validated = $request->validate([

                'age' => [
                    'required',
                    'integer',
                    'min:18',
                ],

                'weight' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'systolic_bp' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'diastolic_bp' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'recent_fever_or_infection' => [
                    'required',
                    'boolean',
                ],

                'infectious_disease_exposure' => [
                    'required',
                    'boolean',
                ],

                'recent_tattoo_or_piercing' => [
                    'required',
                    'boolean',
                ],

                'high_risk_travel_history' => [
                    'required',
                    'boolean',
                ],

                'existing_medical_condition' => [
                    'required',
                    'boolean',
                ],

                'recent_surgery' => [
                    'required',
                    'boolean',
                ],

                'current_medication' => [
                    'required',
                    'boolean',
                ],

                'feeling_unwell' => [
                    'required',
                    'boolean',
                ],

                'deferral_reason' => [
                    'nullable',
                    'string',
                ],

                'deferred_until' => [
                    'nullable',
                    'date',
                ],

                'notes' => [
                    'nullable',
                    'string',
                ],
            ]);

            $donor = Donor::where('uuid', $uuid) ->where('blood_bank_uuid',$bloodBank->uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            $record = DB::transaction(function () use ($validated,$donor,$user) 
            {

                return DonorScreening::create([

                    'uuid' => (string) Str::uuid(),

                    'donor_uuid' => $donor->uuid,

                    'screening_date' => now(),

                    'age' => $validated['age'],

                    'weight' => $validated['weight'],

                    'systolic_bp' => $validated['systolic_bp'],

                    'diastolic_bp' => $validated['diastolic_bp'],

                    'recent_fever_or_infection' =>$validated['recent_fever_or_infection'],

                    'infectious_disease_exposure' =>$validated['infectious_disease_exposure'],

                    'recent_tattoo_or_piercing' =>$validated['recent_tattoo_or_piercing'],

                    'high_risk_travel_history' =>$validated['high_risk_travel_history'],

                    'existing_medical_condition' =>$validated['existing_medical_condition'],

                    'recent_surgery' =>$validated['recent_surgery'],

                    'current_medication' =>$validated['current_medication'],

                    'feeling_unwell' =>$validated['feeling_unwell'],

                    'eligibility' =>'eligible',

                    'deferral_reason' =>$validated['deferral_reason'] ?? null,

                    'deferred_until' =>$validated['deferred_until'] ?? null,

                    'notes' =>$validated['notes'] ?? null,

                    'screened_by_uuid' => $user->uuid ?? null,
                ]);

               
            });

             $donor->update([
                'eligibility' =>  'eligible'
                    ? 'eligible'
                    : 'not eligible',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Donor screening record has been created.',
                'data' => $record,
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error creating screening record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pendingRecord(Request $request)
    {
        //echo"pendingRecord";
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
                        'message' => 'User does not have a blood bank.',
                    ], 400);
                }

                $bloodBank = $user->bloodBank;

                if (!$bloodBank) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Blood bank not found.',
                    ], 404);
                }

                $bloodBank = $request->user()->bloodBank;

                $records = Donor::where('blood_bank_uuid',$bloodBank->uuid)->where('eligibility','pending')->get();

                if (!$records) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No pending screening records have been retrieved.',
                    ], 404);
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Pending screening records have been retrieved.',
                    'data' => $records,
                ], 200);
            } 
        catch (\Throwable $e) 
            {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving pending screening records.',
                    'error' => $e->getMessage(),
                ], 500);
            }
    }

    public function viewPendingRecord(Request $request , string $uuid)
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
                    'message' => 'User does not have a blood bank.',
                ], 400);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $records = Donor::where('blood_bank_uuid',$bloodBank->uuid)
                    ->where('uuid',$uuid)
                    ->where('eligibility','pending')->first();
             if (!$records) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending screening record found.',
                ], 404);
             }

             return response()->json([
                'success' => true,
                'message' => 'Pending screening record has been retrieved.',
                'data' => $records,
             ], 200);

        } 
        catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving pending screening record.',
                    'error' => $e->getMessage(),
                ], 500);
        }
    }

    public function collectRecord(Request $request, string $uuid)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'volume' => [
                    'required',
                    'numeric',
                    'min:1',
                ],

                'unit_id' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:donations,unit_id',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Find Donor
            |--------------------------------------------------------------------------
            */

            $donor = $bloodBank->donors()->where('uuid', $uuid)->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Latest Screening
            |--------------------------------------------------------------------------
            */

            $screening = $donor->screenings()->latest('screening_date')->first();

            if (!$screening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor has not completed eligibility screening.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Eligibility
            |--------------------------------------------------------------------------
            */

            if ($screening->eligibility !== 'eligible') {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor is not eligible to donate blood.',
                    'eligibility' => $screening->eligibility,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Collection
            |--------------------------------------------------------------------------
            */

            $existingCollection = $screening->bloodCollection;

            if ($existingCollection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood has already been collected for this screening.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Donation Unit
            |--------------------------------------------------------------------------
            */

            $existingDonation = Donation::where('unit_id', $validated['unit_id'])->first();

            if ($existingDonation) {
                return response()->json([
                    'success' => false,
                    'message' => 'This unit ID has already been used.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Collection + Donation
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(function () use ($validated,$bloodBank,$donor,$screening,$user)
            {

                $collection = new BloodCollection();

                $collection->uuid = Str::uuid();
                $collection->label = $validated['unit_id'];
                $collection->volume = $validated['volume'];
                $collection->collected_at = now();
                $collection->status = 'collected';
                $collection->notes = $validated['notes'] ?? null;

                $collection->bloodBank()->associate($bloodBank);

                $collection->screening()->associate($screening);

                $collection->collectedBy()->associate($user);

                $donor->bloodCollections()->save($collection);


                $donationNumber ='DON-' . now()->format('YmdHis') . '-' .strtoupper(Str::random(4));

                $donation = new Donation();

                $donation->uuid = Str::uuid();
                $donation->donation_number = $donationNumber;
                $donation->unit_id = $validated['unit_id'];

                $donation->blood_group = $donor->blood_group;

                $donation->source = $donor->donor_type?? 'Walk-in';

                $donation->volume = $validated['volume'];
                $donation->volume_unit = 'ml';

                $donation->donation_date = now();

                $donation->status = 'pending';

                $donation->notes = $validated['notes'] ?? null;

                $donation->bloodBank()->associate($bloodBank);

                $donation->donor()->associate($donor);

                $donation->bloodCollection()->associate($collection);

                $donation->recordedBy()->associate($user);

                $donation->save();

                return [
                    'collection' => $collection,
                    'donation' => $donation,
                ];
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Blood collection recorded and donation created successfully.',
                'data' => [
                    'collection' => $result['collection'],
                    'donation' => $result['donation'],
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to record blood collection.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function labRecord(Request $request, string $uuid)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Blood Collection
            |--------------------------------------------------------------------------
            */

            $collection = $bloodBank->bloodCollections()->where('uuid', $uuid)->first();

            if (!$collection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood collection not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Donor
            |--------------------------------------------------------------------------
            */

            $donor = $collection->donor;

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor associated with this collection was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Screening
            |--------------------------------------------------------------------------
            */

            $screening = $collection->screening;

            if (!$screening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor screening record not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Donation
            |--------------------------------------------------------------------------
            */

            $donation = Donation::where('blood_collection_uuid', $collection->uuid)->where('blood_bank_uuid', $bloodBank->uuid)->first();

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donation record associated with this collection was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Donation Must Still Be Pending
            |--------------------------------------------------------------------------
            */

            if ($donation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This donation has already been processed.',
                    'status' => $donation->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'abo_typing' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'rh_factor' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'hiv_result' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'hiv_test_kit_used' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'hepatitis_a' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'hepatitis_b' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'syphilis_result' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'syphilis_test_kit_used' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                /*
                * The laboratory decision is required.
                */
                'is_safe' => [
                    'required',
                    'boolean',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Laboratory Record
            |--------------------------------------------------------------------------
            */

            $existingLabTest = LabTest::where('collection_uuid',$collection->uuid)->first();

            if ($existingLabTest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laboratory test record already exists for this blood collection.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Determine Donation Status
            |--------------------------------------------------------------------------
            */

            $donationStatus = $validated['is_safe']? 'accepted' : 'rejected';

            /*
            |--------------------------------------------------------------------------
            | Save Laboratory Result + Update Donation
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(function () use ($donor,$bloodBank,$screening,$collection,$donation,$validated,$donationStatus)
            {

                /*
                |--------------------------------------------------------------------------
                | Create Laboratory Test
                |--------------------------------------------------------------------------
                */

                $labTest = LabTest::create([
                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $bloodBank->uuid,
                    'donor_uuid' => $donor->uuid,
                    'screening_uuid' => $screening->uuid,
                    'collection_uuid' => $collection->uuid,

                    'abo_typing' => $validated['abo_typing'] ?? null,
                    'rh_factor' => $validated['rh_factor'] ?? null,

                    'hiv_result' => $validated['hiv_result'] ?? null,
                    'hiv_test_kit_used' => $validated['hiv_test_kit_used'] ?? null,

                    'hepatitis_a' => $validated['hepatitis_a'] ?? null,
                    'hepatitis_b' => $validated['hepatitis_b'] ?? null,

                    'syphilis_result' => $validated['syphilis_result'] ?? null,
                    'syphilis_test_kit_used' => $validated['syphilis_test_kit_used'] ?? null,

                    'is_safe' => $validated['is_safe'],
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Donation Status
                |--------------------------------------------------------------------------
                */

                $donation->update([
                    'status' => $donationStatus,
                ]);

                return [
                    'lab_test' => $labTest,
                    'donation' => $donation->fresh(),
                ];
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => $donationStatus === 'accepted'
                    ? 'Laboratory results recorded and donation accepted successfully.'
                    : 'Laboratory results recorded and donation rejected successfully.',

                'data' => [
                    'lab_test' => $result['lab_test']->load([
                        'donor',
                        'screening',
                        'collection',
                    ]),

                    'donation' => $result['donation']->load([
                        'donor',
                        'bloodCollection',
                    ]),
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to record laboratory test results.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function componentRecord(Request $request, string $uuid)
    {
        try {

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 401);
            }

            $bloodBank = $user->bloodBank;

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validation
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([

                'red_blood_cells' => [
                    'required',
                    'array',
                ],

                'red_blood_cells.volume' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],

                'red_blood_cells.component_id' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:blood_components,component_id',
                ],

                'plasma' => [
                    'required',
                    'array',
                ],

                'plasma.volume' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],

                'plasma.component_id' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:blood_components,component_id',
                ],

                'platelets' => [
                    'required',
                    'array',
                ],

                'platelets.volume' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],

                'platelets.component_id' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:blood_components,component_id',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Get Laboratory Test
            |--------------------------------------------------------------------------
            */

            $labTest = LabTest::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->with([
                    'donor',
                    'screening',
                    'collection',
                ])
                ->first();

            if (!$labTest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laboratory test record not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Donor
            |--------------------------------------------------------------------------
            */

            $donor = $labTest->donor;

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor associated with this laboratory test was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Screening
            |--------------------------------------------------------------------------
            */

            $screening = $labTest->screening;

            if (!$screening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor screening associated with this laboratory test was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Blood Collection
            |--------------------------------------------------------------------------
            */

            $collection = $labTest->collection;

            if (!$collection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood collection associated with this laboratory test was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Donation
            |--------------------------------------------------------------------------
            */

            $donation = Donation::where(
                    'blood_collection_uuid',
                    $collection->uuid
                )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donation record associated with this blood collection was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Donation Must Be Accepted
            |--------------------------------------------------------------------------
            */

            if ($donation->status !== 'accepted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood components can only be created for an accepted donation.',
                    'donation_status' => $donation->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Laboratory Test Must Be Safe
            |--------------------------------------------------------------------------
            */

            if ($labTest->is_safe !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood components cannot be created because the laboratory result is not marked as safe.',
                    'is_safe' => $labTest->is_safe,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Component Separation
            |--------------------------------------------------------------------------
            */

            $existingComponents = BloodComponent::where(
                'laboratory_test_uuid',
                $labTest->uuid
            )->exists();

            if ($existingComponents) {
                return response()->json([
                    'success' => false,
                    'message' => 'Component records already exist for this laboratory test.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Components
            |--------------------------------------------------------------------------
            */

            $components = DB::transaction(function () use (
                $validated,
                $bloodBank,
                $donor,
                $screening,
                $collection,
                $labTest
            ) {

                $components = [];

                /*
                |--------------------------------------------------------------------------
                | Red Blood Cells
                |--------------------------------------------------------------------------
                */

                $components[] = BloodComponent::create([
                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $bloodBank->uuid,

                    'donor_uuid' => $donor->uuid,

                    'screening_uuid' => $screening->uuid,

                    'blood_collection_uuid' => $collection->uuid,

                    'laboratory_test_uuid' => $labTest->uuid,

                    'component_type' => 'Red Blood Cells',

                    'component_id' =>
                        $validated['red_blood_cells']['component_id'],

                    'volume' =>
                        $validated['red_blood_cells']['volume'],

                    'volume_unit' => 'ml',

                    'blood_group' =>
                        $labTest->abo_typing && $labTest->rh_factor
                            ? $labTest->abo_typing . $labTest->rh_factor
                            : $donor->blood_group,

                    'expiry_date' => null,

                    'storage_type' => null,

                    'status' => 'Ready for Storage',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Plasma
                |--------------------------------------------------------------------------
                */

                $components[] = BloodComponent::create([
                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $bloodBank->uuid,

                    'donor_uuid' => $donor->uuid,

                    'screening_uuid' => $screening->uuid,

                    'blood_collection_uuid' => $collection->uuid,

                    'laboratory_test_uuid' => $labTest->uuid,

                    'component_type' => 'Plasma',

                    'component_id' =>
                        $validated['plasma']['component_id'],

                    'volume' =>
                        $validated['plasma']['volume'],

                    'volume_unit' => 'ml',

                    'blood_group' =>
                        $labTest->abo_typing && $labTest->rh_factor
                            ? $labTest->abo_typing . $labTest->rh_factor
                            : $donor->blood_group,

                    'expiry_date' => null,

                    'storage_type' => null,

                    'status' => 'Ready for Storage',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Platelets
                |--------------------------------------------------------------------------
                */

                $components[] = BloodComponent::create([
                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $bloodBank->uuid,

                    'donor_uuid' => $donor->uuid,

                    'screening_uuid' => $screening->uuid,

                    'blood_collection_uuid' => $collection->uuid,

                    'laboratory_test_uuid' => $labTest->uuid,

                    'component_type' => 'Platelets',

                    'component_id' =>
                        $validated['platelets']['component_id'],

                    'volume' =>
                        $validated['platelets']['volume'],

                    'volume_unit' => 'ml',

                    'blood_group' =>
                        $labTest->abo_typing && $labTest->rh_factor
                            ? $labTest->abo_typing . $labTest->rh_factor
                            : $donor->blood_group,

                    'expiry_date' => null,

                    'storage_type' => null,

                    'status' => 'Ready for Storage',
                ]);

                return $components;
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Blood component records created successfully.',
                'data' => [
                    'donation' => $donation,
                    'laboratory_test' => $labTest,
                    'components' => $components,
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to record component records.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}