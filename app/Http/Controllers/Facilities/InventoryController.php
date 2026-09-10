<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use App\Models\BloodBank;
use App\Models\BloodRequest;
use App\Models\BloodComponent;
use App\Models\InventoryReservation;
use App\Models\InventoryTransaction;
use App\Models\Donation;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailDonorMail;
use App\Services\CallService;


class InventoryController extends Controller
{

    public function addToInventory(Request $request, string $uuid)
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
            | Find Blood Component
            |--------------------------------------------------------------------------
            */

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Component Status
            |--------------------------------------------------------------------------
            */

            if ($component->status !== 'Ready for Storage') {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component is not ready to be added to inventory.',
                    'status' => $component->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Donation
            |--------------------------------------------------------------------------
            */

            $donation = Donation::where(
                    'blood_collection_uuid',
                    $component->blood_collection_uuid
                )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$donation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Donation associated with this blood component was not found.',
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
                    'message' => 'Only components from accepted donations can be added to inventory.',
                    'donation_status' => $donation->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Laboratory Test
            |--------------------------------------------------------------------------
            */

            $labTest = LabTest::where(
                    'collection_uuid',
                    $component->blood_collection_uuid
                )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$labTest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laboratory test associated with this component was not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Laboratory Test Must Be Safe
            |--------------------------------------------------------------------------
            */

            if ($labTest->is_safe !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only blood components from safe laboratory results can be added to inventory.',
                    'is_safe' => $labTest->is_safe,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate STOCK_IN
            |--------------------------------------------------------------------------
            */

            $existingTransaction = InventoryTransaction::where(
                    'blood_component_uuid',
                    $component->uuid
                )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->where('transaction_type', 'STOCK_IN')
                ->first();

            if ($existingTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'This blood component has already been added to inventory.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Expiry Alert
            |--------------------------------------------------------------------------
            */

            $expiryAlert = null;

            if ($component->expiry_date) {

                $daysUntilExpiry = now()->startOfDay()->diffInDays(
                    $component->expiry_date,
                    false
                );

                $expiryAlert = match (true) {
                    $daysUntilExpiry < 0 => 'Expired',
                    $daysUntilExpiry <= 7 => 'Expiring Soon',
                    default => 'Normal',
                };
            }

            /*
            |--------------------------------------------------------------------------
            | Add Component To Inventory
            |--------------------------------------------------------------------------
            */

            $transaction = DB::transaction(function () use (
                $component,
                $bloodBank,
                $user,
                $expiryAlert
            ) {

                $transaction = InventoryTransaction::create([
                    'uuid' => (string) Str::uuid(),

                    'blood_bank_uuid' => $bloodBank->uuid,

                    'blood_collection_uuid' =>
                        $component->blood_collection_uuid,

                    'blood_component_uuid' =>
                        $component->uuid,

                    'transaction_type' => 'STOCK_IN',

                    'blood_group' =>
                        $component->blood_group,

                    'component_type' =>
                        $component->component_type,

                    'storage_type' =>
                        $component->storage_type,

                    'previous_status' =>
                        $component->status,

                    'stock_status' =>
                        'Available',

                    'expiry_alert' =>
                        $expiryAlert,

                    'reference_type' =>
                        'blood_component',

                    'reference_uuid' =>
                        $component->uuid,

                    'performed_by_uuid' =>
                        $user->uuid,

                    'reason' =>
                        'Blood component received into inventory.',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Component Status
                |--------------------------------------------------------------------------
                */

                $component->update([
                    'status' => 'Available',
                ]);

                return $transaction;
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Blood component successfully added to inventory.',
                'data' => [
                    'transaction' => $transaction,
                    'component' => $component->fresh(),
                    'donation' => $donation,
                    'laboratory_test' => $labTest,
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to add blood component to inventory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllInventory(Request $request)
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
                'search' => 'nullable|string|max:255',
                'blood_group' => 'nullable|string|max:20',
                'component_type' => 'nullable|string|max:100',
                'status' => 'nullable|string|max:100',
                'storage_type' => 'nullable|string|max:100',
                'expiry' => 'nullable|in:normal,expiring_soon,expired',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Inventory Query
            |--------------------------------------------------------------------------
            */

            $query = BloodComponent::query()
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->where('status', '!=', 'Ready for Storage');

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['search'])) {

                $search = $validated['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('component_id', 'like', "%{$search}%")
                        ->orWhere('blood_group', 'like', "%{$search}%")
                        ->orWhere('component_type', 'like', "%{$search}%")
                        ->orWhere('storage_type', 'like', "%{$search}%");
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

            if (!empty($validated['component_type'])) {
                $query->where(
                    'component_type',
                    $validated['component_type']
                );
            }

            if (!empty($validated['status'])) {
                $query->where(
                    'status',
                    $validated['status']
                );
            }

            if (!empty($validated['storage_type'])) {
                $query->where(
                    'storage_type',
                    $validated['storage_type']
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Expiry Filter
            |--------------------------------------------------------------------------
            */

            if (!empty($validated['expiry'])) {

                $today = now()->startOfDay();
                $sevenDaysFromNow = now()->addDays(7)->endOfDay();

                switch ($validated['expiry']) {

                    case 'expired':

                        $query->whereDate(
                            'expiry_date',
                            '<',
                            $today
                        );

                        break;

                    case 'expiring_soon':

                        $query->whereDate(
                            'expiry_date',
                            '>=',
                            $today
                        )
                        ->whereDate(
                            'expiry_date',
                            '<=',
                            $sevenDaysFromNow
                        );

                        break;

                    case 'normal':

                        $query->whereDate(
                            'expiry_date',
                            '>',
                            $sevenDaysFromNow
                        );

                        break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $perPage = $validated['per_page'] ?? 20;

            /*
            |--------------------------------------------------------------------------
            | Load Inventory
            |--------------------------------------------------------------------------
            */

            $inventory = $query
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Inventory retrieved successfully.',
                'data' => [
                    'inventory' => $inventory->items(),
                    'pagination' => [
                        'current_page' => $inventory->currentPage(),
                        'last_page' => $inventory->lastPage(),
                        'per_page' => $inventory->perPage(),
                        'total' => $inventory->total(),
                        'from' => $inventory->firstItem(),
                        'to' => $inventory->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewInventory(Request $request, string $uuid)
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
            | Find Inventory Component
            |--------------------------------------------------------------------------
            */

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->whereIn('status', [
                    'Available',
                    'Reserved',
                    'Issued',
                    'Transferred',
                    'Discarded',
                    'Expired',
                ])
                ->with([
                    'donor',
                    'bloodCollection',
                    'laboratoryTest',
                    'inventoryTransactions' => function ($query) {
                        $query->latest();
                    },
                ])
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Inventory item not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Calculate Expiry Information
            |--------------------------------------------------------------------------
            */

            $expiryAlert = null;
            $daysUntilExpiry = null;

            if ($component->expiry_date) {

                $daysUntilExpiry = now()->startOfDay()->diffInDays(
                    $component->expiry_date,
                    false
                );

                $expiryAlert = match (true) {
                    $daysUntilExpiry < 0 => 'Expired',
                    $daysUntilExpiry <= 7 => 'Expiring Soon',
                    default => 'Normal',
                };
            }

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Inventory item retrieved successfully.',
                'data' => [
                    'component' => [
                        'uuid' => $component->uuid,
                        'component_id' => $component->component_id,
                        'component_type' => $component->component_type,
                        'blood_group' => $component->blood_group,
                        'volume' => $component->volume,
                        'volume_unit' => $component->volume_unit,
                        'storage_type' => $component->storage_type,
                        'status' => $component->status,
                        'expiry_date' => $component->expiry_date,
                        'expiry_alert' => $expiryAlert,
                        'days_until_expiry' => $daysUntilExpiry,
                        'created_at' => $component->created_at,
                        'updated_at' => $component->updated_at,
                    ],

                    'donor' => $component->donor,

                    'blood_collection' => $component->bloodCollection,

                    'laboratory_test' => $component->laboratoryTest,

                    'transactions' => $component->inventoryTransactions,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getInventoryHistory(Request $request, string $uuid)
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
            | Find Blood Component
            |--------------------------------------------------------------------------
            */

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Pagination
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $validated['per_page'] ?? 20;

            /*
            |--------------------------------------------------------------------------
            | Get Inventory Transaction History
            |--------------------------------------------------------------------------
            */

            $transactions = InventoryTransaction::query()
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->where('blood_component_uuid', $component->uuid)
                ->with([
                    'performedBy',
                ])
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Inventory history retrieved successfully.',
                'data' => [
                    'component' => [
                        'uuid' => $component->uuid,
                        'component_id' => $component->component_id,
                        'component_type' => $component->component_type,
                        'blood_group' => $component->blood_group,
                        'status' => $component->status,
                    ],

                    'transactions' => $transactions->items(),

                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                        'from' => $transactions->firstItem(),
                        'to' => $transactions->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory history.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function inventoryDashboardSummary(Request $request)
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
            | Base Inventory Query
            |--------------------------------------------------------------------------
            */

            $inventoryQuery = BloodComponent::query()
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->whereIn('status', [
                    'Available',
                    'Reserved',
                    'Issued',
                    'Transferred',
                    'Discarded',
                    'Expired',
                ]);

            /*
            |--------------------------------------------------------------------------
            | Date Helpers
            |--------------------------------------------------------------------------
            */

            $today = now()->startOfDay();

            $sevenDaysFromNow = now()
                ->addDays(7)
                ->endOfDay();

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            $totalInventory = (clone $inventoryQuery)->count();

            $available = (clone $inventoryQuery)
                ->where('status', 'Available')
                ->count();

            $reserved = (clone $inventoryQuery)
                ->where('status', 'Reserved')
                ->count();

            $issued = (clone $inventoryQuery)
                ->where('status', 'Issued')
                ->count();

            $transferred = (clone $inventoryQuery)
                ->where('status', 'Transferred')
                ->count();

            $discarded = (clone $inventoryQuery)
                ->where('status', 'Discarded')
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Expiry Summary
            |--------------------------------------------------------------------------
            */

            $expiringSoon = (clone $inventoryQuery)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $sevenDaysFromNow)
                ->whereNotIn('status', [
                    'Discarded',
                    'Expired',
                ])
                ->count();

            $expired = (clone $inventoryQuery)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', $today)
                ->whereNotIn('status', [
                    'Discarded',
                ])
                ->count();

            /*
            |--------------------------------------------------------------------------
            | Component Breakdown
            |--------------------------------------------------------------------------
            */

            $componentBreakdown = (clone $inventoryQuery)
                ->selectRaw('component_type, COUNT(*) as total')
                ->groupBy('component_type')
                ->orderByDesc('total')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Blood Group Breakdown
            |--------------------------------------------------------------------------
            */

            $bloodGroupBreakdown = (clone $inventoryQuery)
                ->whereNotNull('blood_group')
                ->selectRaw('blood_group, COUNT(*) as total')
                ->groupBy('blood_group')
                ->orderBy('blood_group')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Storage Breakdown
            |--------------------------------------------------------------------------
            */

            $storageBreakdown = (clone $inventoryQuery)
                ->whereNotNull('storage_type')
                ->selectRaw('storage_type, COUNT(*) as total')
                ->groupBy('storage_type')
                ->orderByDesc('total')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Inventory dashboard summary retrieved successfully.',

                'data' => [

                    'summary' => [
                        'total_inventory' => $totalInventory,
                        'available' => $available,
                        'reserved' => $reserved,
                        'issued' => $issued,
                        'transferred' => $transferred,
                        'discarded' => $discarded,
                        'expiring_soon' => $expiringSoon,
                        'expired' => $expired,
                    ],

                    'component_breakdown' => $componentBreakdown,

                    'blood_group_breakdown' => $bloodGroupBreakdown,

                    'storage_breakdown' => $storageBreakdown,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve inventory dashboard summary.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reserveInventory(Request $request,string $uuid) 
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
            | Validate Request
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'blood_request_uuid' => [
                    'required',
                    'uuid',
                    'exists:blood_request,uuid',
                ],

                'expires_at' => [
                    'nullable',
                    'date',
                    'after:now',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Find Component
            |--------------------------------------------------------------------------
            */

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Availability
            |--------------------------------------------------------------------------
            */

            if ($component->status !== 'Available') {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component is not available for reservation.',
                    'status' => $component->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Blood Request
            |--------------------------------------------------------------------------
            */

            $bloodRequest = BloodRequest::where(
                'uuid',
                $validated['blood_request_uuid']
            )->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood request not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Make Sure Request Belongs to a Valid Request Flow
            |--------------------------------------------------------------------------
            |
            | Adjust these statuses to your actual blood_requests statuses.
            |
            */

            if (!in_array($bloodRequest->status, [
                'pending',
                'approved',
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This blood request cannot receive a reservation.',
                    'status' => $bloodRequest->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent Existing Reservation
            |--------------------------------------------------------------------------
            */

            $existingReservation = InventoryReservation::where(
                    'blood_component_uuid',
                    $component->uuid
                )
                ->where('status', 'active')
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'This blood component is already reserved.',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | Create Reservation
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(function () use (
                $component,
                $bloodBank,
                $bloodRequest,
                $user,
                $validated
            ) {

                $reservation = InventoryReservation::create([
                    'uuid' => Str::uuid(),
                    'blood_bank_uuid' => $bloodBank->uuid,
                    'blood_component_uuid' => $component->uuid,
                    'blood_request_uuid' => $bloodRequest->uuid,
                    'reserved_by_uuid' => $user->uuid,
                    'reserved_at' => now(),
                    'expires_at' => $validated['expires_at'] ?? null,
                    'status' => 'active',
                    'notes' => $validated['notes'] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Inventory Transaction
                |--------------------------------------------------------------------------
                */

                InventoryTransaction::create([
                    'uuid' => Str::uuid(),
                    'blood_bank_uuid' => $bloodBank->uuid,
                    'blood_collection_uuid' =>
                        $component->blood_collection_uuid,
                    'blood_component_uuid' => $component->uuid,
                    'transaction_type' => 'RESERVE',
                    'blood_group' => $component->blood_group,
                    'component_type' => $component->component_type,
                    'storage_type' => $component->storage_type,
                    'previous_status' => $component->status,
                    'stock_status' => 'Reserved',
                    'expiry_alert' => null,
                    'reference_type' => 'blood_request',
                    'reference_uuid' => $bloodRequest->uuid,
                    'performed_by_uuid' => $user->uuid,
                    'reason' => 'Blood component reserved for blood request.',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Current Component Status
                |--------------------------------------------------------------------------
                */

                $component->update([
                    'status' => 'Reserved',
                ]);

                return $reservation;
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Blood component reserved successfully.',
                'data' => [
                    'reservation' => $result->load([
                        'bloodComponent',
                        'bloodRequest',
                        'reservedBy',
                    ]),
                ],
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to reserve blood component.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function issueInventory(Request $request, string $uuid)
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
            | Validate Request
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'blood_request_uuid' => [
                    'required',
                    'uuid',
                    'exists:blood_request,uuid',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Find Blood Component
            |--------------------------------------------------------------------------
            */

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Component Status
            |--------------------------------------------------------------------------
            */

            if ($component->status !== 'Reserved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only reserved blood components can be issued.',
                    'status' => $component->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Active Reservation
            |--------------------------------------------------------------------------
            */

            $reservation = InventoryReservation::where(
                    'blood_component_uuid',
                    $component->uuid
                )
                ->where(
                    'blood_request_uuid',
                    $validated['blood_request_uuid']
                )
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->where('status', 'active')
                ->first();

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active reservation was found for this blood request.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Check Reservation Expiry
            |--------------------------------------------------------------------------
            */

            if (
                $reservation->expires_at &&
                $reservation->expires_at->isPast()
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'This reservation has expired.',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Find Blood Request
            |--------------------------------------------------------------------------
            */

            $bloodRequest = BloodRequest::where(
                'uuid',
                $validated['blood_request_uuid']
            )->first();

            if (!$bloodRequest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood request not found.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Validate Blood Request Status
            |--------------------------------------------------------------------------
            |
            | Adjust these statuses to match your actual blood request workflow.
            |
            */

            if (!in_array($bloodRequest->status, [
                'approved',
                'reserved',
                'ready_for_issue',
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This blood request cannot receive an issued blood component.',
                    'status' => $bloodRequest->status,
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Issue Blood Component
            |--------------------------------------------------------------------------
            */

            $result = DB::transaction(function () use (
                $component,
                $reservation,
                $bloodBank,
                $bloodRequest,
                $user,
                $validated
            ) {

                /*
                |--------------------------------------------------------------------------
                | Inventory Transaction
                |--------------------------------------------------------------------------
                */

                $transaction = InventoryTransaction::create([
                    'uuid' => Str::uuid(),

                    'blood_bank_uuid' =>
                        $bloodBank->uuid,

                    'blood_collection_uuid' =>
                        $component->blood_collection_uuid,

                    'blood_component_uuid' =>
                        $component->uuid,

                    'transaction_type' =>
                        'ISSUE',

                    'blood_group' =>
                        $component->blood_group,

                    'component_type' =>
                        $component->component_type,

                    'storage_type' =>
                        $component->storage_type,

                    'previous_status' =>
                        $component->status,

                    'stock_status' =>
                        'Issued',

                    'expiry_alert' =>
                        null,

                    'reference_type' =>
                        'blood_request',

                    'reference_uuid' =>
                        $bloodRequest->uuid,

                    'performed_by_uuid' =>
                        $user->uuid,

                    'reason' =>
                        'Blood component issued against blood request.'
                        . (
                            !empty($validated['notes'])
                                ? ' ' . $validated['notes']
                                : ''
                        ),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Component
                |--------------------------------------------------------------------------
                */

                $component->update([
                    'status' => 'Issued',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Complete Reservation
                |--------------------------------------------------------------------------
                */

                $reservation->update([
                    'status' => 'fulfilled',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Return Transaction
                |--------------------------------------------------------------------------
                */

                return $transaction;
            });

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Blood component issued successfully.',
                'data' => [
                    'transaction' => $result,

                    'component' => $component->fresh(),

                    'reservation' => $reservation->fresh(),

                    'blood_request' => $bloodRequest,
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to issue blood component.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function returnInventory(Request $request, string $uuid)
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
                'notes' => 'nullable|string|max:1000',
            ]);

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            if ($component->status !== 'Issued') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only issued blood components can be returned.',
                    'status' => $component->status,
                ], 422);
            }

            $transaction = DB::transaction(function () use (
                $component,
                $bloodBank,
                $user,
                $validated
            ) {

                $transaction = InventoryTransaction::create([
                    'uuid' => Str::uuid(),
                    'blood_bank_uuid' => $bloodBank->uuid,
                    'blood_collection_uuid' => $component->blood_collection_uuid,
                    'blood_component_uuid' => $component->uuid,
                    'transaction_type' => 'RETURN',
                    'blood_group' => $component->blood_group,
                    'component_type' => $component->component_type,
                    'storage_type' => $component->storage_type,
                    'previous_status' => $component->status,
                    'stock_status' => 'Available',
                    'expiry_alert' => null,
                    'reference_type' => 'blood_component',
                    'reference_uuid' => $component->uuid,
                    'performed_by_uuid' => $user->uuid,
                    'reason' => $validated['notes']
                        ?? 'Blood component returned to inventory.',
                ]);

                $component->update([
                    'status' => 'Available',
                ]);

                return $transaction;
            });

            return response()->json([
                'success' => true,
                'message' => 'Blood component successfully returned to inventory.',
                'data' => [
                    'transaction' => $transaction,
                    'component' => $component->fresh(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to return blood component.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function discardInventory(Request $request, string $uuid)
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
                'reason' => 'required|string|max:1000',
            ]);

            $component = BloodComponent::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$component) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood component not found.',
                ], 404);
            }

            if (in_array($component->status, [
                'Discarded',
                'Expired',
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This blood component can no longer be discarded.',
                    'status' => $component->status,
                ], 422);
            }

            if (!in_array($component->status, [
                'Available',
                'Reserved',
                'Issued',
            ])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This blood component cannot be discarded from its current status.',
                    'status' => $component->status,
                ], 422);
            }

            $transaction = DB::transaction(function () use (
                $component,
                $bloodBank,
                $user,
                $validated
            ) {

                $transaction = InventoryTransaction::create([
                    'uuid' => Str::uuid(),
                    'blood_bank_uuid' => $bloodBank->uuid,
                    'blood_collection_uuid' => $component->blood_collection_uuid,
                    'blood_component_uuid' => $component->uuid,
                    'transaction_type' => 'DISCARD',
                    'blood_group' => $component->blood_group,
                    'component_type' => $component->component_type,
                    'storage_type' => $component->storage_type,
                    'previous_status' => $component->status,
                    'stock_status' => 'Discarded',
                    'expiry_alert' => null,
                    'reference_type' => 'blood_component',
                    'reference_uuid' => $component->uuid,
                    'performed_by_uuid' => $user->uuid,
                    'reason' => $validated['reason'],
                ]);

                $component->update([
                    'status' => 'Discarded',
                ]);

                return $transaction;
            });

            return response()->json([
                'success' => true,
                'message' => 'Blood component discarded successfully.',
                'data' => [
                    'transaction' => $transaction,
                    'component' => $component->fresh(),
                ],
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to discard blood component.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function transferInventory(Request $request, string $uuid)
    // {
    //     try {

    //         $user = $request->user();

    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'User not found.',
    //             ], 401);
    //         }

    //         $bloodBank = $user->bloodBank;

    //         if (!$bloodBank) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Blood bank not found.',
    //             ], 404);
    //         }

    //         $validated = $request->validate([
    //             'destination_blood_bank_uuid' => [
    //                 'required',
    //                 'uuid',
    //                 'exists:blood_banks,uuid',
    //             ],

    //             'notes' => [
    //                 'nullable',
    //                 'string',
    //                 'max:1000',
    //             ],
    //         ]);

    //         if (
    //             $validated['destination_blood_bank_uuid']
    //             === $bloodBank->uuid
    //         ) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Destination blood bank must be different from the current blood bank.',
    //             ], 422);
    //         }

    //         $destinationBloodBank = BloodBank::where(
    //             'uuid',
    //             $validated['destination_blood_bank_uuid']
    //         )->first();

    //         if (!$destinationBloodBank) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Destination blood bank not found.',
    //             ], 404);
    //         }

    //         $component = BloodComponent::where('uuid', $uuid)
    //             ->where('blood_bank_uuid', $bloodBank->uuid)
    //             ->first();

    //         if (!$component) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Blood component not found.',
    //             ], 404);
    //         }

    //         if (!in_array($component->status, [
    //             'Available',
    //             'Reserved',
    //         ])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Only available or reserved blood components can be transferred.',
    //                 'status' => $component->status,
    //             ], 422);
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Transfer
    //         |--------------------------------------------------------------------------
    //         */

    //         $transaction = DB::transaction(function () use (
    //             $component,
    //             $bloodBank,
    //             $destinationBloodBank,
    //             $user,
    //             $validated
    //         ) {

    //             $transaction = InventoryTransaction::create([
    //                 'uuid' => Str::uuid(),
    //                 'blood_bank_uuid' => $bloodBank->uuid,
    //                 'blood_collection_uuid' => $component->blood_collection_uuid,
    //                 'blood_component_uuid' => $component->uuid,
    //                 'transaction_type' => 'TRANSFER',
    //                 'blood_group' => $component->blood_group,
    //                 'component_type' => $component->component_type,
    //                 'storage_type' => $component->storage_type,
    //                 'previous_status' => $component->status,
    //                 'stock_status' => 'Transferred',
    //                 'expiry_alert' => null,
    //                 'reference_type' => 'blood_bank',
    //                 'reference_uuid' => $destinationBloodBank->uuid,
    //                 'performed_by_uuid' => $user->uuid,
    //                 'reason' => $validated['notes']
    //                     ?? 'Blood component transferred to another blood bank.',
    //             ]);

    //             $component->update([
    //                 'status' => 'Transferred',
    //             ]);

    //             return $transaction;
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Blood component transferred successfully.',
    //             'data' => [
    //                 'transaction' => $transaction,
    //                 'component' => $component->fresh(),
    //                 'destination' => [
    //                     'uuid' => $destinationBloodBank->uuid,
    //                     'name' => $destinationBloodBank->name,
    //                 ],
    //             ],
    //         ], 200);

    //     } catch (\Throwable $e) {

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to transfer blood component.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


}
