<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use App\Models\Province;
use App\Models\Flight;
use App\Models\RejectionReason;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'phone'=>'+1234567890',
            'age'   => '30',
            'role' => 'admin',
        ]);

        // Create some countries
        $countries = [
            ['name' => 'Saudi Arabia', 'code' => 'SAU'],
            ['name' => 'United Arab Emirates', 'code' => 'ARE'],
            ['name' => 'Egypt', 'code' => 'EGY'],
            ['name' => 'Jordan', 'code' => 'JOR']
        ];

        foreach ($countries as $country) {
            Country::create($country);
        }

        // Create some provinces
        $provinces = [
            ['name' => 'Makkah', 'code' => 'MKH', 'country_id' => 1],
            ['name' => 'Madinah', 'code' => 'MED', 'country_id' => 1],
            ['name' => 'Dubai', 'code' => 'DXB', 'country_id' => 2],
            ['name' => 'Abu Dhabi', 'code' => 'AUH', 'country_id' => 2],
            ['name' => 'Cairo', 'code' => 'CAI', 'country_id' => 3],
            ['name' => 'Alexandria', 'code' => 'ALY', 'country_id' => 3],
            ['name' => 'Amman', 'code' => 'AMM', 'country_id' => 4],
        ];

        foreach ($provinces as $province) {
            Province::create($province);
        }

        // Create some flights
        $flights = [
            [
                'flight_number' => 'SA101',
                'airline' => 'Saudi Airlines',
                'departure_city' => 'Cairo',
                'arrival_city' => 'Jeddah',
                'departure_time' => '2024-01-01 10:00:00',
                'arrival_time' => '2024-01-01 12:00:00',
                'price' => 500.00,
                'available_seats' => 100
            ],
            [
                'flight_number' => 'EK202',
                'airline' => 'Emirates',
                'departure_city' => 'Dubai',
                'arrival_city' => 'Amman',
                'departure_time' => '2024-01-02 14:00:00',
                'arrival_time' => '2024-01-02 16:00:00',
                'price' => 450.00,
                'available_seats' => 150
            ]
        ];

        foreach ($flights as $flight) {
            Flight::create($flight);
        }

        // Create rejection reasons
        $reasons = [
            ['reason' => 'Incomplete documentation'],
            ['reason' => 'Invalid passport'],
            ['reason' => 'Missing visa requirements'],
            ['reason' => 'Payment issues']
        ];

        foreach ($reasons as $reason) {
            RejectionReason::create($reason);
        }
    }
}
