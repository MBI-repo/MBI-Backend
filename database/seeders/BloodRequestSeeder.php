<?php

namespace Database\Seeders;

use App\Models\BloodRequest;
use App\Models\BloodBank;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Models\DoctorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BloodRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bloodBanks = BloodBank::pluck('uuid')->toArray();
        $users = User::pluck('uuid')->toArray();
        $facilities = Facility::pluck('uuid')->toArray();
        //$patients = Patient::pluck('uuid')->toArray();
        $doctors = DoctorProfile::pluck('uuid')->toArray();

        // Make sure required relationships exist
        if (empty($users)) {
            $this->command->warn(
                'No users found. Blood request seeder skipped.'
            );

            return;
        }

        $requests = [
            [
                'request_type' => 'patient',
                'priority' => 'routine',
                'component_type' => 'Whole Blood',
                'unit_needed' => '2 units',
                'department' => 'Emergency',
                'note' => 'Blood required for patient undergoing treatment.',
                'blood_group' => 'O+',
                'clinical_reason' => 'Patient requires blood transfusion due to significant blood loss.',
                'status' => 'pending',
            ],

            [
                'request_type' => 'emergency',
                'priority' => 'emergency',
                'component_type' => 'Packed Red Blood Cells',
                'unit_needed' => '4 units',
                'department' => 'Emergency',
                'note' => 'Urgent blood required for emergency patient.',
                'blood_group' => 'O-',
                'clinical_reason' => 'Severe hemorrhage following emergency surgery.',
                'status' => 'approved',
            ],

            [
                'request_type' => 'elective',
                'priority' => 'normal',
                'component_type' => 'Packed Red Blood Cells',
                'unit_needed' => '2 units',
                'department' => 'Surgery',
                'note' => 'Blood reserved for scheduled surgical procedure.',
                'blood_group' => 'A+',
                'clinical_reason' => 'Blood required for elective surgical procedure.',
                'status' => 'pending',
            ],

            [
                'request_type' => 'stock_replenishment',
                'priority' => 'low',
                'component_type' => 'Fresh Frozen Plasma',
                'unit_needed' => '10 units',
                'department' => 'Blood Bank',
                'note' => 'Replenishment of blood component stock.',
                'blood_group' => 'AB+',
                'clinical_reason' => 'Blood component stock replenishment.',
                'status' => 'approved',
            ],

            [
                'request_type' => 'patient',
                'priority' => 'urgent',
                'component_type' => 'Platelets',
                'unit_needed' => '6 units',
                'department' => 'Haematology',
                'note' => 'Patient has critically low platelet count.',
                'blood_group' => 'B+',
                'clinical_reason' => 'Severe thrombocytopenia requiring platelet transfusion.',
                'status' => 'approved',
            ],

            [
                'request_type' => 'patient',
                'priority' => 'normal',
                'component_type' => 'Packed Red Blood Cells',
                'unit_needed' => '1 unit',
                'department' => 'Medical Ward',
                'note' => 'Blood requested for anaemic patient.',
                'blood_group' => 'A-',
                'clinical_reason' => 'Severe anaemia requiring transfusion.',
                'status' => 'rejected',
            ],

            [
                'request_type' => 'emergency',
                'priority' => 'emergency',
                'component_type' => 'Whole Blood',
                'unit_needed' => '5 units',
                'department' => 'Trauma',
                'note' => 'Emergency trauma case requiring immediate transfusion.',
                'blood_group' => 'O+',
                'clinical_reason' => 'Massive blood loss following road traffic accident.',
                'status' => 'pending',
            ],

            [
                'request_type' => 'elective',
                'priority' => 'urgent',
                'component_type' => 'Packed Red Blood Cells',
                'unit_needed' => '3 units',
                'department' => 'Obstetrics',
                'note' => 'Blood required for high-risk obstetric procedure.',
                'blood_group' => 'B+',
                'clinical_reason' => 'High risk of blood loss during scheduled procedure.',
                'status' => 'approved',
            ],
        ];

        foreach ($requests as $index => $request) {

            $requestDate = Carbon::today()->subDays($index);

            $status = $request['status'];

            BloodRequest::create([
                'uuid' => (string) Str::uuid(),

                'request_number' => 'BR-' .
                    $requestDate->format('Ymd') .
                    '-' .
                    str_pad($index + 1, 4, '0', STR_PAD_LEFT),

                'blood_bank_uuid' => !empty($bloodBanks)
                    ? $bloodBanks[array_rand($bloodBanks)]
                    : null,

                'requester_uuid' => $users[array_rand($users)],

                'facility_uuid' => !empty($facilities)
                    ? $facilities[array_rand($facilities)]
                    : null,

                'patient_uuid' => !empty($patients)
                    ? $patients[array_rand($patients)]
                    : null,

                'doctor_uuid' => !empty($doctors)
                    ? $doctors[array_rand($doctors)]
                    : null,

                'request_type' => $request['request_type'],

                'priority' => $request['priority'],

                'component_type' => $request['component_type'],

                'unit_needed' => $request['unit_needed'],

                'department' => $request['department'],

                'note' => $request['note'],

                'request_date' => $requestDate->toDateString(),

                'request_time' => now()->subHours($index)->format('H:i:s'),

                'blood_group' => $request['blood_group'],

                'clinical_reason' => $request['clinical_reason'],

                'required_at' => $requestDate
                    ->copy()
                    ->addDays(1)
                    ->setTime(10, 0, 0),

                'status' => $status,

                'rejection_reason' => $status === 'rejected'
                    ? 'Requested blood component is currently unavailable.'
                    : null,

                'created_at' => $requestDate,

                'updated_at' => $requestDate,
            ]);
        }

        $this->command->info(
            count($requests) . ' blood requests created successfully.'
        );
    }
}