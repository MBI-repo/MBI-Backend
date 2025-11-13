<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // create 50 random users
        User::factory()->count(50)->create();

        // create a predictable test user for login
        User::factory()->create([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '08000000000',
            'password' => Hash::make('secret123'), // remember this password
            'category' => 'Doctor',
            'specialisation' => 'General Practice',
            'institution' => 'Test Hospital',
            'license_number' => 'TEST-00001',
            'approval_status' => 'approved',
            'status' => 'active',
        ]);
    }
}
