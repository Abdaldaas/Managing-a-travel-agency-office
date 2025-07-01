<?php

namespace App\Http\Controllers;

use App\Models\TaxiDriver;
use App\Models\TaxiRequest;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TaxiController extends Controller
{
    /**
     * Request a taxi for a booking
     */
    public function requestTaxi(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_id' => 'required|exists:bookings,id',
                'pickup_latitude' => 'required|numeric|between:-90,90',
                'pickup_longitude' => 'required|numeric|between:-180,180',
                'pickup_address' => 'required|string',
                'scheduled_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get the booking
            $booking = Booking::findOrFail($request->booking_id);

            // Verify booking belongs to user
            if ($booking->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Set destination based on booking type
            $destination = $this->getDestinationByBookingType($booking->type);
            if (!$destination) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid booking type for taxi service'
                ], 400);
            }

            // Calculate distance
            $distance = $this->calculateDistance(
                $request->pickup_latitude,
                $request->pickup_longitude,
                $destination['latitude'],
                $destination['longitude']
            );

            // Create taxi request
            $taxiRequest = new TaxiRequest([
                'user_id' => auth()->id(),
                'booking_id' => $booking->id,
                'booking_type' => $booking->type,
                'pickup_latitude' => $request->pickup_latitude,
                'pickup_longitude' => $request->pickup_longitude,
                'pickup_address' => $request->pickup_address,
                'destination_latitude' => $destination['latitude'],
                'destination_longitude' => $destination['longitude'],
                'destination_address' => $destination['address'],
                'distance_km' => $distance,
                'scheduled_at' => $request->scheduled_at
            ]);

            // Calculate price using config values
            $baseFare = Config::get('taxi.pricing.base_fare');
            $pricePerKm = Config::get('taxi.pricing.price_per_km');
            $taxiRequest->price = $baseFare + ($distance * $pricePerKm);
            
            // Save the request
            $taxiRequest->save();

            // Update booking total price
            $booking->price += $taxiRequest->price;
            $booking->save();

            // Find nearest available drivers
            $nearestDrivers = $this->findNearestDrivers(
                $request->pickup_latitude,
                $request->pickup_longitude,
                Config::get('taxi.search.max_distance'),
                Config::get('taxi.search.max_results')
            );

            return response()->json([
                'status' => true,
                'message' => 'Taxi request created successfully',
                'taxi_request' => $taxiRequest,
                'nearest_drivers' => $nearestDrivers
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error creating taxi request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get destination coordinates and address based on booking type
     */
    private function getDestinationByBookingType($type)
    {
        switch ($type) {
            case 'ticket':
                return [
                    'latitude' => Config::get('taxi.airport.latitude'),
                    'longitude' => Config::get('taxi.airport.longitude'),
                    'address' => Config::get('taxi.airport.address')
                ];
            case 'visa':
            case 'passport':
                return [
                    'latitude' => Config::get('taxi.office.latitude'),
                    'longitude' => Config::get('taxi.office.longitude'),
                    'address' => Config::get('taxi.office.address')
                ];
            default:
                return null;
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Radius of the earth in km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * Find nearest available drivers
     */
    private function findNearestDrivers($latitude, $longitude, $maxDistance = null, $limit = null)
    {
        if ($maxDistance === null) {
            $maxDistance = Config::get('taxi.search.max_distance', 10);
        }

        if ($limit === null) {
            $limit = Config::get('taxi.search.max_results', 5);
        }

        return TaxiDriver::select(DB::raw('*, 
            ( 6371 * acos( cos( radians(?) ) * 
            cos( radians( current_latitude ) ) * 
            cos( radians( current_longitude ) - radians(?) ) + 
            sin( radians(?) ) * 
            sin( radians( current_latitude ) ) ) ) AS distance'))
            ->addBinding([$latitude, $longitude, $latitude], 'select')
            ->where('status', 'available')
            ->having('distance', '<', $maxDistance)
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }

    /**
     * Accept a taxi request (for drivers)
     */
    public function acceptRequest(Request $request, $requestId)
    {
        try {
            $user = auth()->user();
            
            if (!$user->isTaxiDriver()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only taxi drivers can accept requests'
                ], 403);
            }

            $taxiRequest = TaxiRequest::findOrFail($requestId);
            
            if ($taxiRequest->status !== 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'This request is no longer available'
                ], 400);
            }

            $driver = $user->taxiDriver;
            
            if ($driver->status !== 'available') {
                return response()->json([
                    'status' => false,
                    'message' => 'You must be available to accept new requests'
                ], 400);
            }

            DB::transaction(function () use ($taxiRequest, $driver) {
                $taxiRequest->driver_id = $driver->id;
                $taxiRequest->status = 'accepted';
                $taxiRequest->save();

                $driver->status = 'busy';
                $driver->save();
            });

            return response()->json([
                'status' => true,
                'message' => 'Request accepted successfully',
                'taxi_request' => $taxiRequest
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error accepting request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update driver's current location
     */
    public function updateLocation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()
                ], 400);
            }

            $user = auth()->user();
            
            if (!$user->isTaxiDriver()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only taxi drivers can update location'
                ], 403);
            }

            $driver = $user->taxiDriver;
            $driver->updateLocation($request->latitude, $request->longitude);

            return response()->json([
                'status' => true,
                'message' => 'Location updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error updating location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a taxi trip
     */
    public function completeTrip(Request $request, $requestId)
    {
        try {
            $user = auth()->user();
            
            if (!$user->isTaxiDriver()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only taxi drivers can complete trips'
                ], 403);
            }

            $taxiRequest = TaxiRequest::findOrFail($requestId);
            
            if ($taxiRequest->driver_id !== $user->taxiDriver->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only complete your own trips'
                ], 403);
            }

            if ($taxiRequest->status !== 'in_progress') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only in-progress trips can be completed'
                ], 400);
            }

            DB::transaction(function () use ($taxiRequest, $user) {
                $taxiRequest->status = 'completed';
                $taxiRequest->completed_at = now();
                $taxiRequest->save();

                $driver = $user->taxiDriver;
                $driver->status = 'available';
                $driver->total_trips += 1;
                $driver->save();
            });

            return response()->json([
                'status' => true,
                'message' => 'Trip completed successfully',
                'taxi_request' => $taxiRequest
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error completing trip: ' . $e->getMessage()
            ], 500);
        }
    }
} 