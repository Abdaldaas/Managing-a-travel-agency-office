<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\HajController;
use App\Http\Controllers\VisaController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\TaxiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;

// Public Routes
Route::get('/weather', [WeatherController::class, 'getWeather']);
Route::post('/register', [UserController::class, 'registeruser']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/driver/login', [AuthController::class, 'driverLogin']);
Route::post('/admin/login', [AdminController::class, 'adminLogin']);

Route::get('/payment/success', [UserController::class, 'handlePaymentSuccess'])->name('payment.success');
Route::get('/payment/cancel', [UserController::class, 'handlePaymentCancel'])->name('payment.cancel');

// Authenticated User Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Profile & Auth
    Route::post('/logout', [UserController::class, 'logout']);

    
    Route::get('/tickets_shows', [UserController::class, 'get_tickets']);
    // User Requests
    Route::post('/visa/request', [UserController::class, 'requestVisa']);
    Route::post('/ticket/request', [UserController::class, 'requestTicket']);
    Route::post('/passport/request', [UserController::class, 'requestPassport']);
    Route::post('/haj/request', [UserController::class, 'requestHaj']);
    Route::get('/requests/status', [UserController::class, 'getRequestsStatus']);
    
    //
    Route::get('/haj', [HajController::class, 'getAllHajTrips']);
    Route::get('/flight', [FlightController::class, 'getAllFlights']);
    Route::get('/visa/showall', [VisaController::class, 'getAllVisas']);
    Route::get('/hotels', [HotelController::class, 'getAllHotels']);
    // User Notifications
    Route::get('/notifications', [UserController::class, 'getNotifications']);
    Route::patch('/notifications/{id}/mark-as-read', [UserController::class, 'markNotificationAsRead']);
    Route::delete('/notifications/{id}', [UserController::class, 'deleteNotification']);
    
    // User Bookings
    Route::get('/bookings', [UserController::class, 'getBookings']);
    Route::delete('/bookings/{id}', [UserController::class, 'cancelBooking']);
    Route::post('/hotel_request', [HotelController::class, 'requestHotel']);
    
    // User Taxi
    Route::post('/taxi/request', [TaxiController::class, 'requestTaxi']);
    Route::get('/taxi/requests', [TaxiController::class, 'getUserRequests']);
    Route::delete('/taxi/requests/{id}', [TaxiController::class, 'cancelRequest']);
    Route::get('/taxi/drivers/nearby', [TaxiController::class, 'getNearbyDrivers']);
    Route::get('/taxi/request/active', [TaxiController::class, 'getUserActiveRequest']);
    Route::post('rate/{taxi_request}', [TaxiController::class, 'rateTrip']);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // User Management
    Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
    Route::get('/admin/users/{id}', [AdminController::class, 'getUserDetails']);
    
    // Booking Management
    Route::get('/admin/bookings', [AdminController::class, 'viewAllBookings']);
    Route::get('/admin/bookings/{id}', [AdminController::class, 'getBookingDetails']);
    Route::put('/admin/bookings/{id}/status', [AdminController::class, 'updateBookingStatus']);
    
    // Visa Management
    Route::get('/admin/visa-bookings', [AdminController::class, 'getAllVisaBookings']);
    Route::post('/admin/visa-booking/update', [AdminController::class, 'updateVisaBooking']);
    Route::post('/admin/visa', [VisaController::class, 'addVisa']);
    Route::put('/admin/visa/{id}', [VisaController::class, 'updateVisa']);
    Route::delete('/admin/visa/{id}', [VisaController::class, 'deleteVisa']);
    Route::get('/admin/visa', [VisaController::class, 'getAllVisas']);
    Route::get('/admin/visa/{id}', [VisaController::class, 'getVisaById']);
    
    // Passport Management
    Route::get('/admin/passport-requests', [AdminController::class, 'getAllPassportRequests']);
    Route::post('/admin/passport-request/handle', [AdminController::class, 'handlePassportRequest']);
    Route::get('/passports/{id}', [AdminController::class, 'getPassportDetails']);
    
    // Ticket Management
    Route::get('/admin/ticket-requests', [AdminController::class, 'getAllTicketRequests']);
    Route::post('/admin/ticket-request/handle', [AdminController::class, 'handleTicketRequest']);
    
    // Haj Management
    Route::get('/admin/haj-bookings', [AdminController::class, 'getAllHajBookings']);
    Route::post('/admin/haj-booking/handle', [AdminController::class, 'handleHajBookingRequest']);
    Route::post('/admin/haj', [HajController::class, 'addHajTrip']);
    Route::put('/admin/haj/{id}', [HajController::class, 'updateHajTrip']);
    Route::delete('/admin/haj/{id}', [HajController::class, 'deleteHajTrip']);
    Route::get('/admin/haj', [HajController::class, 'getAllHajTrips']);
    Route::get('/admin/haj/{id}', [HajController::class, 'getHajTripById']);
    
    // Flight Management
    Route::post('/admin/flight', [FlightController::class, 'addFlight']);
    Route::put('/admin/flight/{id}', [FlightController::class, 'updateFlight']);
    Route::delete('/admin/flight/{id}', [FlightController::class, 'deleteFlight']);
    Route::get('/admin/flight', [FlightController::class, 'getAllFlights']);
    Route::get('/admin/flight/{id}', [FlightController::class, 'getFlightById']);
    Route::get('/airports', [FlightController::class, 'getAirports']);
        Route::get('/flight', action:  [FlightController::class, 'getAllFlights']);
    // Reports
    Route::get('/admin/reports/bookings', [AdminController::class, 'getBookingsReport']);
    Route::get('/admin/reports/revenue', [AdminController::class, 'getRevenueReport']);
    
    // Admin Notifications
    Route::get('/admin/notifications', [AdminController::class, 'getNotifications']);
    Route::patch('/admin/notifications/{id}/mark-as-read', [AdminController::class, 'markNotificationAsRead']);
    Route::delete('/admin/notifications/{id}', [AdminController::class, 'deleteNotification']);
    // Hotel Management
    Route::post('/admin/hotel', [HotelController::class, 'addHotel']);
    Route::delete('/admin/hotel/{id}', [HotelController::class, 'deleteHotel']);
    Route::post('/admin/hotel-request/handle', [AdminController::class, 'handleHotelRequest']);
    Route::get('/admin/hotel-requests', [AdminController::class, 'getAllHotelRequests']);
});

