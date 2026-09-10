<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        $patients = [
            [
                'uuid' => (string) Str::uuid(),
                'patient_number' => 'PAT-000001',
                'full_name' => 'John Chinedu Okafor',
                'date_of_birth' => '1989-05-14',
                'gender' => 'male',
                'blood_group' => 'A+',
                'genotype' => 'AA',
                'contact' => '08031111111',
                'email' => 'john.okafor@example.com',
                'address' => '12 Independence Avenue',
                'diagnosis' => 'Severe anemia',
                'allergies' => 'None',
                'medical_notes' => 'Requires regular blood monitoring.',
                'status' => 'active',
            ],

            [
                'uuid' => (string) Str::uuid(),
                'patient_number' => 'PAT-000002',
                'full_name' => 'Mary Ada Eze',
                'date_of_birth' => '1995-11-22',
                'gender' => 'female',
                'blood_group' => 'O+',
                'genotype' => 'AS',
                'contact' => '08032222222',
                'email' => 'mary.eze@example.com',
                'address' => '45 Okigwe Street',
                'diagnosis' => 'Postpartum hemorrhage',
                'allergies' => 'Penicillin',
                'medical_notes' => 'Monitor hemoglobin level closely.',
                'status' => 'active',
            ],

            [
                'uuid' => (string) Str::uuid(),
                'patient_number' => 'PAT-000003',
                'full_name' => 'David Williams',
                'date_of_birth' => '1978-02-10',
                'gender' => 'male',
                'blood_group' => 'B+',
                'genotype' => 'AA',
                'contact' => '08033333333',
                'email' => 'david.williams@example.com',
                'address' => '8 Mission Road',
                'diagnosis' => 'Surgical blood loss',
                'allergies' => 'None',
                'medical_notes' => null,
                'status' => 'active',
            ],

            [
                'uuid' => (string) Str::uuid(),
                'patient_number' => 'PAT-000004',
                'full_name' => 'Grace Adebayo',
                'date_of_birth' => '2001-08-30',
                'gender' => 'female',
                'blood_group' => 'AB+',
                'genotype' => 'AA',
                'contact' => '08034444444',
                'email' => 'grace.adebayo@example.com',
                'address' => '21 Stadium Road',
                'diagnosis' => 'Thrombocytopenia',
                'allergies' => 'None',
                'medical_notes' => 'Requires platelet support when clinically indicated.',
                'status' => 'active',
            ],

            [
                'uuid' => (string) Str::uuid(),
                'patient_number' => 'PAT-000005',
                'full_name' => 'Peter Ibrahim',
                'date_of_birth' => '1967-03-18',
                'gender' => 'male',
                'blood_group' => 'O-',
                'genotype' => 'AA',
                'contact' => '08035555555',
                'email' => 'peter.ibrahim@example.com',
                'address' => '6 Hospital Road',
                'diagnosis' => 'Chronic blood loss',
                'allergies' => 'Sulfa drugs',
                'medical_notes' => 'History of repeated transfusions.',
                'status' => 'active',
            ],
        ];

        foreach ($patients as $patient) {
            Patient::create($patient);
        }
    }
}