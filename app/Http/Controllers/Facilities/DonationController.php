<?php
namespace App\Http\Controllers\Facilities;
use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\LabTest;
use App\Models\BloodComponent;
use App\Models\BloodCollection;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DonationController extends Controller
{


    public function getAllDonations(Request $request)
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
                'search' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:50',
                'status' => 'nullable|in:pending,accepted,rejected',
                'source' => 'nullable|string|max:100',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $query = Donation::query()
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->with([
                    'donor',
                    'bloodCollection',
                    'recordedBy',
                ]);

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($q) use ($search) {

                    $q->where('unit_id', 'like', "%{$search}%")
                        ->orWhere('donation_number', 'like', "%{$search}%")
                        ->orWhere('blood_group', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhereHas('donor', function ($donorQuery) use ($search) {

                            $donorQuery->where('donor_number', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%")
                                // ->orWhere('first_name', 'like', "%{$search}%")
                                // ->orWhere('last_name', 'like', "%{$search}%")
                                ;
                        });
                });
            }

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['blood_group'])) {
                $query->where(
                    'blood_group',
                    $validated['blood_group']
                );
            }

            if (!empty($validated['status'])) {
                $query->where(
                    'status',
                    $validated['status']
                );
            }

            if (!empty($validated['source'])) {
                $query->where(
                    'source',
                    $validated['source']
                );
            }

            if (!empty($validated['date_from'])) {
                $query->whereDate(
                    'donation_date',
                    '>=',
                    $validated['date_from']
                );
            }

            if (!empty($validated['date_to'])) {
                $query->whereDate(
                    'donation_date',
                    '<=',
                    $validated['date_to']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $perPage = $validated['per_page'] ?? 20;

            $donations = $query
                ->latest('donation_date')
                ->paginate($perPage)
                ->withQueryString();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Donations retrieved successfully.',
                'data' => [
                    'donations' => $donations->items(),

                    'pagination' => [
                        'current_page' => $donations->currentPage(),
                        'last_page' => $donations->lastPage(),
                        'per_page' => $donations->perPage(),
                        'total' => $donations->total(),
                        'from' => $donations->firstItem(),
                        'to' => $donations->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve donations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recordDonation(Request $request, string $donor_uuid)
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
            | Validate Collection Details
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

            $donor = $bloodBank->donors()
                ->where('uuid', $donor_uuid)
                ->first();

            if (!$donor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Latest Eligibility Screening
            |--------------------------------------------------------------------------
            */

            $screening = $donor->screenings()
                ->latest('screening_date')
                ->first();

            if (!$screening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donor has not completed an eligibility assessment.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Donor Must Be Eligible
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

            if ($screening->bloodCollection) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood has already been collected for this screening.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Blood Collection + Donation
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(function () use (
                $validated,
                $bloodBank,
                $donor,
                $screening,
                $user
            ) {

                /*
                |--------------------------------------------------------------------------
                | Blood Collection
                |--------------------------------------------------------------------------
                */

                $collection = new BloodCollection();

                $collection->uuid = (string) Str::uuid();
                $collection->label = $validated['unit_id'];
                $collection->volume = $validated['volume'];
                $collection->collected_at = now();
                $collection->status = 'collected';
                $collection->notes = $validated['notes'] ?? null;

                $collection->bloodBank()
                    ->associate($bloodBank);

                $collection->donor()
                    ->associate($donor);

                $collection->screening()
                    ->associate($screening);

                $collection->collectedBy()
                    ->associate($user);

                $collection->save();

                /*
                |--------------------------------------------------------------------------
                | Generate Donation Number
                |--------------------------------------------------------------------------
                */

                $donationNumber = 'DON-' .
                    now()->format('YmdHis') .
                    '-' .
                    strtoupper(Str::random(4));

                /*
                |--------------------------------------------------------------------------
                | Create Donation
                |--------------------------------------------------------------------------
                */

                $donation = new Donation();

                $donation->uuid = (string) Str::uuid();
                $donation->donation_number = $donationNumber;
                $donation->unit_id = $validated['unit_id'];

                $donation->blood_group = $donor->blood_group;

                $donation->source = $donor->donor_type ?? 'walk_in';

                $donation->volume = $validated['volume'];
                $donation->volume_unit = 'ml';

                $donation->donation_date = now();

                /*
                |--------------------------------------------------------------------------
                | Initial Donation Status
                |--------------------------------------------------------------------------
                */

                $donation->status = 'pending';

                $donation->notes = $validated['notes'] ?? null;

                $donation->bloodBank()
                    ->associate($bloodBank);

                $donation->donor()
                    ->associate($donor);

                $donation->bloodCollection()
                    ->associate($collection);

                $donation->recordedBy()
                    ->associate($user);

                $donation->save();

                return [
                    'collection' => $collection,
                    'donation' => $donation,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Donation recorded successfully and is pending laboratory processing.',
                'data' => [
                    'collection' => $result['collection']->load([
                        'donor',
                        'screening',
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
                'message' => 'Failed to record donation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function viewDonation(Request $request, string $uuid)
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
            | Find Donation
            |--------------------------------------------------------------------------
            */

            $donation = Donation::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->with([
                    'donor',
                    'bloodCollection',
                    'recordedBy',
                ])
                ->first();

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donation not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Laboratory Test
            |--------------------------------------------------------------------------
            */

            $labTest = null;

            if ($donation->blood_collection_uuid) {
                $labTest = LabTest::where(
                    'collection_uuid',
                    $donation->blood_collection_uuid
                )
                    ->where('blood_bank_uuid', $bloodBank->uuid)
                    ->first();
            }

            /*
            |--------------------------------------------------------------------------
            | Get Components
            |--------------------------------------------------------------------------
            */

            $components = BloodComponent::where(
                'blood_collection_uuid',
                $donation->blood_collection_uuid
            )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Donation retrieved successfully.',
                'data' => [
                    'donation' => $donation,

                    'donor' => $donation->donor,

                    'blood_collection' => $donation->bloodCollection,

                    'laboratory_test' => $labTest,

                    'components' => $components,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve donation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateDonation(Request $request, string $uuid)
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
            | Find Donation
            |--------------------------------------------------------------------------
            */

            $donation = Donation::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donation not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Request
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'unit_id' => [
                    'sometimes',
                    'string',
                    'max:100',
                    'unique:donations,unit_id,' . $donation->id,
                ],

                'blood_group' => [
                    'sometimes',
                    'string',
                    'max:50',
                ],

                'source' => [
                    'sometimes',
                    'string',
                    'max:100',
                ],

                'volume' => [
                    'sometimes',
                    'numeric',
                    'min:1',
                ],

                'volume_unit' => [
                    'sometimes',
                    'string',
                    'max:20',
                ],

                'donation_date' => [
                    'sometimes',
                    'date',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Prevent Changes That Could Break Processing
            |--------------------------------------------------------------------------
            */

            if ($donation->status !== 'pending') {

                $restrictedFields = [
                    'unit_id',
                    'blood_group',
                    'volume',
                    'volume_unit',
                    'donation_date',
                ];

                $attemptedRestrictedUpdate = !empty(array_intersect(array_keys($validated),$restrictedFields));

                if ($attemptedRestrictedUpdate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Processed donations cannot have their core donation details changed.',
                        'status' => $donation->status,
                    ], 422);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Donation
            |--------------------------------------------------------------------------
            */

            $donation->update($validated);

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Donation updated successfully.',
                'data' => [
                    'donation' => $donation->fresh([
                        'donor',
                        'bloodCollection',
                        'recordedBy',
                    ]),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update donation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteDonation(Request $request, string $uuid)
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
            | Find Donation
            |--------------------------------------------------------------------------
            */

            $donation = Donation::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donation not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Only Pending Donations Can Be Deleted
            |--------------------------------------------------------------------------
            */

            if ($donation->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending donations can be deleted.',
                    'status' => $donation->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Related Components
            |--------------------------------------------------------------------------
            */

            $hasComponents = BloodComponent::where(
                'blood_collection_uuid',
                $donation->blood_collection_uuid
            )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->exists();

            if ($hasComponents) {
                return response()->json([
                    'success' => false,
                    'message' => 'This donation cannot be deleted because blood components have already been created.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Donation + Collection
            |--------------------------------------------------------------------------
            */

            DB::transaction(function () use ($donation) {

                $collection = $donation->bloodCollection;

                $donation->delete();

                if ($collection) {
                    $collection->delete();
                }
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Donation deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete donation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


}