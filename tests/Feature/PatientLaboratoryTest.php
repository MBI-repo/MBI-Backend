<?php

namespace Tests\Feature;

use App\Models\LabCenter;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\TestCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientLaboratoryTest extends TestCase
{
    use RefreshDatabase;

    private User $patient;
    private User $otherPatient;
    private TestCategory $category;
    private LabCenter $labCenter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard patients
        $this->patient = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'patient',
            'category' => 'patient',
        ]);

        $this->otherPatient = User::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'password' => bcrypt('password'),
            'user_type' => 'patient',
            'category' => 'patient',
        ]);

        // Create test category and lab center
        $this->category = TestCategory::create([
            'name' => 'Hematology',
            'description' => 'Blood related tests',
        ]);

        $this->labCenter = LabCenter::create([
            'name' => 'Main Clinic Lab',
            'location' => 'Building A',
        ]);
    }

    /**
     * Test patient dashboard stats count critical alerts, pending, completed orders accurately.
     */
    public function test_patient_dashboard_stats_counts_and_structures_correctly(): void
    {
        // Assert unauthenticated users cannot access dashboard stats
        $response = $this->getJson('/api/patient/v1/dashboard/stats');
        $response->assertStatus(401);

        // Authenticate as patient
        Sanctum::actingAs($this->patient);

        // Create 1 completed order (normal result)
        $completedOrder = LabOrder::create([
            'order_id' => 'ORD-000001',
            'patient_id' => $this->patient->id,
            'lab_facility_id' => 1,
            'test_category_id' => $this->category->id,
            'specific_test_name' => 'Complete Blood Count',
            'priority' => 'Routine',
            'lab_center_id' => $this->labCenter->id,
            'status' => 'Completed',
        ]);

        LabResult::create([
            'lab_order_id' => $completedOrder->id,
            'signed_off_by_name' => 'Dr. House',
            'date_completed' => now()->toDateString(),
            'overall_flag' => 'Normal',
            'status' => 'Completed',
        ]);

        // Create 1 pending order (Routine)
        LabOrder::create([
            'order_id' => 'ORD-000002',
            'patient_id' => $this->patient->id,
            'lab_facility_id' => 1,
            'test_category_id' => $this->category->id,
            'specific_test_name' => 'Thyroid Panel',
            'priority' => 'Routine',
            'lab_center_id' => $this->labCenter->id,
            'status' => 'Requested',
        ]);

        // Create 1 emergency pending order (this should count as critical alert)
        LabOrder::create([
            'order_id' => 'ORD-000003',
            'patient_id' => $this->patient->id,
            'lab_facility_id' => 1,
            'test_category_id' => $this->category->id,
            'specific_test_name' => 'Cardiac Enzymes',
            'priority' => 'Emergency',
            'lab_center_id' => $this->labCenter->id,
            'status' => 'Processing',
        ]);

        // Create 1 completed order with critical flag (this should count as critical alert)
        $criticalCompletedOrder = LabOrder::create([
            'order_id' => 'ORD-000004',
            'patient_id' => $this->patient->id,
            'lab_facility_id' => 1,
            'test_category_id' => $this->category->id,
            'specific_test_name' => 'Lipid Profile',
            'priority' => 'Routine',
            'lab_center_id' => $this->labCenter->id,
            'status' => 'Completed',
        ]);

        LabResult::create([
            'lab_order_id' => $criticalCompletedOrder->id,
            'signed_off_by_name' => 'Dr. Watson',
            'date_completed' => now()->toDateString(),
            'overall_flag' => 'Critical',
            'status' => 'Completed',
        ]);

        // Fetch stats
        $response = $this->getJson('/api/patient/v1/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.stats.completed_tests', 2) // ORD-000001, ORD-000004
            ->assertJsonPath('data.stats.pending_orders', 2)    // ORD-000002, ORD-000003
            ->assertJsonPath('data.stats.critical_alerts', 2);  // ORD-000003 (Emergency), ORD-000004 (Critical result)
    }

    /**
     * Test patient can view details of their own lab order, but not others.
     */
    public function test_patient_can_view_own_order_details_and_not_other_patients_orders(): void
    {
        // Authenticate as patient
        Sanctum::actingAs($this->patient);

        // Create order owned by the patient
        $order = LabOrder::create([
            'order_id' => 'ORD-MYOWN1',
            'patient_id' => $this->patient->id,
            'lab_facility_id' => 1,
            'test_category_id' => $this->category->id,
            'specific_test_name' => 'Complete Blood Count',
            'priority' => 'Routine',
            'lab_center_id' => $this->labCenter->id,
            'status' => 'Requested',
        ]);

        // Create order owned by the other patient
        $otherOrder = LabOrder::create([
            'order_id' => 'ORD-OTHER1',
            'patient_id' => $this->otherPatient->id,
            'lab_facility_id' => 1,
            'test_category_id' => $this->category->id,
            'specific_test_name' => 'Urine Culture',
            'priority' => 'Emergency',
            'lab_center_id' => $this->labCenter->id,
            'status' => 'Requested',
        ]);

        // 1. Check successful details retrieval for own order
        $response = $this->getJson("/api/patient/v1/orders/{$order->order_id}");
        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.order_id', 'ORD-MYOWN1');

        // 2. Check forbidden response when requesting other patient's order details
        $responseForbidden = $this->getJson("/api/patient/v1/orders/{$otherOrder->order_id}");
        $responseForbidden->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Forbidden');

        // 3. Check 404 response for non-existent order
        $responseNotFound = $this->getJson('/api/patient/v1/orders/ORD-NONEXISTENT');
        $responseNotFound->assertStatus(404)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Lab order not found');
    }
}
