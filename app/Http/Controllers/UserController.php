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
use App\Models\HotelRequest;

use App\Models\PassportRequest;
use App\Models\User;
use Carbon\Carbon;

use App\Events\PassportRequested;
use App\Events\VisaRequested;
use App\Events\TicketRequested;
use Illuminate\Notifications\DatabaseNotification;
use App\Services\NotificationService;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\DB;


class UserController extends Controller {

     protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
    $this->notificationService = $notificationService;
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

    public function get_tickets(Request $request)
{
    $userId = auth()->id();

    $tickets = TicketRequest::where('user_id', $userId)->get();
    $flightIds = $tickets->pluck('flight_id')->unique();
    $flights = Flight::whereIn('id', $flightIds)->get();

    return response()->json([
        'status' => true,
        'tickets' => $tickets,
        'flights' => $flights
    ], 200);
}


    public function requestPassport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'passport_type' => 'required|in:regular,urgent,express',
            'first_name' => 'required|string|min:2',
            'last_name' => 'required|string|min:2',
            'father_name' => 'required|string',
            'mother_name' => 'required|string',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string',
            'nationality' => 'required|string',
            'national_number' => 'required|string|unique:passports,national_number',
            'gender' => 'required|in:male,female',
            'identity_front' => 'required|file',
            'identity_back' => 'required|file',
            'personal_photo' => 'required|file',
            'old_passport_page1' => 'nullable|file|required_if:has_old_passport,true,1',
            'old_passport_page2' => 'nullable|file|required_if:has_old_passport,true,1',
            'num_dependents'=>'nullable|integer|max:2',
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

        $age = Carbon::parse($request->date_of_birth)->age;

        $dependentDetails = $request->dependent_details;
        $numDependents = $request->num_dependents;

        if ($age < 18) {
            if (empty($dependentDetails) || $numDependents < 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Dependent details and number of dependents are required for applicants under 18.',
                ], 400);
            }
        } else {
            $dependentDetails = null;
            $numDependents = 0;
        }

        try {
            $personal_photo = null;
            if ($request->hasFile('personal_photo') && $request->file('personal_photo')->isValid()) {
                $personal_photo = $request->file('personal_photo')->store('personal_photos', 'public');
            }

            $identity_front = $request->file('identity_front')->store('identity_front', 'public');
            $identity_back = $request->file('identity_back')->store('identity_back', 'public');

            $old_passport_page1 = $request->hasFile('old_passport_page1') ? $request->file('old_passport_page1')->store('old_passports', 'public') : null;
            $old_passport_page2 = $request->hasFile('old_passport_page2') ? $request->file('old_passport_page2')->store('old_passports', 'public') : null;

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
                'personal_photo' => $personal_photo,
                'old_passport_page1' => $old_passport_page1,
                'old_passport_page2' => $old_passport_page2,
                'num_dependents' => $numDependents,
                'dependent_details' => json_encode($dependentDetails),
                'has_old_passport' => filter_var($request->has_old_passport, FILTER_VALIDATE_BOOLEAN)
            ]);

            $passportRequest = new PassportRequest();
            $passportRequest->user_id = auth()->id();
            $passportRequest->passport_id = $passport->id;
            $passportRequest->passport_type = $request->passport_type;
            $passportRequest->status = 'pending_payment';
            $passportRequest->calculatePrice();
            $passportRequest->save();
            try{
            Stripe::setApiKey(config('services.stripe.secret'));

            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $passportRequest->price * 100,
                        'product_data' => [
                            'name' => 'Passport Application',
                            'description' => "Type: " . ucfirst($request->passport_type),
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'booking_type' => 'passport',
                    'user_id' => auth()->id(),
                    'passport_request_id' => $passportRequest->id,
                     
                ]
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Redirecting to payment gateway',
                'url' => $checkout_session->url
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Error processing passport request: ' . $e->getMessage()
        ], 500);
    }
    
    }
    public function requestVisa(Request $request)
{
    $validator = Validator::make($request->all(), [
        'visa_id' => 'required|exists:visa,id', 
        'passport_file' => 'required|file',
        'photo_file' => 'required|file'
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => false, 'message' => 'Invalid data', 'errors' => $validator->errors()], 400);
    }

    $visa = Visa::where('id',$request->visa_id)->first();
    if (!$visa) {
        return response()->json(['status' => false, 'message' => 'Visa not found'], 404);
    }

    try {
 
        $passportPath = $request->file('passport_file')->store('passports');
        $photoPath = $request->file('photo_file')->store('photos');
        
       
        $visaBooking = VisaBooking::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'visa_id' => $visa->id,
            'PhotoFile' => $photoPath,
            'PassportFile' => $passportPath,
            'status' => 'pending',
        ]);

 
        Stripe::setApiKey(config('services.stripe.secret'));

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $visa->Total_cost * 100, 
                    'product_data' => [
                        'name' => 'Visa Application',
                        'description' => "Visa for " . $visa->country,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel'),
            'metadata' => [
                'booking_type' => 'visa',
                'user_id' => auth()->id(),
                'visa_id' => $visa->id,
                'visa_booking_id' => $visaBooking->id 
            ]
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Redirecting to payment gateway',
            'url' => $checkout_session->url
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()], 500);
    }
}

