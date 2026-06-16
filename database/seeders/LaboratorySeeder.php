<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\LabFacility;
use App\Models\TestCategory;
use App\Models\LabCenter;
use App\Models\LabEquipment;

class LaboratorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Lab Facilities & connected Test Categories
        $facilities = [
            [
                'name' => 'Hospital ',
                'label' => 'Hospital Refferal',
                'description' => 'Cases that require the Hospital facilities for treatments',
                'icon' => 'Hospital',
                'categories' => [
                    ['name' => 'Clinical Pathology & Laboratory Medicine', 'description' => 'Blood, urine, fluid analysis'],
                    ['name' => 'Oncology & Genetics', 'description' => 'DNA sequencing, tumor markers'],
                    ['name' => 'Toxicology & Occupational Health', 'description' => 'Drug screenings, heavy metal panels'],
                    ['name' => 'Rheumatology & Immunology', 'description' => 'Autoantibody panels, joint fluid testing'],
                ],
            ],
            [
                'name' => 'Pathology Laboratory',
                'label' => 'The Laboratories',
                'description' => 'Tests involving the collection and chemical/biological analysis of fluid or tissue samples.',
                'icon' => 'Microscope',
                'categories' => [
                    ['name' => 'Clinical Pathology & Laboratory Medicine', 'description' => 'Blood, urine, fluid analysis'],
                    ['name' => 'Oncology & Genetics', 'description' => 'DNA sequencing, tumor markers'],
                    ['name' => 'Toxicology & Occupational Health', 'description' => 'Drug screenings, heavy metal panels'],
                    ['name' => 'Rheumatology & Immunology', 'description' => 'Autoantibody panels, joint fluid testing'],
                    ['name' => 'Haematology', 'description' => 'Haematological examinations'],
                ],
            ],
            [
                'name' => 'Radiology & Imaging Center',
                'label' => 'The Imaging Suite',
                'description' => 'Tests utilizing radiation, magnets, or sound waves to view internal anatomy.',
                'icon' => 'Microscope',
                'categories' => [
                    ['name' => 'Diagnostic Imaging & Radiology', 'description' => 'MRI, CT, X-Ray, Ultrasound, Nuclear Medicine'],
                    ['name' => 'Orthopedics & Physical Medicine', 'description' => 'DEXA bone density scans'],
                ],
            ],
            [
                'name' => 'Specialized Outpatient Clinics',
                'label' => 'The Examination Room',
                'description' => 'Tests performed directly by a specialist doctor using dedicated diagnostic equipment in a clinical office.',
                'icon' => 'Radiation',
                'categories' => [
                    ['name' => 'Cardiology', 'description' => 'Echocardiograms, Stress tests'],
                    ['name' => 'Neurology', 'description' => 'EEGs, EMGs'],
                    ['name' => 'Gastroenterology & Pulmonology', 'description' => 'Endoscopy, Colonoscopy, Spirometry'],
                    ['name' => 'Ophthalmology', 'description' => 'Vision scans, OCT eye imaging'],
                    ['name' => 'Audiology', 'description' => 'Hearing tests, Tympanometry'],
                    ['name' => 'Obstetrics & Gynecology (OB/GYN)', 'description' => 'Fetal monitoring, amniocentesis'],
                    ['name' => 'Dermatology', 'description' => 'Skin biopsies, dermoscopy'],
                    ['name' => 'Urology & Nephrology', 'description' => 'Urodynamic testing'],
                ],
            ],
            [
                'name' => 'Dedicated Treatment & Assessment Units',
                'label' => 'The Specialized Unit',
                'description' => 'Tests requiring overnight stays or extensive behavioral and physical observation.',
                'icon' => 'Accessibility',
                'categories' => [
                    ['name' => 'Sleep & Allergy Medicine', 'description' => 'Overnight polysomnography sleep studies'],
                    ['name' => 'Psychiatry & Behavioral Health', 'description' => 'Neuropsychological and cognitive evaluations'],
                ],
            ],
        ];

        foreach ($facilities as $facData) {
            $categoriesData = $facData['categories'];
            unset($facData['categories']);
            
            $facility = LabFacility::firstOrCreate(['name' => $facData['name']], $facData);
            
            foreach ($categoriesData as $catData) {
                TestCategory::firstOrCreate(
                    [
                        'name' => $catData['name'],
                        'lab_facility_id' => $facility->id
                    ],
                    [
                        'description' => $catData['description']
                    ]
                );
            }
        }

        // 2. Seed Lab Centers (using the exact dummy names/details)
        $labCenters = [
            [
                'name' => 'Medlab Ghana – Osu',
                'address' => '14 Cantonments Road, Osu',
                'location' => 'Osu, Accra',
                'tat' => 'TAT ~4hrs',
                'certification' => 'ISO 15189',
                'services' => ['Haematology', 'Biochemistry', 'Pathology'],
                'opening_hours' => 'Mon–Sat · 07:00–18:00'
            ],
            [
                'name' => 'Trust Diagnostic Centre',
                'address' => '7 Labone Link, Labone',
                'location' => 'Labone, Accra',
                'tat' => 'TAT ~4hrs',
                'certification' => 'ISO 15189',
                'services' => ['Haematology', 'Biochemistry', 'Pathology'],
                'opening_hours' => 'Mon–Sat · 07:00–18:00'
            ],
        ];

        foreach ($labCenters as $center) {
            LabCenter::firstOrCreate(['name' => $center['name']], $center);
        }

        // 3. Seed Equipment
        $osuCenter = LabCenter::where('name', 'like', 'Medlab Ghana%')->first();
        if ($osuCenter) {
            $equipment = [
                [
                    'name' => 'Sysmex XN-1000',
                    'serial_number' => 'ANA-001',
                    'type' => 'Haematology Analyzer',
                    'lab_center_id' => $osuCenter->id,
                    'manufacturer' => 'Sysmex',
                ],
                [
                    'name' => 'Siemens DRIVE',
                    'serial_number' => 'SN-2024-MRI-001',
                    'type' => 'MRI Scanner',
                    'lab_center_id' => $osuCenter->id,
                    'manufacturer' => 'Siemens',
                ],
                [
                    'name' => 'Philips Digital',
                    'serial_number' => 'SN-2024-MRI-002',
                    'type' => 'X-Ray Unit',
                    'lab_center_id' => $osuCenter->id,
                    'manufacturer' => 'Philips',
                ]
            ];

            foreach ($equipment as $item) {
                LabEquipment::firstOrCreate(['serial_number' => $item['serial_number']], $item);
            }
        }

        // 4. Seed Patients
        $patients = [
            [
                'full_name' => 'Amara Asante',
                'email' => 'amara@example.com',
                'phone' => '2025129485483',
                'password' => bcrypt('password'),
                'category' => 'patient',
                'gender' => 'Female',
                'dob' => '1992-03-23',
                'city' => 'Accra',
                'country' => 'Ghana',
                'approval_status' => 'approved',
                'status' => 'active',
            ],
            [
                'full_name' => 'Jane Gideon',
                'email' => 'jane@example.com',
                'phone' => '12343',
                'password' => bcrypt('password'),
                'category' => 'patient',
                'gender' => 'Female',
                'dob' => '1995-08-12',
                'city' => 'Accra',
                'country' => 'Ghana',
                'approval_status' => 'approved',
                'status' => 'active',
            ],
            [
                'full_name' => 'kahby james',
                'email' => 'kahby@example.com',
                'phone' => '234509',
                'password' => bcrypt('password'),
                'category' => 'patient',
                'gender' => 'Male',
                'dob' => '1988-11-05',
                'city' => 'Osu',
                'country' => 'Ghana',
                'approval_status' => 'approved',
                'status' => 'active',
            ],
            [
                'full_name' => 'Cole Gideon',
                'email' => 'cole@example.com',
                'phone' => '098534',
                'password' => bcrypt('password'),
                'category' => 'patient',
                'gender' => 'Male',
                'dob' => '2000-01-20',
                'city' => 'Labone',
                'country' => 'Ghana',
                'approval_status' => 'approved',
                'status' => 'active',
            ],
        ];

        foreach ($patients as $patData) {
            \App\Models\User::firstOrCreate(['email' => $patData['email']], $patData);
        }

        // 5. Seed Lab Orders & Results
        $amara = \App\Models\User::where('email', 'amara@example.com')->first();
        $jane = \App\Models\User::where('email', 'jane@example.com')->first();
        $kahby = \App\Models\User::where('email', 'kahby@example.com')->first();
        $cole = \App\Models\User::where('email', 'cole@example.com')->first();

        $pathologyLab = LabFacility::where('name', 'Pathology Laboratory')->first();
        $imagingCenter = LabFacility::where('name', 'Radiology & Imaging Center')->first();

        $haematologyCat = TestCategory::where('name', 'Haematology')->first();
        $pathologyCat = TestCategory::where('name', 'Clinical Pathology & Laboratory Medicine')->first();
        $imagingCat = TestCategory::where('name', 'Diagnostic Imaging & Radiology')->first();

        $medlabOsu = LabCenter::where('name', 'like', 'Medlab Ghana%')->first();

        // Amara Asante Completed order
        if ($amara && $haematologyCat && $medlabOsu && $pathologyLab) {
            $order = \App\Models\LabOrder::firstOrCreate(
                ['order_id' => 'ORD-2025-04881'],
                [
                    'patient_id' => $amara->id,
                    'lab_facility_id' => $pathologyLab->id,
                    'test_category_id' => $haematologyCat->id,
                    'specific_test_name' => 'Full Blood Count',
                    'priority' => 'Urgent',
                    'lab_center_id' => $medlabOsu->id,
                    'provisional_diagnosis' => 'Suspected iron-deficiency anaemia',
                    'clinical_notes' => 'Patient reports fatigue and exertional dyspnoea for 3 weeks. Pallor noted on examination. Dietary history suggests low iron intake.',
                    'status' => 'Completed',
                    'created_at' => '2025-03-20 09:12:00',
                ]
            );

            $result = \App\Models\LabResult::firstOrCreate(
                ['lab_order_id' => $order->id],
                [
                    'signed_off_by_name' => 'Dr. K. Boateng',
                    'signed_off_by_title' => 'Pathologist',
                    'signed_off_by_gmc' => 'GMC 9912441',
                    'date_completed' => '2025-03-23 09:55:00',
                    'doctor_comment' => '',
                    'overall_flag' => 'Critical',
                    'status' => 'Completed',
                ]
            );

            $sysmex = LabEquipment::where('serial_number', 'ANA-001')->first();
            if ($sysmex) {
                $result->equipment()->sync([$sysmex->id]);
            }

            $params = [
                ['parameter_name' => 'WBC', 'result_value' => '6.8', 'unit' => 'x10^9/L', 'reference_range' => '4.0–11.0 x10^9/L', 'flag' => 'Normal'],
                ['parameter_name' => 'Haemoglobin (Hb)', 'result_value' => '5.2', 'unit' => 'g/L', 'reference_range' => '120–160 g/L', 'flag' => 'Critical'],
                ['parameter_name' => 'Platelets', 'result_value' => '198', 'unit' => 'x10^9/L', 'reference_range' => '150–400 x10^9/L', 'flag' => 'Normal'],
                ['parameter_name' => 'Rubella Test', 'result_value' => '64', 'unit' => '/L', 'reference_range' => '80–120/L', 'flag' => 'Abnormal'],
                ['parameter_name' => 'Tau Protein', 'result_value' => '19', 'unit' => 'pg', 'reference_range' => '27-33 pg', 'flag' => 'Abnormal'],
                ['parameter_name' => 'Haptoglobin', 'result_value' => '14.2', 'unit' => '%', 'reference_range' => '11.5–14.5%', 'flag' => 'Normal'],
            ];

            foreach ($params as $p) {
                $result->parameters()->firstOrCreate(['parameter_name' => $p['parameter_name']], $p);
            }
        }

        // Jane Gideon Requested order
        if ($jane && $haematologyCat && $medlabOsu && $pathologyLab) {
            \App\Models\LabOrder::firstOrCreate(
                ['order_id' => 'ORD-2026-03180'],
                [
                    'patient_id' => $jane->id,
                    'lab_facility_id' => $pathologyLab->id,
                    'test_category_id' => $haematologyCat->id,
                    'specific_test_name' => 'Coagulation Panel',
                    'priority' => 'Urgent',
                    'lab_center_id' => $medlabOsu->id,
                    'provisional_diagnosis' => 'Routine monitoring',
                    'clinical_notes' => 'Checking clotting factors.',
                    'status' => 'Requested',
                    'created_at' => '2026-03-18 07:45:00',
                ]
            );
        }

        // kahby james Completed order
        if ($kahby && $pathologyCat && $medlabOsu && $pathologyLab) {
            $order = \App\Models\LabOrder::firstOrCreate(
                ['order_id' => 'ORD-2026-03181'],
                [
                    'patient_id' => $kahby->id,
                    'lab_facility_id' => $pathologyLab->id,
                    'test_category_id' => $pathologyCat->id,
                    'specific_test_name' => 'Tissue Biopsy',
                    'priority' => 'Emergency',
                    'lab_center_id' => $medlabOsu->id,
                    'provisional_diagnosis' => 'Dermatological assessment',
                    'clinical_notes' => 'Biopsy of skin lesion.',
                    'status' => 'Completed',
                    'created_at' => '2026-03-18 07:45:00',
                ]
            );

            $result = \App\Models\LabResult::firstOrCreate(
                ['lab_order_id' => $order->id],
                [
                    'signed_off_by_name' => 'Dr. K. Boateng',
                    'signed_off_by_title' => 'Pathologist',
                    'signed_off_by_gmc' => 'GMC 9912441',
                    'date_completed' => '2026-03-20 07:45:00',
                    'doctor_comment' => 'Tissues appear benign.',
                    'overall_flag' => 'Normal',
                    'status' => 'Completed',
                ]
            );

            $result->parameters()->firstOrCreate(
                ['parameter_name' => 'Tissue Cell Count'],
                ['parameter_name' => 'Tissue Cell Count', 'result_value' => '1.2', 'unit' => 'M/mL', 'reference_range' => '1.0–2.0 M/mL', 'flag' => 'Normal']
            );
        }

        // Cole Gideon Processing order
        if ($cole && $imagingCat && $medlabOsu && $imagingCenter) {
            \App\Models\LabOrder::firstOrCreate(
                ['order_id' => 'ORD-2026-03182'],
                [
                    'patient_id' => $cole->id,
                    'lab_facility_id' => $imagingCenter->id,
                    'test_category_id' => $imagingCat->id,
                    'specific_test_name' => 'Chest X-Ray',
                    'priority' => 'Routine',
                    'lab_center_id' => $medlabOsu->id,
                    'provisional_diagnosis' => 'Persistent cough',
                    'clinical_notes' => 'Excluding chest infection.',
                    'status' => 'Processing',
                    'created_at' => '2026-03-18 07:45:00',
                ]
            );
        }
    }
}
