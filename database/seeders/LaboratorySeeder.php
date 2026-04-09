<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        $categories = ['Haematology', 'Biochemistry', 'Pathology', 'Imaging'];
        foreach ($categories as $cat) {
            TestCategory::firstOrCreate(['name' => $cat]);
        }

        $labCenters = [
            [
                'name' => 'Medlab Ghana — Osu',
                'address' => '14 Cantonments Road, Osu',
                'location' => 'Osu, Accra',
                'tat' => '~4hrs',
                'certification' => 'ISO 15189',
                'services' => ['Haematology', 'Biochemistry', 'Pathology'],
                'opening_hours' => 'Mon-Sat · 07:00–18:00'
            ],
            [
                'name' => 'Trust Diagnostic Centre',
                'address' => '7 Labone Link, Labone',
                'location' => 'Labone, Accra',
                'tat' => '~4hrs',
                'certification' => 'ISO 15189',
                'services' => ['Haematology', 'Biochemistry', 'Pathology'],
                'opening_hours' => 'Mon-Sat · 07:00–18:00'
            ],
            [
                'name' => 'Nyaho Medical Centre Lab',
                'address' => 'Nyaho Clinic, Airport Residential Area',
                'location' => 'Airport Residential, Accra',
                'tat' => '~4hrs',
                'certification' => 'ISO 15189',
                'services' => ['Haematology', 'Biochemistry', 'Imaging', 'Pathology'],
                'opening_hours' => 'Mon-Sat · 07:00–18:00'
            ],
            [
                'name' => 'MDS Lancet laboratories',
                'address' => 'Labone',
                'location' => 'Labone, Accra',
                'tat' => '~4hrs',
                'certification' => 'ISO 15189',
                'services' => ['Haematology', 'Biochemistry', 'Pathology'],
                'opening_hours' => 'Mon-Sat · 07:00–18:00'
            ],
        ];

        // Seed Equipment
        $osuCenter = LabCenter::where('name', 'Medlab Ghana — Osu')->first();
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
    }
}
