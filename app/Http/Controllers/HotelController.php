<?php

namespace App\Http\Controllers;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;
use App\Models\Booking;
use App\Models\TicketRequest;
use App\Models\HotelRequest;
use Stripe\Stripe;

class HotelController extends Controller
{
    public function addHotel(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'country' => 'required|string',
            'city' => 'required|string',
            'price'=>'required|numeric',
            'stars'=>'required|numeric'
          
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }
        
        $Hotel = Hotel::create([
            'name' => $request->name,
            'country' => $request->country,
            'city' => $request->city,
            'price'=>$request->price,
            'stars'=>$request->stars,
        ]);
        $notificationService = new NotificationService();
        $users = User::whereNotNull('fcm_token')->get();
    
        $title = 'New Hotel Offer Available!';
        $message = "A new Hotel offer to {$Hotel->country} has been added. Check it out!";
        if ($users->count() > 0) {
            $notificationService->sendToMany($title, $message, $users);
        }
        return response()->json([
            'status' => true,
            'message' => 'Hotel added successfully',
            'Hotel' => $Hotel,], 201);
    }

    public function deleteHotel($id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $Hotel = Hotel::find($id);
        if (!$Hotel) {
            return response()->json([
                'status' => false,
                'message' => 'Hotel not found'
            ], 404);
        }

       
        $hasBookings = Booking::where('type', 'hotel')
            ->exists();

        if ($hasBookings) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete hotel with existing bookings'
            ], 400);
        }

        $Hotel->delete();

        return response()->json([
            'status' => true,
            'message' => 'Hotel deleted successfully'
        ]);
    }

    public function getAllHotels()
    {
        $hotels = Hotel::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => true,
            'message' => 'Hotels retrieved successfully',
            'hotels' => $hotels
        ]);
    }

    public function requestHotel(Request $request){

        $validator = Validator::make($request->all(), [
            'hotel_id' => 'required|exists:hotels,id',
            'ticket_request_id'=>'required|exists:ticket_requests,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $ticketRequest = TicketRequest::where('id', $request->ticket_request_id)
                ->where('user_id', auth()->id())
                ->first();

        if (!$ticketRequest) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to request a hotel for this ticket'
            ], 403);
        }
    
        $hotel = Hotel::find($request->hotel_id);
        if (!$hotel) {
            return response()->json([
                'status' => false,
                'message' => 'Hotel not found'
            ], 404);
        }

        try {
            // Create HotelRequest first with pending status
            $hotelRequest = HotelRequest::create([
                'user_id' => auth()->id(),
                'hotel_id' => $hotel->id,
                'ticket_id' => $ticketRequest->id,
                'price' => $hotel->price,
                'status' => 'initiated',
            ]);

            Stripe::setApiKey(config('services.stripe.secret'));

            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => (int) round($hotel->price * 100),
                        'product_data' => [
                            'name' => 'Hotel Booking',
                            'description' => "Hotel: {$hotel->name}, {$hotel->city}, {$hotel->country}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'booking_type' => 'hotel',
                    'user_id' => auth()->id(),
                    'hotel_id' => $hotel->id,
                    'hotel_request_id' => $hotelRequest->id,
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
}