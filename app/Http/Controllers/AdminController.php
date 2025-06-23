<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Passport;
use App\Models\Visa;
use App\Models\HajBooking;
use App\Models\Flight;
use App\Models\Booking;
use App\Models\TicketRequest;
use App\Models\RejectionReason;
use App\Models\Notification;
use App\Models\VisaBooking;
use App\Models\PassportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller {
    public function adminLogin(Request $request)
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

        $user = User::where('email', $request->email)
            ->whereIn('role', ['admin', 'super_admin'])
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'The email is not registered or not authorized to login as admin'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'admin' => $user,
            'token' => $token
        ], 200);
    }
    public function getAllUsers(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $users = User::where('role', '!=', 'super_admin')
            ->when($request->search, function($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'message' => 'Users fetched successfully',
            'users' => $users
        ]);
    }

    public function getUserDetails($id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $user = User::with(['passports', 'visas', 'hajRequests', 'ticketRequests'])
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'User details fetched successfully',
            'user' => $user
        ]);
    }
    public function viewAllBookings(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin', 'security'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $bookings = Booking::with(['user'])->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'bookings' => $bookings
        ], 200);
    }




public function handleTicketRequest(Request $request)
{
    if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
        return response()->json([
            'status' => false,
            'message' => 'You are not authorized to perform this action'
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'ticket_request_id' => 'required|exists:ticket_requests,id',
        'status' => 'required|in:approved,rejected',
        'rejection_reason' => 'required_if:status,rejected|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid data',
            'errors' => $validator->errors()
        ], 400);
    }

    $ticketRequest = TicketRequest::find($request->ticket_request_id);
    if (!$ticketRequest) {
        return response()->json([
            'status' => false,
            'message' => 'Ticket request not found'
        ], 404);
    }

    if ($ticketRequest->status !== 'pending') {
        return response()->json([
            'status' => false,
            'message' => 'This ticket request has already been processed'
        ], 400);
    }

    $ticketRequest->status = $request->status;
    $ticketRequest->save();

    // Update the corresponding booking status
    $booking = Booking::where('user_id', $ticketRequest->user_id)
        ->where('type', 'ticket')
        ->where('status', 'pending')
        ->first();

    if ($booking) {
        $booking->status = $request->status;
        $booking->save();
    }

    if ($request->status === 'rejected' && $request->rejection_reason) {
        $rejectionReason = new RejectionReason([
            'reason' => $request->rejection_reason,
            'request_type' => 'ticket',
            'request_id' => $ticketRequest->id
        ]);
        $rejectionReason->save();

        // Create notification for rejection
     Notification::create([
            'user_id' => $ticketRequest->user_id,
            'title' => 'Ticket Request Rejected',
            'message' => 'Your ticket request has been rejected. Reason: ' . $request->rejection_reason,
            'type' => 'ticket_request'
        ]);
    } 

    return response()->json([
        'status' => true,
        'message' => 'Ticket request ' . $request->status . ' successfully',
        'ticket_request' => $ticketRequest
    ]);
}

