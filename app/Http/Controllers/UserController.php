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
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string',
            'nationality' => 'required|string',
            'national_number' => 'required|string',
            'gender' => 'required|in:male,female',
            'identity_front' => 'required|file',
            'identity_back' => 'required|file',
            'num_dependents'=>'required|integer|max:2',
            'dependent_details' => 'nullable|array',
            'has_old_passport' => 'required|in:true,false,0,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $identity_front = $request->file('identity_front')->store('identity_front');
        $identity_back =$request->file('identity_back')->store('identity_back');
        do {
            $passport_number = 'P' . mt_rand(10000000, 99999999);
        } while (Passport::where('passport_number', $passport_number)->exists());

        $passport = Passport::create([
            'user_id' => auth()->id(),
            'passport_number' => $passport_number,
            'passport_type' => $request->passport_type,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'father_name' => $request->father_name,
            'mother_name' => $request->mother_name,
            'date_of_birth' => $request->date_of_birth,
            'place_of_birth' => $request->place_of_birth,
            'nationality' => $request->nationality,
            'national_number' => $request->national_number,
            'gender' => $request->gender,
            'identity_front' => $identity_front,
            'identity_back'=> $identity_back,
            'num_dependents' => $request->num_dependents,
            'dependent_details' => $request->dependent_details,
            'has_old_passport' => filter_var($request->has_old_passport, FILTER_VALIDATE_BOOLEAN)
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
            'passport_file' => 'required|file',
            'photo_file' => 'required|file',
            'health_report_file' => 'required|file',
            'vaccination_certificate' => 'required|file'
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

        $passportPath = $request->file('passport_file')->store('passports');
        $photoPath = $request->file('photo_file')->store('photos');
        $healthReportPath = $request->file('health_report_file')->store('health_reports');
        $vaccinationPath = $request->file('vaccination_certificate')->store('vaccination_certificates');

        $hajBooking = new HajBooking();
        $hajBooking->user_id = auth()->id();
        $hajBooking->haj_id = $request->haj_id;
        $hajBooking->status = 'pending';
        $hajBooking->passport_file = $passportPath;
        $hajBooking->photo_file = $photoPath;
        $hajBooking->health_report_file = $healthReportPath;
        $hajBooking->vaccination_certificate = $vaccinationPath;
        $hajBooking->save();

        return response()->json([
            'status' => true,
            'message' => 'Haj booking request submitted successfully',
            'booking' => $booking,
            'haj_booking' => $hajBooking
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => true, 'message' => 'Logged out successfully'], 200);
    }

    public function getBookings(Request $request)
    {
        $bookings = Booking::where('user_id', auth()->id())->get();
        return response()->json(['status' => true, 'bookings' => $bookings], 200);
    }

    public function cancelBooking(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Booking not found'], 404);
        }

        if ($booking->user_id !== auth()->id()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $booking->delete();

        return response()->json(['status' => true, 'message' => 'Booking canceled successfully'], 200);
    }

    public function getRequestsStatus(Request $request)
    {
        $userId = auth()->id();

        $passportRequests = PassportRequest::where('user_id', $userId)->get();
        $visaBookings = VisaBooking::where('user_id', $userId)->get();
        $ticketRequests = TicketRequest::where('user_id', $userId)->get();
        $hajBookings = HajBooking::where('user_id', $userId)->get();

        return response()->json([
            'status' => true,
            'requests' => [
                'passport_requests' => $passportRequests,
                'visa_bookings' => $visaBookings,
                'ticket_requests' => $ticketRequests,
                'haj_bookings' => $hajBookings,
            ]
        ], 200);
    }
}

