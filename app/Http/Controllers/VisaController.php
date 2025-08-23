<?php

namespace App\Http\Controllers;

use App\Models\Visa;
use App\Models\Booking;
use App\Models\RejectionReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;
use App\Models\User;

class VisaController extends Controller
{
     protected $notificationService;
    public function __construct(NotificationService $notificationService)
    {
    $this->notificationService = $notificationService;
    }

    public function addVisa(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'country' => 'required|string',
            'visa_type' => 'required|string',
            'Total_cost' => 'required|numeric',
          
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }


        $visa = Visa::create([
            'country' => $request->country,
            'visa_type' => $request->visa_type,
            'Total_cost' => $request->Total_cost
        ]);
        $notificationService = new NotificationService();
        $users = User::whereNotNull('fcm_token')->get();
    
        $title = 'New Visa Offer Available!';
        $message = "A new visa offer to {$visa->country} has been added. Check it out!";
    
        if ($users->count() > 0) {
            $notificationService->sendToMany($title, $message, $users);
        }
        return response()->json([
            'status' => true,
            'message' => 'Visa added successfully',
            'visa' => $visa,], 201);
    }

 

    public function updateVisa(Request $request, $id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'country' => 'required|string',
            'visa_type' => 'required|string',
            'Total_cost' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $visa = Visa::find($id);
        if (!$visa) {
            return response()->json([
                'status' => false,
                'message' => 'Visa not found'
            ], 404);
        }

        $visa->update([
            'country' => $request->country,
            'visa_type' => $request->visa_type,
            'Total_cost' => $request->Total_cost
        ]);

        $booking = Booking::where('type', Visa::class)
            ->where('id', $visa->id)
            ->first();

        if ($booking) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot update visa with existing bookings'
            ], 400);
        }
        $notificationService = new NotificationService();
        $users = User::whereNotNull('fcm_token')->get();
        
        $title = 'Visa Offer Updated';
        $message = "The visa offer for {$visa->country} has been updated. Check the latest details!";
        
        if ($users->count() > 0) {
            $notificationService->sendToMany($title, $message, $users);
        }
        return response()->json([
            'status' => true,
            'message' => 'Visa updated successfully',
            'visa' => $visa,
            'booking' => $booking
        ]);
    }

    public function deleteVisa($id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $visa = Visa::find($id);
        if (!$visa) {
            return response()->json([
                'status' => false,
                'message' => 'Visa not found'
            ], 404);
        }

        // Check if there are any bookings related to this visa
        $hasBookings = Booking::where('type', 'visa')
            ->where('user_id', $visa->user_id)
            ->exists();

        if ($hasBookings) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete visa with existing bookings'
            ], 400);
        }

        $visa->delete();

        return response()->json([
            'status' => true,
            'message' => 'Visa deleted successfully'
        ]);
    }

    public function getAllVisas()
    {
        $visas = Visa::with('user')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Visas retrieved successfully',
            'visas' => $visas
        ]);
    }

    public function getVisaById($id)
    {
        $visa = Visa::with('user')->find($id);
        if (!$visa) {
            return response()->json([
                'status' => false,
                'message' => 'Visa not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Visa retrieved successfully',
            'visa' => $visa
        ]);
    }
}