public function updateVisaBooking(Request $request)
{
    if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
        return response()->json([
            'status' => false,
            'message' => 'You are not authorized to perform this action'
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'visa_booking_id' => 'required|exists:visa_bookings,id',
        'status' => 'required|in:approved,rejected',
        'rejection_reason' => 'required_if:status,rejected|string|nullable'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid data',
            'errors' => $validator->errors()
        ], 400);
    }

    $visaBooking = \App\Models\VisaBooking::find($request->visa_booking_id);
    if (!$visaBooking) {
        return response()->json([
            'status' => false,
            'message' => 'Visa booking not found'
        ], 404);
    }

    $visaBooking->status = $request->status;
    if ($request->status === 'rejected' && $request->rejection_reason) {
        $visaBooking->rejection_reason = $request->rejection_reason;
    }
    $visaBooking->save();

    return response()->json([
        'status' => true,
        'message' => 'Visa booking status updated successfully',
        'visa_booking' => $visaBooking
    ]);
}        

    public function handleHajBookingRequest(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'haj_booking_id' => 'required|exists:haj_bookings,id',
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $hajBooking = HajBooking::find($request->haj_booking_id);
        if (!$hajBooking) {
            return response()->json([
                'status' => false,
                'message' => 'Haj booking request not found'
            ], 404);
        }

        if ($hajBooking->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'This haj booking request has already been processed'
            ], 400);
        }

        $hajBooking->status = $request->status;
        $hajBooking->admin_id = auth()->id();
        $hajBooking->save();

        // Update the corresponding booking status
        $booking = Booking::where('user_id', $hajBooking->user_id)
            ->where('type', 'haj')
            ->where('status', 'pending')
            ->first();

        if ($booking) {
            $booking->status = $request->status;
            $booking->save();
        }

        if ($request->status === 'rejected' && $request->rejection_reason) {
            $rejectionReason = new RejectionReason([
                'reason' => $request->rejection_reason,
                'request_type' => 'haj',
                'request_id' => $hajBooking->id
            ]);
            $rejectionReason->save();

            
        } 

        return response()->json([
            'status' => true,
            'message' => 'Haj booking request processed successfully',
            'haj_booking' => $hajBooking
        ]);
    }
    public function getBookingDetails($id)
    {
        $booking = Booking::where('id', $id)->first();
        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'booking not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'booking retrieved successfully',
            'visa' => $booking
        ]);
    }
    public function getAllVisaBookings()
    {
        try {
            $visaBookings = VisaBooking::get();
            return response()->json([
                'status' => 'success',
                'message' => 'All visa bookings retrieved successfully',
                'data' => $visaBookings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while retrieving visa bookings'
            ], 500);
        }
    }

    public function getAllHajBookings()
    {
        try {
            $hajBookings = HajBooking::get();
            return response()->json([
                'status' => 'success',
                'message' => 'All Hajj bookings retrieved successfully',
                'data' => $hajBookings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while retrieving Hajj bookings'
            ], 500);
        }
    }

    public function getAllTicketRequests()
    {
        try {
            $ticketRequests = TicketRequest::get();
            return response()->json([
                'status' => 'success',
                'message' => 'All ticket requests retrieved successfully',
                'data' => $ticketRequests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while retrieving ticket requests'
            ], 500);
        }
    }

    public function getAllPassportRequests()
    {
        try {
            $passportRequests = PassportRequest::get();
            return response()->json([
                'status' => 'success',
                'message' => 'All passport requests retrieved successfully',
                'data' => $passportRequests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while retrieving passport requests'
            ], 500);
        }
    }

    public function handlePassportRequest(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:passport_requests,id',
            'status' => 'required|in:pending_payment,completed,rejected',
            'rejection_reason' => 'required_if:status,rejected|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $passportRequest = PassportRequest::find($request->request_id);
        if (!$passportRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Passport request not found'
            ], 404);
        }

        if ($passportRequest->status !== 'processing') {
            return response()->json([
                'status' => false,
                'message' => 'This passport request has already been processed'
            ], 400);
        }

        $passportRequest->status = $request->status;
        $passportRequest->save();

        if ($request->status === 'rejected' && $request->rejection_reason) {
            $rejectionReason = new RejectionReason([
                'reason' => $request->rejection_reason,
                'request_type' => 'passport',
                'request_id' => $passportRequest->id
            ]);
            $rejectionReason->save();
        }

        // Create notification for the user
        // $title = 'Passport Request ' . ucfirst($request->status);
        // $message = 'Your passport request has been ' . $request->status . '.';
        // if ($request->status === 'rejected') {
        //     $message .= ' Reason: ' . $request->rejection_reason;
        // }

        // Notification::create([
        //     'user_id' => $passportRequest->user_id,
        //     'title' => $title,
        //     'message' => $message,
        //     'type' => 'passport_request'
        // ]);

        return response()->json([
            'status' => true,
            'message' => 'Passport request ' . $request->status . ' successfully',
            'passport_request' => $passportRequest
        ]);
    }

    public function updateBookingStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected',
                'rejection_reason' => 'required_if:status,rejected'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $booking = Booking::findOrFail($id);
            $booking->status = $request->status;
            
            if ($request->status === 'rejected' && $request->has('rejection_reason')) {
                $rejectionReason = new RejectionReason([
                    'reason' => $request->rejection_reason
                ]);
                $booking->rejectionReason()->save($rejectionReason);
            }
            
            $booking->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Booking status updated successfully',
                'data' => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while updating booking status'
            ], 500);
        }
    }


}