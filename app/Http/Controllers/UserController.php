<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Passport;
use App\Models\VisaBooking;
use App\Models\Visa;
use App\Models\Booking;
use App\Models\TicketRequest;
use App\Models\Flight;
use App\Models\Haj;
use App\Models\HajBooking;

use App\Models\PassportRequest;
use App\Models\User;

class UserController extends Controller {
    public function requestPassport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passport_type' => 'required|in:regular,urgent',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'father_name' => 'required|string',
            'mother_name' => 'required|string',
            'place_of_birth' => 'required|string',
            'national_number' => 'required|string',
            'gender' => 'required|in:male,female',
            'identity_front' => 'required|file',
            'identity_back' => 'required|file',
            'num_dependents'=>'required|integer|max:2',
            'has_old_passport' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $identity_front = $request->file('identity_proof')->store('identity_front');
        $identity_back =$request->file('identity_back')->store('identity_back');
        $passport = Passport::create([
            'user_id' => auth()->id(),
            'passport_type' => $request->passport_type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'date_of_birth' => $request->date_of_birth,
            'place_of_birth' => $request->place_of_birth,
            'national_number' => $request->national_number,
            'gender' => $request->gender,
            'identity_front' => $identity_front,
            'identity_back'=> $identity_back,
            'has_old_passport' => $request->has_old_passport
        ]);

        $passportRequest = new PassportRequest();
        $passportRequest->user_id = auth()->id();
        $passportRequest->passport_id = $passport->id;
        $passportRequest->passport_type = $request->passport_type;
        $passportRequest->status = 'processing';
        $passportRequest->calculatePrice();
        $passportRequest->save();

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'type' => 'passport',
            'status' => 'processing',
            'price' => $passportRequest->price
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Passport request submitted successfully',
            'passport' => $passport,
            'passport_request' => $passportRequest,
            'booking' => $booking
        ], 201);
    }
    public function registeruser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone' => 'required|string|min:10',
            'age' => 'required|integer|min:18',
        ]);
        if($validator->fails())
        {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }
        $user_check = User::where("email", "=", $request->email)->first();
        if(isset($user_check->id))
        {
            return response()->json([
                'status' => false,
                'message'=> 'This email is already registered'
            ], 400);        }
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->age=$request->age;
        $user->phone=$request->phone;
        $user->role = "user";
        $user->save(); 
        $token = $user->createToken("auth_token")->plainTextToken;
   
        return response()->json(['user' => $user, 'message' => 'User registered successfully','token' => $token], 200);    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,'message' => 'Invalid data', 'errors' => $validator->errors()], 400);
            }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => false,'message' => 'Email not registered'], 404);
            }
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['status' => false,'message' => 'Invalid password'], 401);
            }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['status' => true,'message' => 'Login successful',
        'user' => $user,'token' => $token], 200);
    }
    public function requestVisa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visa_id' => 'required|exists:visa,id',
            'passport_file' => 'required|file',
            'photo_file' => 'required|file'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $visa = Visa::find($request->visa_id);
        if (!$visa) {
            return response()->json([
                'status' => false,
                'message' => 'Visa not found'
            ], 404);
        }

        $passportPath = $request->file('passport_file')->store('passports');
        $photoPath = $request->file('photo_file')->store('photos');
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'type' => 'visa',
            'status' => 'pending',
            'price' => $visa->Total_cost
        ]);
        $visaBooking = new VisaBooking();
        $visaBooking->user_id = auth()->id();
        $visaBooking->user_name = auth()->user()->name;
        $visaBooking->PhotoFile = $photoPath;
        $visaBooking->PassportFile = $passportPath;
        $visaBooking->status = 'pending';
        $visaBooking->save();

        return response()->json([
            'status' => true,
            'message' => 'Visa booking request submitted successfully',
            'booking' => $booking,
            'visa_booking' => $visaBooking
        ], 201);
    }

    public function requestTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flight_id' => 'required|exists:flights,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }
        
        $flight = Flight::find($request->flight_id);
        if (!$flight) {
            return response()->json([
                'status' => false,
                'message' => 'Flight not found'
            ], 404);
        }
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'type' => 'ticket',
            'status' => 'pending',
            'price' => $flight->price
        ]);
        $ticketRequest = new TicketRequest();
        $ticketRequest->user_id = auth()->id();
        $ticketRequest->flight_id = $request->flight_id;
        $ticketRequest->total_price = $flight->price;
        $ticketRequest->status = 'pending';
        $ticketRequest->save();

        return response()->json([
            'status' => true,
            'message' => 'Ticket request submitted successfully',
            'ticket_request' => $ticketRequest,
            'booking' => $booking
        ], 201);
    }

    public function requestHaj(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'haj_id' => 'required|exists:haj,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $haj = Haj::find($request->haj_id);
        if (!$haj) {
            return response()->json([
                'status' => false,
                'message' => 'Haj package not found'
            ], 404);
        }

        
        $booking = Booking::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'type' => 'haj',
            'status' => 'pending',
            'price' => $haj->total_price
        ]);

        $hajBooking = new HajBooking();
        $hajBooking->user_id = auth()->id();
        $hajBooking->haj_id = $request->haj_id;
        $hajBooking->status = 'pending';
        $hajBooking->save();

        return response()->json([
            'status' => true,
            'message' => 'Haj booking request submitted successfully',
            'booking' => $booking,
            'haj_booking' => $hajBooking
        ], 201);
    }
}

