<?php

namespace App\Http\Controllers\Facilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodBank;
use App\Models\Equipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ]);
        }

        if(!$user->bloodBank){
            return response()->json([
                'success' => false,
                'message' => 'Blood bank not found',
            ]);
        }
        $bloodBank = $user->bloodBank;
        return response()->json([
            'success' => true,
            'bloodBank' => $bloodBank,
        ], 200);
    }


    public function getAllEquipment(Request $request)
    {
        try {

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $query = Equipment::where('blood_bank_uuid',$bloodBank->uuid);

            if ($request->filled('search')) {

                $search = $request->search;

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere(
                            'equipment_code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'category',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'manufacturer',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'model',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'serial_number',
                            'like',
                            "%{$search}%"
                        );
                });
            }

            if ($request->filled('status')) {
                $query->where(
                    'status',
                    $request->status
                );
            }

            if ($request->filled('category')) {
                $query->where('category',$request->category);
            }

            $equipment = $query->latest()->paginate($request->integer('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Equipment retrieved successfully.',
                'data' => $equipment,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addEquipment(Request $request)
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
                'name' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'equipment_code' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:equipment,equipment_code',
                ],

                'category' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'manufacturer' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'purpose' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'uses' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'replacement' => [
                    'required',
                    'string',
                    'in:yes,no',
                ],
                'frequency' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'department' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'trainning' => [
                    'required',
                    'string',
                    'in:yes,no',
                ],
                'image' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'condition' => [
                    'required',
                    'string',
                    'in:good,poor',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],
            ]);

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $existingEquipment = Equipment::where('blood_bank_uuid', $bloodBank->uuid)
                    ->where('equipment_code', $validated['equipment_code'])
                    ->where('condition',$validated['condition'] ?? 'good')
                    ->first();

            if ($existingEquipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This equipment code has already been registered.',
                ], 409);
            }

            $data = DB::transaction(function () use ($validated,$bloodBank) {

              $equipment = Equipment::create([

                    'uuid' => (string) Str::uuid(),

                    

                    'blood_bank_uuid' => $bloodBank->uuid,

                    'name' => $validated['name'],

                    'equipment_code' =>$validated['equipment_code'],

                    'category' =>$validated['category'] ?? null,

                    'manufacturer' =>$validated['manufacturer'] ?? null,

                    'purpose' =>$validated['purpose'] ?? null,

                    'uses' =>$validated['uses'] ?? null,

                    'replacement' =>$validated['replacement'] ?? null,

                    'frequency' =>$validated['frequency'] ?? null,

                    'department' =>$validated['department'] ?? null,

                    'trainning' =>$validated['trainning'] ?? null,

                    'image' =>$validated['image'] ?? null,

                    'condition' =>$validated['condition'] ?? null,

                    'description' =>$validated['description'] ?? null,
                ]);

                return $equipment;
            });

            return response()->json([
                'success' => true,
                'message' => 'Equipment added successfully.',
                'data' => $data,
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to add equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateEquipment(Request $request,string $uuid) 
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

            $equipment = Equipment::where('uuid', $uuid)->where('blood_bank_uuid', $user->bloodBank->uuid)->first();

            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found.',
                ], 404);
            }

            $validated = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                ],

                'equipment_code' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    'unique:equipment,equipment_code,' .$equipment->id,
                ],

                'category' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100',
                ],

                'manufacturer' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'model' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'serial_number' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'purchase_date' => [
                    'nullable',
                    'date',
                ],

                'installation_date' => [
                    'nullable',
                    'date',
                ],

                'status' => [
                    'sometimes',
                    'required',
                    'string',
                    'in:active,inactive,maintenance,retired',
                ],

                'description' => [
                    'nullable',
                    'string',
                ],
            ]);

            $duplicate = Equipment::where('blood_bank_uuid',$user->bloodBank->uuid)
                    ->where('equipment_code', $validated['equipment_code'])
                    ->where('uuid', '!=', $equipment->uuid)
                    ->first();

                if ($duplicate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This equipment code has already been registered.',
                    ], 409);
                }

            DB::transaction(function () use ($equipment,$validated) {

                $equipment->update($validated);
        
            });

            return response()->json([
                'success' => true,
                'message' => 'Equipment updated successfully.',
                'data' => $equipment->fresh(),
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to update equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function viewEquipment(string $uuid)
    {
        try {

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $equipment = Equipment::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Equipment retrieved successfully.',
                'data' => $equipment,
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteEquipment(string $uuid)
    {
        try {

            $bloodBank = BloodBank::first();

            if (!$bloodBank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Blood bank not found.',
                ], 404);
            }

            $equipment = Equipment::where('uuid', $uuid)
                ->where('blood_bank_uuid', $bloodBank->uuid)
                ->first();

            if (!$equipment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Equipment not found.',
                ], 404);
            }

            $equipment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Equipment deleted successfully.',
            ], 200);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete equipment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

    
    



