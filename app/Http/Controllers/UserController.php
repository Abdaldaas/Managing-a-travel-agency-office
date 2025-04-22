<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
class UserController extends Controller
{
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
                'status' => false,
                'message' => 'Invalid data', 
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Email not registered'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function requestPassport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passport_number' => 'required|string|unique:passports,passport_number',
            'issue_date' => 'required|date|before:today',
            'expiry_date' => 'required|date|after:issue_date',
            'place_of_issue' => 'required|string|max:100',
            'nationality' => 'required|string|max:50',
            'passport_image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $passport = new Passport();
        $passport->user_id = auth()->id();
        $passport->passport_number = $request->passport_number;
        $passport->issue_date = $request->issue_date;
        $passport->expiry_date = $request->expiry_date;
        $passport->place_of_issue = $request->place_of_issue;
        $passport->nationality = $request->nationality;
        $passport->status = 'pending';
        $passport->save();

        return response()->json([
            'status' => true,
            'message' => 'Passport request submitted successfully',
            'passport' => $passport
        ], 201);
    }

    public function requestVisa(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'country_id' => 'required|exists:countries,id',
            'visa_type' => 'required|in:tourist,business,student,work',
            'start_date' => 'required|date|after:today',
            'duration' => 'required|integer|min:1',
            'passport_id' => 'required|exists:passports,id',
            'supporting_documents' => 'required|array',
            'supporting_documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $visa = new Visa();
        $visa->user_id = auth()->id();
        $visa->country_id = $request->country_id;
        $visa->visa_type = $request->visa_type;
        $visa->start_date = $request->start_date;
        $visa->duration = $request->duration;
        $visa->passport_id = $request->passport_id;
        $visa->status = 'pending';
        $visa->save();

        return response()->json([
            'status' => true,
            'message' => 'Visa request submitted successfully',
            'visa' => $visa
        ], 201);
    }

    public function requestHaj(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passport_id' => 'required|exists:passports,id',
            'preferred_date' => 'required|date|after:today',
            'package_type' => 'required|in:economy,standard,premium',
            'accommodation_type' => 'required|string',
            'number_of_people' => 'required|integer|min:1',
            'special_requirements' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $haj = new Haj();
        $haj->user_id = auth()->id();
        $haj->passport_id = $request->passport_id;
        $haj->preferred_date = $request->preferred_date;
        $haj->package_type = $request->package_type;
        $haj->accommodation_type = $request->accommodation_type;
        $haj->number_of_people = $request->number_of_people;
        $haj->special_requirements = $request->special_requirements;
        $haj->status = 'pending';
        $haj->save();

        return response()->json([
            'status' => true,
            'message' => 'Haj request submitted successfully',
            'haj' => $haj
        ], 201);
    }

    public function requestTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flight_id' => 'required|exists:flights,id',
            'passport_id' => 'required|exists:passports,id',
            'seat_preference' => 'nullable|in:window,aisle,middle',
            'meal_preference' => 'nullable|string',
            'special_assistance' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $flight = Flight::find($request->flight_id);
        if ($flight->available_seats < 1) {
            return response()->json([
                'status' => false,
                'message' => 'No available seats on this flight'
            ], 400);
        }

        $ticketRequest = new TicketRequest();
        $ticketRequest->user_id = auth()->id();
        $ticketRequest->flight_id = $request->flight_id;
        $ticketRequest->passport_id = $request->passport_id;
        $ticketRequest->seat_preference = $request->seat_preference;
        $ticketRequest->meal_preference = $request->meal_preference;
        $ticketRequest->special_assistance = $request->special_assistance;
        $ticketRequest->status = 'pending';
        $ticketRequest->save();

        return response()->json([
            'status' => true,
            'message' => 'Ticket request submitted successfully',
            'ticket_request' => $ticketRequest
        ], 201);
    }
}