// Driver Routes
Route::middleware(['auth:sanctum', 'taxi.driver'])->group(function () {
    Route::post('/driver/logout', [AuthController::class, 'logout']);
    Route::post('/taxi/location', [TaxiController::class, 'updateLocation']);
    Route::post('/taxi/requests/{id}/accept', [TaxiController::class, 'acceptRequest']);
    Route::post('/taxi/requests/{id}/complete', [TaxiController::class, 'completeTrip']);
    Route::get('/taxi/active-request', [TaxiController::class, 'getActiveRequest']);
    Route::post('/taxi/status', [TaxiController::class, 'updateStatus']);
    Route::get('/taxi/trips', [TaxiController::class, 'getDriverTrips']);
    Route::get('/driver/requests', [TaxiController::class, 'getDriverRequests']);
    Route::get('/driver/incoming-requests', [TaxiController::class, 'getDriverIncomingRequests']);
});

// Admin Taxi Management Routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/taxi')->group(function () {
    Route::get('/drivers', [TaxiController::class, 'getAllDrivers']);
    Route::get('/drivers/{id}', [TaxiController::class, 'getDriverDetails']);
    Route::post('/drivers', [TaxiController::class, 'addDriver']);
    Route::put('/drivers/{id}', [TaxiController::class, 'updateDriver']);
    Route::delete('/drivers/{id}', [TaxiController::class, 'deleteDriver']);
    Route::get('/requests', [TaxiController::class, 'getAllRequests']);
    Route::get('/drivers/{id}/trips', [TaxiController::class, 'getDriverTrips']);
    Route::get('/statistics', [TaxiController::class, 'getStatistics']);
});

    

  
   
