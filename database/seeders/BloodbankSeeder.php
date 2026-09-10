<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Models\BloodBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BloodBankSeeder extends Seeder
{

    public function run(): void
    {
        $user = User::create([
            'uuid' => Str::uuid(),
            'full_name' => 'MBI Blood Bank',
            'role_id' => 2,
            'email' => 'bloodbank@example.com',
            'password' => Hash::make('Password123'),
            'phone' => '08000000000',
            'category' => 'Blood Bank',
            'specialisation' => 'Blood Bank',
            'institution' => 'MBI Blood Bank',
            'license_number' => 'MBI-00001',
            'approval_status' => 'approved',
            'status' => 'active',
            'registration_status' => 'completed',
        ]);

        BloodBank::create([
            'uuid' => Str::uuid(),
            'user_uuid' => $user->uuid,
            'name' => 'MBI Blood Bank',
            'status' => 'active',
        ]);


    }

}