public function requestTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'flight_id' => 'required|exists:flights,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Invalid data', 'errors' => $validator->errors()], 400);
        }

        $flight = Flight::find($request->flight_id);
        if (!$flight) {
            return response()->json(['status' => false, 'message' => 'Flight not found'], 404);
        }

        try {
            $ticketRequest = TicketRequest::create([
                'user_id' => auth()->id(),
                'flight_id' => $request->flight_id,
                'total_price' => $flight->price,
                'status' => 'pending',
            ]);

            Stripe::setApiKey(config('services.stripe.secret'));

            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $flight->price * 100,
                        'product_data' => [
                            'name' => 'Flight Ticket Booking',
                            'description' => "Flight from {$flight->departure_airport} to {$flight->arrival_airport}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'booking_type' => 'ticket',
                    'user_id' => auth()->id(),
                    'flight_id' => $flight->id,
                    'ticket_request_id' => $ticketRequest->id
                ]
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Redirecting to payment gateway',
                'url' => $checkout_session->url
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()], 500);
        }
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

        try {
            $passportPath = $request->file('passport_file')->store('passports');
            $photoPath = $request->file('photo_file')->store('photos');
            $healthReportPath = $request->file('health_report_file')->store('health_reports');
            $vaccinationPath = $request->file('vaccination_certificate')->store('vaccination_certificates');

            $hajBooking = HajBooking::create([
                'user_id' => auth()->id(),
                'haj_id' => $request->haj_id,
                'status' => 'pending', 
                'passport_file' => $passportPath,
                'photo_file' => $photoPath,
                'health_report_file' => $healthReportPath,
                'vaccination_certificate' => $vaccinationPath
            ]);
            
            Stripe::setApiKey(config('services.stripe.secret'));

         
            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $haj->total_price * 100, 
                        'product_data' => [
                            'name' => 'Haj Package Booking',
                            'description' => $haj->package_type,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'booking_type' => 'haj',
                    'user_id' => auth()->id(),
                    'haj_id' => $request->haj_id,
                    'haj_booking_id' => $hajBooking->id
                ]
            ]);

           
            return response()->json([
                'status' => true,
                'message' => 'Redirecting to payment gateway',
                'url' => $checkout_session->url
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

   
    public function handlePaymentSuccess(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return response()->json(['status' => false, 'message' => 'Session ID not found.'], 400);
        }
    
        try {
            $session = Session::retrieve($sessionId, ['expand' => ['payment_intent']]);
            $paymentIntentId = null;
            if (isset($session->payment_intent)) {
                if (is_object($session->payment_intent) && isset($session->payment_intent->id)) {
                    $paymentIntentId = $session->payment_intent->id;
                } elseif (is_string($session->payment_intent)) {
                    $paymentIntentId = $session->payment_intent;
                }
            }
            
            if (!$session || $session->payment_status !== 'paid') {
                return response()->json(['status' => false, 'message' => 'Payment was not successful.'], 400);
            }
    
            if (!$session->metadata) {
                return response()->json(['status' => false, 'message' => 'Stripe session metadata is missing.'], 400);
            }
    
            $metadata = (object) $session->metadata->toArray();
            $bookingType = $metadata->booking_type ?? null;
            $userId = $metadata->user_id ?? null;
        
            if (!$bookingType || !$userId) {
                return response()->json(['status' => false, 'message' => 'Missing required metadata.'], 400);
            }
    
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'User not found.'], 404);
            }
    
            $notificationService = new NotificationService();
            $admin = User::where('role', 'admin')->first();
            $booking = null;
            if (!$bookingType || !$userId) {
                return response()->json(['status' => false, 'message' => 'Missing metadata from Stripe session.'], 400);
            }

            $booking = null;
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'User not found.'], 404);
            }

            $notificationService = new NotificationService();
            $admin = User::where('role', 'admin')->first();

            DB::transaction(function () use ($metadata, $bookingType, $user, &$booking, $notificationService, $admin, $session, $paymentIntentId) {
                switch ($bookingType) {
                    case 'haj':
                        $hajBookingId = $metadata->haj_booking_id ?? null;
                        $hajBooking = HajBooking::find($hajBookingId);
                        if ($hajBooking) {
                            $hajBooking->status = 'pending';
                            $hajBooking->save();

                            $haj = Haj::find($hajBooking->haj_id);
                            if ($haj) {
                                $booking = Booking::create([
                                    'user_id' => $user->id,
                                    'user_name' => $user->name,
                                    'type' => 'haj',
                                    'status' => 'pending',
                                    'price' => $haj->total_price,
                                    'stripe_payment_intent_id' => $paymentIntentId,
                                ]);
                            }
                        }
                        break;

                    case 'visa':
                        $visaBookingId = $metadata->visa_booking_id ?? null;
                        $visaBooking = VisaBooking::find($visaBookingId);
                        if ($visaBooking) {
                            $visaBooking->status = 'pending';
                            $visaBooking->save();

                            $visaId = $metadata->visa_id ?? null;
                            $visa = Visa::find($visaId);
                            if ($visa) {
                                $booking = Booking::create([
                                    'user_id' => $user->id,
                                    'user_name' => $user->name,
                                    'type' => 'visa',
                                    'status' => 'paid',
                                    'price' => $visa->Total_cost,
                                    'stripe_payment_intent_id' => $paymentIntentId,
                                ]);

                                $title = 'New Visa Request Paid';
                                $message = "A visa request to {$visa->country} has been paid for by {$user->name}.";
                                if ($admin) {
                                    $notificationService->sendToUser($title, $message, $admin);
                                }
                            }
                        }
                        break;

                    case 'ticket':
                        $ticketRequestId = $metadata->ticket_request_id ?? null;
                        $ticketRequest = TicketRequest::find($ticketRequestId);
                        if ($ticketRequest) {
                            $ticketRequest->status = 'pending';
                            $ticketRequest->save();

                            $flightId = $metadata->flight_id ?? null;
                            $flight = Flight::find($flightId);
                            if ($flight) {
                                $booking = Booking::create([
                                    'user_id' => $user->id,
                                    'user_name' => $user->name,
                                    'type' => 'ticket',
                                    'status' => 'paid',
                                    'price' => $flight->price,
                                    'stripe_payment_intent_id' => $paymentIntentId,
                                ]);

                                $title = 'New Ticket Request Paid';
                                $message = "A ticket request has been paid for by {$user->name}.";
                                if ($admin) {
                                    $notificationService->sendToUser($title, $message, $admin);
                                }
                            }
                        }
                        break;

                    case 'passport':
                        $passportRequestId = $metadata->passport_request_id ?? null;
                        $passportRequest = PassportRequest::find($passportRequestId);
                        if ($passportRequest) {
                            $passportRequest->status = 'processing';
                            $passportRequest->save();

                            $booking = Booking::create([
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'type' => 'passport',
                                'status' => 'paid',
                                'price' => $passportRequest->price,
                                'stripe_payment_intent_id' => $paymentIntentId,
                            ]);

                       
                            $title = 'New Passport Request Paid';
                            $message = "A passport request has been paid for by {$user->name}.";
                            if ($admin) {
                                $notificationService->sendToUser($title, $message, $admin);
                            }
                        }
                        break;

                    case 'hotel':
                        $hotelRequestId = $metadata->hotel_request_id ?? null;
                        $hotelRequest = HotelRequest::find($hotelRequestId);
                        if ($hotelRequest) {
                            // After payment, mark request as pending for admin processing
                            $hotelRequest->status = 'pending';
                            $hotelRequest->save();

                            $booking = Booking::create([
                                'user_id' => $user->id,
                                'user_name' => $user->name,
                                'type' => 'hotel',
                                'status' => 'pending',
                                'price' => $hotelRequest->price,
                                'stripe_payment_intent_id' => $paymentIntentId,
                            ]);

                            $title = 'New Hotel Request Paid';
                            $message = "A hotel booking request has been paid for by {$user->name}.";
                            if ($admin) {
                                $notificationService->sendToUser($title, $message, $admin);
                            }
                        }
                        break;
                }
            });

            if (!$booking) {
                return response()->json(['status' => false, 'message' => 'Booking record could not be processed.'], 404);
            }

            return response()->json(['status' => true, 'message' => 'Payment successful and booking created.', 'booking' => $booking], 200);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()], 500);
        }
    }


    

    
    public function handlePaymentCancel()
    {
        return response()->json([
            'status' => false,
            'message' => 'Payment was cancelled.'
        ], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
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

  
    if ($booking->status === 'complete') {
        return response()->json(['status' => false, 'message' => 'Cannot cancel a completed booking.'], 400);
    }
    
    
    if (is_null($booking->stripe_payment_intent_id)) {
        return response()->json(['status' => false, 'message' => 'Payment ID not found, cannot process refund automatically.'], 500);
    }

    try {
        Stripe::setApiKey(config('services.stripe.secret'));
        
        \Stripe\Refund::create([
            'payment_intent' => $booking->stripe_payment_intent_id,
        ]);
        $booking->status = 'canceled';
        $booking->save();
        return response()->json(['status' => true, 'message' => 'Booking canceled and refund is being processed.'], 200);

    } 
    catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Refund failed: ' . $e->getMessage()], 500);
    }
}
    public function getRequestsStatus(Request $request)
    {
        $userId = auth()->id();

        $passportRequests = PassportRequest::where('user_id', $userId)->get();
        $visaBookings = VisaBooking::where('user_id', $userId)->get();
        $ticketRequests = TicketRequest::where('user_id', $userId)->get();
        $hajBookings = HajBooking::where('user_id', $userId)->get();
        $hotelRequests = HotelRequest::where('user_id', $userId)->get();

        return response()->json([
            'status' => true,
            'requests' => [
                'passport_requests' => $passportRequests,
                'visa_bookings' => $visaBookings,
                'ticket_requests' => $ticketRequests,
                'haj_bookings' => $hajBookings,
                'hotel_requests' => $hotelRequests,
            ]
        ], 200);
    }

    public function getNotifications()
    {
        try {
            $user = auth()->user();
            
            return response()->json([
                'status' => true,
                'data' => [
                    'unread' => $user->unreadNotifications,
                    'read' => $user->readNotifications
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markNotificationAsRead($id)
    {
        try {
            $user = auth()->user();
            $notification = $user->unreadNotifications->where('id', $id)->first();

            if (!$notification) {
                return response()->json([
                    'status' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->markAsRead();

            return response()->json([
                'status' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error marking notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteNotification($id)
    {
        try {
            $user = auth()->user();
            $notification = $user->notifications->where('id', $id)->first();

            if (!$notification) {
                return response()->json([
                    'status' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'status' => true,
                'message' => 'Notification deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error deleting notification: ' . $e->getMessage()
            ], 500);
        }
    }
}

