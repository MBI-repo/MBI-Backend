<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{

    public function run(): void

    {
        Role::create([
            'uuid' => Str::uuid(),
            'name' => 'Super Admin',
            'slug' => 'super_admin',
        ]);

        Role::create([
            'uuid' => Str::uuid(),
            'name' => 'Blood Bank',
            'slug' => 'blood_bank',
        ]);

        Role::create([
            'uuid' => Str::uuid(),
            'name' => 'Doctor',
            'slug' => 'doctor',
        ]);

        Role::create([
            'uuid' => Str::uuid(),
            'name' => 'Hospital/Clinic',
            'slug' => 'hospital',
        ]);

        Role::create([
            'uuid' => Str::uuid(),
            'name' => 'Clinic',
            'slug' => 'clinic',
        ]);

    }
    
}