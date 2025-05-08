<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\TicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FlightController extends Controller
{
    public function addFlight(Request $request)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'flight_number' => 'required|string',
            'airline' => 'required|string',
            'departure_city' => 'required|string',
            'arrival_city' => 'required|string',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'price' => 'required|numeric|min:0',
            'available_seats' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $flight = Flight::create([
            'flight_number' => $request->flight_number,
            'airline' => $request->airline,
            'departure_city' => $request->departure_city,
            'arrival_city' => $request->arrival_city,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'price' => $request->price,
            'available_seats' => $request->available_seats
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Flight added successfully',
            'flight' => $flight
        ], 201);
    }

    public function updateFlight(Request $request, $id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'flight_number' => 'required|string',
            'airline' => 'required|string',
            'departure_city' => 'required|string',
            'arrival_city' => 'required|string',
            'departure_time' => 'required|date|after:now',
            'arrival_time' => 'required|date|after:departure_time',
            'price' => 'required|numeric|min:0',
            'available_seats' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $flight = Flight::find($id);
        if (!$flight) {
            return response()->json([
                'status' => false,
                'message' => 'Flight not found'
            ], 404);
        }

        $flight->update([
            'flight_number' => $request->flight_number,
            'airline' => $request->airline,
            'departure_city' => $request->departure_city,
            'arrival_city' => $request->arrival_city,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'price' => $request->price,
            'available_seats' => $request->available_seats
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Flight updated successfully',
            'flight' => $flight
        ]);
    }

    public function deleteFlight($id)
    {
        if (!auth()->user() || !in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to perform this action'
            ], 403);
        }

        $flight = Flight::find($id);
        if (!$flight) {
            return response()->json([
                'status' => false,
                'message' => 'Flight not found'
            ], 404);
        }

        // Check if there are any ticket requests for this flight
        $hasTicketRequests = TicketRequest::where('flight_id', $id)->exists();
        if ($hasTicketRequests) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete flight with existing ticket requests'
            ], 400);
        }

        $flight->delete();

        return response()->json([
            'status' => true,
            'message' => 'Flight deleted successfully'
        ]);
    }

    public function getAllFlights()
    {
        $flights = Flight::orderBy('departure_time', 'asc')->get();

        return response()->json([
            'status' => true,
            'message' => 'Flights retrieved successfully',
            'flights' => $flights
        ]);
    }

    public function getFlightById($id)
    {
        $flight = Flight::find($id);
        if (!$flight) {
            return response()->json([
                'status' => false,
                'message' => 'Flight not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Flight retrieved successfully',
            'flight' => $flight
        ]);
    }
}