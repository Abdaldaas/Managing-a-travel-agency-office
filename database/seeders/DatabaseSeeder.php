<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Country;
use App\Models\Province;
use App\Models\Flight;
use App\Models\Airport;
use App\Models\TaxiDriver;
use App\Models\RejectionReason;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\PassportSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Run the passport seeder
        $this->call(PassportSeeder::class);

        // Create admin user
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'phone'    => '+1234567890',
            'age'      => 30,
            'role'     => 'admin',
            'fcm_token'=> 'fc25DZcAvmbUmpQhNnffeW:APA91bFqmhGuksNlI75zHzdLej-5MiaRMioOKW72QtktbcRR1Vxvix2lDUPGxKLjZ93vq28AUAE20e-Ju-ScK6dq3M6SYjhyfLD_iyOM6O0imr45dXdvEV4',
        ]);

        // Create a few regular users with fake fcm_tokens
        User::factory()->count(5)->create()->each(function ($user) {
            $user->update([
                'fcm_token' => 'test_user_token_'.uniqid(),
            ]);
        });

        // Create countries if they don't exist
        if (Country::count() === 0) {
            $countries = [
                ['name' => 'Saudi Arabia', 'code' => 'SAU'],
                ['name' => 'United Arab Emirates', 'code' => 'ARE'],
                ['name' => 'Egypt', 'code' => 'EGY'],
                ['name' => 'Jordan', 'code' => 'JOR']
            ];

            foreach ($countries as $country) {
                Country::create($country);
            }
        }

        // Create provinces if they don't exist
        if (Province::count() === 0) {
            $provinces = [
                ['name' => 'Riyadh', 'code' => 'RUH', 'country_id' => 1],
                ['name' => 'Makkah', 'code' => 'MKH', 'country_id' => 1],
                ['name' => 'Madinah', 'code' => 'MED', 'country_id' => 1],
                ['name' => 'Dubai', 'code' => 'DXB', 'country_id' => 2],
                ['name' => 'Abu Dhabi', 'code' => 'AUH', 'country_id' => 2]
            ];

            foreach ($provinces as $province) {
                Province::create($province);
            }
        }

        // Create airports
        $airports = [
            [
                'name' => 'King Abdulaziz International Airport',
                'iata_code' => 'JED',
                'icao_code' => 'OEJN',
                'city' => 'Jeddah',
                'country' => 'Saudi Arabia',
                'latitude' => 21.6805,
                'longitude' => 39.1722,
                'timezone' => 'Asia/Riyadh'
            ],
            [
                'name' => 'Cairo International Airport',
                'iata_code' => 'CAI',
                'icao_code' => 'HECA',
                'city' => 'Cairo',
                'country' => 'Egypt',
                'latitude' => 30.1219,
                'longitude' => 31.4056,
                'timezone' => 'Africa/Cairo'
            ],
            [
                'name' => 'Dubai International Airport',
                'iata_code' => 'DXB',
                'icao_code' => 'OMDB',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'latitude' => 25.2532,
                'longitude' => 55.3657,
                'timezone' => 'Asia/Dubai'
            ],
            [
                'name' => 'Queen Alia International Airport',
                'iata_code' => 'AMM',
                'icao_code' => 'OJAI',
                'city' => 'Amman',
                'country' => 'Jordan',
                'latitude' => 31.7226,
                'longitude' => 35.9932,
                'timezone' => 'Asia/Amman'
            ]
        ];

        foreach ($airports as $airport) {
            Airport::create($airport);
        }

        // Create some flights
        $flights = [
            [
                'flight_number' => 'SA101',
                'departure_airport_id' => 1,
                'arrival_airport_id' => 2,
                'departure_time' => '2024-01-01 10:00:00',
                'arrival_time' => '2024-01-01 12:00:00',
                'price' => 500.00,
                'available_seats' => 100,
                'status' => 'scheduled'
            ],
            [
                'flight_number' => 'EK202',
                'departure_airport_id' => 3,
                'arrival_airport_id' => 4,
                'departure_time' => '2024-01-02 14:00:00',
                'arrival_time' => '2024-01-02 16:00:00',
                'price' => 450.00,
                'available_seats' => 150,
                'status' => 'scheduled'
            ]
        ];

        foreach ($flights as $flight) {
            Flight::create($flight);
        }

        // Create taxi drivers if they don't exist
        
            $drivers = [
                [
                    'user_id' => User::create([
                        'name' => 'سائق محمد',
                        'email' => 'driver1@example.com',
                        'password' => Hash::make('12345678'),
                        'phone' => '+966501234567',
                        'role' => 'driver'
                    ])->id,
                    'national_id' => '1234567890',
                    'car_model' => 'Toyota Camry',
                    'car_plate_number' => 'ABC 123',
                    'license_number' => 'DL12345678',
                    'address' => 'حي النزهة، الرياض',
                    'birth_date' => '1990-01-01',
                    'status' => 'available',
                    'current_latitude' => 24.774265,
                    'current_longitude' => 46.738586,
                    'rating' => 4.5,
                    'total_trips' => 150
                ],
                [
                    'user_id' => User::create([
                        'name' => 'سائق أحمد',
                        'email' => 'driver2@example.com',
                        'password' => Hash::make('12345678'),
                        'phone' => '+966501234568',
                        'role' => 'driver'
                    ])->id,
                    'national_id' => '1234567891',
                    'car_model' => 'Honda Accord',
                    'car_plate_number' => 'XYZ 789',
                    'license_number' => 'DL87654321',
                    'address' => 'حي العليا، الرياض',
                    'birth_date' => '1988-05-15',
                    'status' => 'available',
                    'current_latitude' => 24.774265,
                    'current_longitude' => 46.738586,
                    'rating' => 4.8,
                    'total_trips' => 200
                ],
                [
                    'user_id' => User::create([
                        'name' => 'سائق خالد',
                        'email' => 'driver3@example.com',
                        'password' => Hash::make('12345678'),
                        'phone' => '+966501234569',
                        'role' => 'driver'
                    ])->id,
                    'national_id' => '1234567892',
                    'car_model' => 'Hyundai Sonata',
                    'car_plate_number' => 'DEF 456',
                    'license_number' => 'DL98765432',
                    'address' => 'حي الملز، الرياض',
                    'birth_date' => '1992-08-20',
                    'status' => 'available',
                    'current_latitude' => 24.774265,
                    'current_longitude' => 46.738586,
                    'rating' => 4.7,
                    'total_trips' => 175
                ]
            ];

            foreach ($drivers as $driver) {
                TaxiDriver::create($driver);
            }
        

        // Create rejection reasons if they don't exist
        if (RejectionReason::count() === 0) {
            $reasons = [
                ['reason' => 'Invalid documents'],
                ['reason' => 'Incomplete information'],
                ['reason' => 'Technical issues'],
                ['reason' => 'Service unavailable'],
                ['reason' => 'Other']
            ];

            foreach ($reasons as $reason) {
                RejectionReason::create($reason);
            }
        }
    }
}
