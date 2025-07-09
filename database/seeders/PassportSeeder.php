co<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Passport;
use App\Models\User;
use App\Models\PassportRequest;
use App\Models\Booking;
use Carbon\Carbon;

class PassportSeeder extends Seeder
{
    public function run()
    {
        // Create three example users first
        $user1 = User::create([
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+9661234567',
            'age' => 35,
            'role' => 'user',
        ]);

        $user2 = User::create([
            'name' => 'Sara Mohammed',
            'email' => 'sara@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+9661234568',
            'age' => 16,
            'role' => 'user',
        ]);

        $user3 = User::create([
            'name' => 'Khalid Omar',
            'email' => 'khalid@example.com',
            'password' => bcrypt('password123'),
            'phone' => '+9661234569',
            'age' => 25,
            'role' => 'user',
        ]);

        // Example 1: User with old passport
        $passport1 = Passport::create([
            'user_id' => $user1->id,
            'passport_number' => 'P' . mt_rand(10000000, 99999999),
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'father_name' => 'Mohammed',
            'mother_name' => 'Fatima',
            'date_of_birth' => '1988-05-15',
            'place_of_birth' => 'Riyadh',
            'nationality' => 'Saudi',
            'gender' => 'male',
            'national_number' => '1234567890',
            'passport_type' => 'regular',
            'num_dependents' => 0,
            'dependent_details' => json_encode([]),
            'identity_front' => 'identity_front/example1.jpg',
            'identity_back' => 'identity_back/example1.jpg',
            'personal_photo' => 'personal_photos/example1.jpg',
            'old_passport_page1' => 'old_passports/example1_page1.jpg',
            'old_passport_page2' => 'old_passports/example1_page2.jpg',
            'has_old_passport' => true
        ]);

        // Example 2: User under 18
        $passport2 = Passport::create([
            'user_id' => $user2->id,
            'passport_number' => 'P' . mt_rand(10000000, 99999999),
            'first_name' => 'Sara',
            'last_name' => 'Mohammed',
            'father_name' => 'Mohammed',
            'mother_name' => 'Aisha',
            'date_of_birth' => '2008-08-20',
            'place_of_birth' => 'Jeddah',
            'nationality' => 'Saudi',
            'gender' => 'female',
            'national_number' => '1234567891',
            'passport_type' => 'regular',
            'num_dependents' => 2,
            'dependent_details' => json_encode([
                ['name' => 'Mohammed Abdullah', 'relation' => 'Father'],
                ['name' => 'Aisha Omar', 'relation' => 'Mother']
            ]),
            'identity_front' => 'identity_front/example2.jpg',
            'identity_back' => 'identity_back/example2.jpg',
            'personal_photo' => 'personal_photos/example2.jpg',
            'old_passport_page1' => null,
            'old_passport_page2' => null,
            'has_old_passport' => false
        ]);

        // Example 3: User without old passport
        $passport3 = Passport::create([
            'user_id' => $user3->id,
            'passport_number' => 'P' . mt_rand(10000000, 99999999),
            'first_name' => 'Khalid',
            'last_name' => 'Omar',
            'father_name' => 'Omar',
            'mother_name' => 'Nora',
            'date_of_birth' => '1998-03-10',
            'place_of_birth' => 'Dammam',
            'nationality' => 'Saudi',
            'gender' => 'male',
            'national_number' => '1234567892',
            'passport_type' => 'express',
            'num_dependents' => 0,
            'dependent_details' => json_encode([]),
            'identity_front' => 'identity_front/example3.jpg',
            'identity_back' => 'identity_back/example3.jpg',
            'personal_photo' => 'personal_photos/example3.jpg',
            'old_passport_page1' => null,
            'old_passport_page2' => null,
            'has_old_passport' => false
        ]);

        // Create passport requests for each passport
        $passports = [$passport1, $passport2, $passport3];
        foreach ($passports as $passport) {
            $passportRequest = PassportRequest::create([
                'user_id' => $passport->user_id,
                'passport_id' => $passport->id,
                'passport_type' => $passport->passport_type,
                'status' => 'processing',
                'price' => $passport->passport_type === 'express' ? 800 : 500
            ]);

            Booking::create([
                'user_id' => $passport->user_id,
                'user_name' => User::find($passport->user_id)->name,
                'type' => 'passport',
                'status' => 'processing',
                'price' => $passportRequest->price
            ]);
        }
    }
}