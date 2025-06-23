<?php

namespace App\Http\Controllers;

use App\Models\Haj;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HajController extends Controller
{
    public function addHajTrip(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'package_type' => 'required|in:haj,umrah',
            'total_price' => 'required|numeric|min:0',
            'departure_date' => 'required|date|after:now',
            'return_date' => 'required|date|after:departure_date',
            'takeoff_time' => 'required|date_format:H:i',
            'landing_time' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $hajTrip = Haj::create([
            'package_type' => $request->package_type,
            'total_price' => $request->total_price,
            'departure_date' => $request->departure_date,
            'return_date' => $request->return_date,
            'takeoff_time' => $request->takeoff_time,
            'landing_time' => $request->landing_time
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Haj trip added successfully',
            'trip' => $hajTrip
        ], 201);
    }

    public function updateHajTrip(Request $request, $id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'package_type' => 'required|in:haj,umrah',
            'total_price' => 'required|numeric|min:0',
            'departure_date' => 'required|date|after:now',
            'return_date' => 'required|date|after:departure_date',
            'takeoff_time' => 'required|date_format:H:i',
            'landing_time' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $hajTrip = Haj::find($id);
        if (!$hajTrip) {
            return response()->json([
                'status' => false,
                'message' => 'Haj trip not found'
            ], 404);
        }

        $hajTrip->update([
            'package_type' => $request->package_type,
            'total_price' => $request->total_price,
            'departure_date' => $request->departure_date,
            'return_date' => $request->return_date,
            'takeoff_time' => $request->takeoff_time,
            'landing_time' => $request->landing_time
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Haj trip updated successfully',
            'trip' => $hajTrip
        ]);
    }

    public function deleteHajTrip($id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $hajTrip = Haj::find($id);
        if (!$hajTrip) {
            return response()->json([
                'status' => false,
                'message' => 'Haj trip not found'
            ], 404);
        }

        // Check if there are any bookings related to this haj trip
        $hasBookings = Booking::where('type', 'haj')
            ->where('type', Haj::class)
            ->where('id', $id)
            ->exists();

        if ($hasBookings) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete haj trip with existing bookings'
            ], 400);
        }

        $hajTrip->delete();

        return response()->json([
            'status' => true,
            'message' => 'Haj trip deleted successfully'
        ]);
    }

    public function getAllHajTrips()
    {
        $hajTrips = Haj::orderBy('departure_date', 'asc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Haj trips retrieved successfully',
            'trips' => $hajTrips
        ]);
    }

    public function getHajTripById($id)
    {
        $hajTrip = Haj::where('id',$id)->get();
        if (!$hajTrip) {
            return response()->json([
                'status' => false,
                'message' => 'Haj trip not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Haj trip retrieved successfully',
            'trip' => $hajTrip
        ],200);
    }
}