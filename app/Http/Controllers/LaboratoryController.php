<?php

namespace App\Http\Controllers;

use App\Models\LabCenter;
use App\Models\LabOrder;
use App\Models\TestCategory;
use App\Models\LabEquipment;
use App\Models\LabResult;
use App\Models\LabResultParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LaboratoryController extends Controller
{
    /**
     * List all lab orders for the authenticated user (patient or provider).
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // If patient, show only their orders. If there's a provider role, they might see more.
        // For now, let's assume patients see their own orders.
        $orders = LabOrder::with(['patient', 'category', 'labCenter'])
            ->when($user->category === 'patient', function ($query) use ($user) {
                return $query->where('patient_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a new lab order.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:users,id',
            'test_category_id' => 'required|exists:test_categories,id',
            'specific_test_name' => 'required|string|max:255',
            'priority' => 'required|in:Routine,Urgent,Emergency',
            'lab_center_id' => 'required|exists:lab_centers,id',
            'provisional_diagnosis' => 'nullable|string',
            'clinical_notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Specific Validation: similar test ordered recently (e.g., within 24 hours)
        $timeframe = Carbon::now()->subHours(24);
        $similarTest = LabOrder::where('patient_id', $validated['patient_id'])
            ->where('specific_test_name', $validated['specific_test_name'])
            ->where('created_at', '>=', $timeframe)
            ->first();

        if ($similarTest && !$request->has('confirm_duplicate')) {
            return response()->json([
                'status' => false,
                'duplicate_warning' => true,
                'message' => 'A similar test was ordered for this patient within the defined timeframe. Review before proceeding.',
                'previous_order' => $similarTest
            ], 409); // Conflict
        }

        // Handle attachment
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('lab_orders/attachments', 'public');
            $validated['attachment_path'] = $path;
        }

        // Generate unique order ID
        $validated['order_id'] = 'ORD-' . strtoupper(substr(uniqid(), -6));
        $validated['status'] = 'Requested';

        $order = LabOrder::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Lab order created successfully',
            'data' => $order->load(['patient', 'category', 'labCenter'])
        ], 201);
    }

    /**
     * Show a specific lab order.
     */
    public function show($id)
    {
        $order = LabOrder::with(['patient', 'category', 'labCenter'])->where('order_id', $id)->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Lab order not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $order
        ]);
    }

    /**
     * Update a lab order.
     */
    public function update(Request $request, $id)
    {
        $order = LabOrder::where('order_id', $id)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Lab order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'test_category_id' => 'sometimes|exists:test_categories,id',
            'specific_test_name' => 'sometimes|string|max:255',
            'priority' => 'sometimes|in:Routine,Urgent,Emergency',
            'lab_center_id' => 'sometimes|exists:lab_centers,id',
            'provisional_diagnosis' => 'nullable|string',
            'clinical_notes' => 'nullable|string',
            'status' => 'sometimes|in:Requested,Processing,Completed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Lab order updated successfully',
            'data' => $order->load(['patient', 'category', 'labCenter'])
        ]);
    }

    /**
     * Delete a lab order.
     */
    public function destroy($id)
    {
        $order = LabOrder::find($id);
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Lab order not found'], 404);
        }

        $order->delete();

        return response()->json([
            'status' => true,
            'message' => 'Lab order deleted successfully'
        ]);
    }

    /**
     * List all test categories.
     */
    public function categories()
    {
        return response()->json([
            'status' => true,
            'data' => TestCategory::all()
        ]);
    }

    /**
     * List all lab centers.
     */
    /**
     * List all lab centers.
     */
    public function labCenters()
    {
        return response()->json([
            'status' => true,
            'data' => LabCenter::all()
        ]);
    }

    // --- Equipment Endpoints ---

    /**
     * List all lab equipment.
     */
    public function equipmentIndex()
    {
        return response()->json([
            'status' => true,
            'data' => LabEquipment::with('labCenter')->get()
        ]);
    }

    /**
     * Create new lab equipment.
     */
    public function equipmentStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'serial_number' => 'required|string|unique:lab_equipment,serial_number',
            'type' => 'required|string|max:255',
            'lab_center_id' => 'required|exists:lab_centers,id',
            'manufacturer' => 'nullable|string',
            'model_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $equipment = LabEquipment::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Equipment added successfully',
            'data' => $equipment
        ], 201);
    }


    public function equipmentDelete($id)
    {
        $equipment = LabEquipment::find($id);
        if (!$equipment) {
            return response()->json(['status' => false, 'message' => 'Equipment not found'], 404);
        }

        $equipment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Equipment deleted successfully'
        ]);
    }
    // --- Results Endpoints ---

    /**
     * List all lab results.
     */
    public function resultsIndex()
    {
        $results = LabResult::with(['order.patient', 'order.category', 'equipment', 'parameters'])
            ->orderBy('date_completed', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $results
        ]);
    }

    /**
     * Store a lab result.
     */
    public function resultsStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lab_order_id' => 'required|exists:lab_orders,id',
            'signed_off_by_name' => 'required|string',
            'signed_off_by_title' => 'nullable|string',
            'signed_off_by_gmc' => 'nullable|string',
            'date_completed' => 'required|date',
            'doctor_comment' => 'nullable|string',
            'overall_flag' => 'required|in:Normal,Abnormal,Critical',
            'equipment_ids' => 'required|array|min:1',
            'equipment_ids.*' => 'exists:lab_equipment,id',
            'parameters' => 'required|array|min:1',
            'parameters.*.parameter_name' => 'required|string',
            'parameters.*.result_value' => 'required|string',
            'parameters.*.unit' => 'nullable|string',
            'parameters.*.reference_range' => 'nullable|string',
            'parameters.*.flag' => 'required|in:Normal,Abnormal,Critical',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $result = LabResult::create([
            'lab_order_id' => $validated['lab_order_id'],
            'signed_off_by_name' => $validated['signed_off_by_name'],
            'signed_off_by_title' => $validated['signed_off_by_title'],
            'signed_off_by_gmc' => $validated['signed_off_by_gmc'],
            'date_completed' => $validated['date_completed'],
            'doctor_comment' => $validated['doctor_comment'],
            'overall_flag' => $validated['overall_flag'],
            'status' => 'Completed',
        ]);

        // Link equipment
        $result->equipment()->attach($validated['equipment_ids']);

        // Add parameters
        foreach ($validated['parameters'] as $param) {
            $result->parameters()->create($param);
        }

        // Update Order status to Completed
        LabOrder::where('id', $validated['lab_order_id'])->update(['status' => 'Completed']);

        return response()->json([
            'status' => true,
            'message' => 'Lab result stored successfully',
            'data' => $result->load(['order', 'equipment', 'parameters'])
        ], 201);
    }

    /**
     * Show a lab result.
     */
    public function resultsShow($id)
    {
        $result = LabResult::with(['order.patient', 'order.category', 'equipment', 'parameters'])->find($id);

        if (!$result) {
            return response()->json(['status' => false, 'message' => 'Lab result not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }
}
