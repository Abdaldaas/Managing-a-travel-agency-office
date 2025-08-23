<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Models\Airport;
use App\Models\TicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;
use App\Models\User;

class FlightController extends Controller
{
     protected $notificationService;
   public function __construct(NotificationService $notificationService)
    {
    $this->notificationService = $notificationService;
    }

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
            'departure_airport_id' => 'required|exists:airports,id',
            'arrival_airport_id' => 'required|exists:airports,id|different:departure_airport_id',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'price' => 'required|numeric|min:0',
            'available_seats' => 'required|integer|min:0',
            'status' => 'sometimes|in:scheduled,delayed,cancelled,completed'
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
            'departure_airport_id' => $request->departure_airport_id,
            'arrival_airport_id' => $request->arrival_airport_id,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'price' => $request->price,
            'available_seats' => $request->available_seats,
            'status' => $request->status ?? 'scheduled'
        ]);

    
        $flight->load(['departureAirport', 'arrivalAirport']);

        $notificationService = new NotificationService();

        $users = User::whereNotNull('fcm_token')->get();

        $title = 'New Flight Added';
        $message = "A new flight has been added: Flight {$flight->flight_number} from {$flight->departureAirport->name} to {$flight->arrivalAirport->name}.";
        
        if ($users->count() > 0) {
            $notificationService->sendToMany($title, $message, $users);
        }
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
            'departure_airport_id' => 'required|exists:airports,id',
            'arrival_airport_id' => 'required|exists:airports,id|different:departure_airport_id',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'price' => 'required|numeric|min:0',
            'available_seats' => 'required|integer|min:0',
            'status' => 'sometimes|in:scheduled,delayed,cancelled,completed'
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
            'departure_airport_id' => $request->departure_airport_id,
            'arrival_airport_id' => $request->arrival_airport_id,
            'departure_time' => $request->departure_time,
            'arrival_time' => $request->arrival_time,
            'price' => $request->price,
            'available_seats' => $request->available_seats,
            'status' => $request->status ?? $flight->status
        ]);


        $flight->load(['departureAirport', 'arrivalAirport']);
        $notificationService = new NotificationService();
        $users = User::whereNotNull('fcm_token')->get();
        $title = 'Flight Status Update';
        $message = "Flight {$flight->flight_number} status has been updated to {$flight->status}.";
        
        if ($users->count() > 0) {
            $notificationService->sendToMany($title, $message, $users);
        }
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
        $flights = Flight::with(['departureAirport', 'arrivalAirport'])
            ->orderBy('departure_time', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Flights retrieved successfully',
            'flights' => $flights
        ]);
    }

    public function getFlightById($id)
    {
        $flight = Flight::with(['departureAirport', 'arrivalAirport'])->find($id);
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

    public function searchFlights(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'departure' => 'required|string|size:3', 
            'arrival' => 'required|string|size:3',   
            'date' => 'required|date|after_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid search parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        $departureAirport = Airport::where('iata_code', $request->departure)->first();
        $arrivalAirport = Airport::where('iata_code', $request->arrival)->first();

        if (!$departureAirport || !$arrivalAirport) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid airport code(s)',
                'errors' => [
                    'airports' => ['One or both airports not found']
                ]
            ], 400);
        }

        $flights = Flight::with(['departureAirport', 'arrivalAirport'])
            ->where('departure_airport_id', $departureAirport->id)
            ->where('arrival_airport_id', $arrivalAirport->id)
            ->whereDate('departure_time', $request->date)
            ->where('status', '!=', 'cancelled')
            ->where('available_seats', '>', 0)
            ->orderBy('departure_time')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Flights found successfully',
            'flights' => $flights
        ]);
    }
    public function getAirports()
    {
        $airports = Airport::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'iata_code', 'city', 'country']);
        return response()->json([
            'status' => true,
            'airports' => $airports
        ]);
    }
}