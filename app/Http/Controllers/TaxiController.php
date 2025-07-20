<?php

namespace App\Http\Controllers;

use App\Models\TaxiDriver;
use App\Models\TaxiRequest;
use App\Models\Booking;
use App\Models\TicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\Rating;

class TaxiController extends Controller
{
    public function requestTaxi(Request $request)
    {
        $messages = [
            'ticket_request_id.required' => 'Ticket request ID is required',
            'ticket_request_id.exists' => 'Invalid ticket request ID',
            'taxi_driver_id.required' => 'Please select a driver',
            'taxi_driver_id.exists' => 'Selected driver is not available',
            'pickup_latitude.required' => 'Pickup latitude is required',
            'pickup_latitude.numeric' => 'Latitude must be a number',
            'pickup_latitude.between' => 'Latitude must be between -90 and 90',
            'pickup_longitude.required' => 'Pickup longitude is required',
            'pickup_longitude.numeric' => 'Longitude must be a number',
            'pickup_longitude.between' => 'Longitude must be between -180 and 180',
            'pickup_address.required' => 'Pickup address is required',
            'pickup_address.string' => 'Pickup address must be text',
            'scheduled_at.required' => 'Scheduled time is required',
            'scheduled_at.date' => 'Invalid date format',
            'scheduled_at.after' => 'Scheduled time must be in the future'
        ];

        try {
            $validated = $request->validate([
                'ticket_request_id' => 'required|exists:ticket_requests,id',
                'taxi_driver_id' => 'required|exists:taxi_drivers,id,status,available',
                'pickup_latitude' => 'required|numeric|between:-90,90',
                'pickup_longitude' => 'required|numeric|between:-180,180',
                'pickup_address' => 'required|string',
                'scheduled_at' => 'required|date|after:now'
            ], $messages);

            
            $ticketRequest = TicketRequest::where('id', $validated['ticket_request_id'])
                ->where('user_id', auth()->id())
                ->first();

            if (!$ticketRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not authorized to request a taxi for this ticket'
                ], 403);
            }

            $existingRequest = TaxiRequest::where('booking_id', $validated['ticket_request_id'])
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'A taxi request already exists for this ticket'
                ], 400);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        $ticketRequest = TicketRequest::with(['flight.departureAirport'])->findOrFail($validated['ticket_request_id']);
        $flight = $ticketRequest->flight;
        $airport = $flight->departureAirport;

        if (!$airport) {
            return response()->json([
                'status' => false,
                'message' => 'Airport information not found for this flight.'
            ], 400);
        }

        
        $distance = $this->calculateDistance(
            $validated['pickup_latitude'],
            $validated['pickup_longitude'],
            $airport->latitude,
            $airport->longitude
        );

       
        $taxiRequest = TaxiRequest::create([
            'user_id' => auth()->id(),
            'booking_id' => $ticketRequest->id,
            'booking_type' => 'ticket',
            'pickup_latitude' => $validated['pickup_latitude'],
            'pickup_longitude' => $validated['pickup_longitude'],
            'pickup_address' => $validated['pickup_address'],
            'destination_latitude' => $airport->latitude,
            'destination_longitude' => $airport->longitude,
            'destination_address' => $airport->name . ' Airport, ' . $airport->city,
            'distance_km' => $distance,
            'price' => $this->calculatePrice($distance),
            'scheduled_at' => $validated['scheduled_at']
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Taxi request created successfully.',
            'taxi_request' => $taxiRequest->load('user')
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $r = 6371; 
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return round($r * $c, 2);
    }

    private function calculatePrice($distance)
    {
        $basePrice = 5; 
        $pricePerKm = 0.5; 
        return round($basePrice + ($distance * $pricePerKm), 2);
    }

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

    public function acceptRequest(Request $request, $requestId)
    {
      
        $user = auth()->user();
        $driver = TaxiDriver::where('user_id', $user->id)->first();

        if (!$driver) {
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

        $taxiRequest->driver_id = $driver->id;
        $taxiRequest->status = 'accepted';
        $taxiRequest->save();

        return response()->json([
            'status' => true,
            'message' => 'Request accepted successfully',
            'taxi_request' => $taxiRequest
        ]);
    }

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
            $driver = TaxiDriver::where('user_id', $user->id)->first();

            if (!$driver) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only taxi drivers can update location'
                ], 403);
            }

            $driver->current_latitude = $request->latitude;
            $driver->current_longitude = $request->longitude;
            $driver->save();

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

    public function completeTrip(Request $request, $requestId)
    {
        
        $user = auth()->user();
        $driver = TaxiDriver::where('user_id', $user->id)->first();

        if (!$driver) {
            return response()->json([
                'status' => false,
                'message' => 'Only taxi drivers can complete trips'
            ], 403);
        }

        $taxiRequest = TaxiRequest::findOrFail($requestId);

        if ($taxiRequest->driver_id !== $driver->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only complete your own trips'
            ], 403);
        }

        if ($taxiRequest->status !== 'accepted') {
            return response()->json([
                'status' => false,
                'message' => 'This trip cannot be completed'
            ], 400);
        }

        $taxiRequest->status = 'completed';
        $taxiRequest->completed_at = now();
        $taxiRequest->save();

        return response()->json([
            'status' => true,
            'message' => 'Trip completed successfully',
            'taxi_request' => $taxiRequest
        ]);
    }

    public function getAllDrivers()
    {
        $drivers = TaxiDriver::with(['user', 'completedTrips'])
            ->withCount(['trips as total_trips', 'completedTrips as completed_trips'])
            ->get()
            ->map(function ($driver) {
                $avgRating = $driver->ratings()->avg('star') ?? 0;
                return [
                    'id' => $driver->id,
                    'user' => $driver->user,
                    'status' => $driver->status,
                    'current_latitude' => $driver->current_latitude,
                    'current_longitude' => $driver->current_longitude,
                    'total_trips' => $driver->total_trips,
                    'completed_trips' => $driver->completed_trips,
                    'average_rating' => round($avgRating, 1),
                    'created_at' => $driver->created_at,
                    'updated_at' => $driver->updated_at
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Drivers fetched successfully',
            'drivers' => $drivers
        ]);
    }

    public function getDriverDetails($id)
    {
        $driver = TaxiDriver::with(['user', 'completedTrips', 'trips'])
            ->withCount(['trips as total_trips', 'completedTrips as completed_trips'])
            ->findOrFail($id);

        
        $recentTrips = $driver->trips()
            ->with(['user', 'rating'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($trip) {
                return [
                    'id' => $trip->id,
                    'user' => $trip->user,
                    'pickup_address' => $trip->pickup_address,
                    'destination_address' => $trip->destination_address,
                    'status' => $trip->status,
                    'price' => $trip->price,
                    'distance_km' => $trip->distance_km,
                    'scheduled_at' => $trip->scheduled_at,
                    'completed_at' => $trip->completed_at,
                    'rating' => $trip->rating ? [
                        'star' => $trip->rating->star,
                        'comment' => $trip->rating->comment
                    ] : null
                ];
            });

        
        $avgRating = $driver->ratings()->avg('star') ?? 0;
        $totalEarnings = $driver->completedTrips()->sum('price');
        $totalDistance = $driver->completedTrips()->sum('distance_km');

        return response()->json([
            'status' => true,
            'message' => 'Driver details fetched successfully',
            'driver' => [
                'id' => $driver->id,
                'user' => $driver->user,
                'status' => $driver->status,
                'current_latitude' => $driver->current_latitude,
                'current_longitude' => $driver->current_longitude,
                'statistics' => [
                    'total_trips' => $driver->total_trips,
                    'completed_trips' => $driver->completed_trips,
                    'average_rating' => round($avgRating, 1),
                    'total_earnings' => round($totalEarnings, 2),
                    'total_distance' => round($totalDistance, 1)
                ],
                'recent_trips' => $recentTrips,
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at
            ]
        ]);
    }

    public function addDriver(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'phone' => 'required|string|unique:users,phone',
                'password' => 'required|string|min:8',
                'national_id' => 'required|string|unique:taxi_drivers,national_id',
                'car_model' => 'required|string',
                'car_plate_number' => 'required|string|unique:taxi_drivers,car_plate_number',
                'license_number' => 'required|string|unique:taxi_drivers,license_number',
                'address' => 'required|string',
                'birth_date' => 'required|date|before:today'
            ]);

            DB::beginTransaction();

     
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role' => 'driver'
            ]);
            $driver = TaxiDriver::create([
                'user_id' => $user->id,
                'national_id' => $validated['national_id'],
                'car_model' => $validated['car_model'],
                'car_plate_number' => $validated['car_plate_number'],
                'license_number' => $validated['license_number'],
                'address' => $validated['address'],
                'birth_date' => $validated['birth_date'],
                'status' => 'offline'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Driver added successfully',
                'driver' => $driver->load('user')
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to add driver',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateDriver(Request $request, $id)
    {
        $driver = TaxiDriver::findOrFail($id);
        $user = $driver->user;

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:120',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'car_model' => 'sometimes|string',
            'car_plate_number' => 'sometimes|string|unique:taxi_drivers,car_plate_number,' . $id,
            'license_number' => 'sometimes|string|unique:taxi_drivers,license_number,' . $id,
            'address' => 'sometimes|string',
            'status' => 'sometimes|in:available,busy,offline',
            'car_type' => 'sometimes|in:sedan,suv,van',
            'car_year' => 'sometimes|integer|min:2000',
            'car_color' => 'sometimes|string',
            'license_expiry' => 'sometimes|date|after:today',
            'insurance_provider' => 'nullable|string',
            'insurance_policy_number' => 'nullable|string',
            'insurance_expiry' => 'nullable|date|after:today',
            'bank_name' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'bank_iban' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

           
            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('email')) $user->email = $request->email;
            if ($request->has('phone')) $user->phone = $request->phone;
            if ($request->has('address')) $user->address = $request->address;
            $user->save();

           
            $driver->fill($request->only([
                'car_model', 'car_plate_number', 'license_number', 'status',
                'car_type', 'car_year', 'car_color', 'license_expiry',
                'insurance_provider', 'insurance_policy_number', 'insurance_expiry',
                'bank_name', 'bank_account', 'bank_iban'
            ]));
            $driver->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Driver updated successfully',
                'driver' => $driver->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error updating driver: ' . $e->getMessage()
            ], 500);
        }
    }

 
    public function deleteDriver($id)
    {
        try {
            $driver = TaxiDriver::with('trips')->findOrFail($id);
            $hasActiveTrips = $driver->trips()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();

            if ($hasActiveTrips) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete driver with active trips'
                ], 400);
            }

            $userId = $driver->user_id;
            DB::beginTransaction();
            $driver->delete();
            User::where('id', $userId)->delete();
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Driver deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error deleting driver: ' . $e->getMessage()
            ], 500);
        }
    }

  
    public function getAllRequests(Request $request)
    {
        $query = TaxiRequest::with(['user', 'driver.user'])
            ->when($request->status, function($q) use ($request) {
                return $q->where('status', $request->status);
            })
            ->when($request->date, function($q) use ($request) {
                return $q->whereDate('created_at', $request->date);
            })
            ->when($request->driver_id, function($q) use ($request) {
                return $q->where('driver_id', $request->driver_id);
            })
            ->when($request->user_id, function($q) use ($request) {
                return $q->where('user_id', $request->user_id);
            });

        $requests = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'status' => true,
            'requests' => $requests
        ]);
    }

    public function getStatistics()
    {
        try {
            
            $totalDrivers = TaxiDriver::count();
            $activeDrivers = TaxiDriver::where('status', 'available')->count();
            $busyDrivers = TaxiDriver::where('status', 'busy')->count();
            $offlineDrivers = TaxiDriver::where('status', 'offline')->count();

       
            $totalTrips = TaxiRequest::count();
            $completedTrips = TaxiRequest::where('status', 'completed')->count();
            $cancelledTrips = TaxiRequest::where('status', 'cancelled')->count();
            $activeTrips = TaxiRequest::whereIn('status', ['pending', 'accepted'])->count();

          
            $totalRevenue = TaxiRequest::where('status', 'completed')->sum('price');
            $averageTripPrice = TaxiRequest::where('status', 'completed')->avg('price') ?? 0;
            $totalDistance = TaxiRequest::where('status', 'completed')->sum('distance_km');

          
            $averageRating = DB::table('ratings')
                ->join('taxi_requests', function ($join) {
                    $join->on('ratings.rateable_id', '=', 'taxi_requests.id')
                        ->where('ratings.rateable_type', '=', TaxiRequest::class);
                })
                ->avg('star') ?? 0;

            
            $todayTrips = TaxiRequest::whereDate('created_at', today())->count();
            $todayRevenue = TaxiRequest::whereDate('created_at', today())
                ->where('status', 'completed')
                ->sum('price');

           
            $topDrivers = TaxiDriver::withCount(['completedTrips'])
                ->with('user')
                ->orderBy('completed_trips_count', 'desc')
                ->take(5)
                ->get()
                ->map(function ($driver) {
                    return [
                        'id' => $driver->id,
                        'name' => $driver->user->name,
                        'completed_trips' => $driver->completed_trips_count,
                        'status' => $driver->status
                    ];
                });

            return response()->json([
                'status' => true,
                'message' => 'Statistics fetched successfully',
                'data' => [
                    'drivers' => [
                        'total' => $totalDrivers,
                        'active' => $activeDrivers,
                        'busy' => $busyDrivers,
                        'offline' => $offlineDrivers,
                        'top_performers' => $topDrivers
                    ],
                    'trips' => [
                        'total' => $totalTrips,
                        'completed' => $completedTrips,
                        'cancelled' => $cancelledTrips,
                        'active' => $activeTrips,
                        'today' => $todayTrips
                    ],
                    'revenue' => [
                        'total' => round($totalRevenue, 2),
                        'today' => round($todayRevenue, 2),
                        'average_trip' => round($averageTripPrice, 2)
                    ],
                    'performance' => [
                        'total_distance' => round($totalDistance, 1),
                        'average_rating' => round($averageRating, 1)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error fetching statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getNearbyDrivers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $radius = $request->radius ; 

        $drivers = $this->findNearestDrivers(
            $request->latitude,
            $request->longitude,
            $radius
        );

        return response()->json([
            'status' => true,
            'drivers' => $drivers
        ]);
    }

    public function getUserActiveRequest()
    {
        $activeRequest = TaxiRequest::with(['driver.user'])
            ->where('user_id', auth()->id())
            ->whereIn('status', ['pending', 'accepted', 'in_progress'])
            ->latest()
            ->first();

        return response()->json([
            'status' => true,
            'active_request' => $activeRequest
        ]);
    }

    public function getDriverTrips()
    {
        $driver = TaxiDriver::where('user_id', auth()->id())->firstOrFail();
        $trips = TaxiRequest::where('driver_id', $driver->id)->get();
        if ($trips->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'you do not have any trips',
                'data' => []
            ]);
        }
        return response()->json([
            'status' => true,
            'data' => $trips
        ]);
    }

    public function rateTrip(Request $request, TaxiRequest $taxi_request)
    {

        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:500'
        ]);

    
        if ($taxi_request->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'You are not authorized to rate this trip'
            ], 403);
        }


        if ($taxi_request->status !== 'completed') {
            return response()->json([
                'status' => false,
                'message' => 'You can only rate completed trips'
            ], 400);
        }


        if ($taxi_request->rating()->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You have already rated this trip'
            ], 400);
        }

    
        $rating = new Rating([
            'user_id' => auth()->id(),
            'star' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'rateable_type' => TaxiRequest::class,
            'rateable_id' => $taxi_request->id
        ]);

        $rating->save();

        return response()->json([
            'status' => true,
            'message' => 'Trip rated successfully',
            'data' => $rating
        ]);
    }

    /**
     * إرجاع جميع الطلبات الخاصة بالسائق الحالي من جدول TaxiRequest بدون أي معالجة إضافية
     */
    public function getDriverRequests()
    {
        $driver = TaxiDriver::where('user_id', auth()->id())->firstOrFail();
        $requests = TaxiRequest::where('driver_id', $driver->id)->get();
        if ($requests->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'you do not have any requests',
                'data' => []
            ]);
        }
        return response()->json([
            'status' => true,
            'data' => $requests
        ]);
    }

   
    public function getDriverIncomingRequests()
    {
        $driver = TaxiDriver::where('user_id', auth()->id())->firstOrFail();
        $requests = TaxiRequest::where('driver_id', $driver->id)
            ->whereIn('status', ['pending', 'accepted', 'in_progress'])
            ->get();
            return response()->json([
            'status' => true,
            'data' => $requests
        ]);
    }

   
} 