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

// Public routes
Route::get('/weather', [WeatherController::class, 'getWeather']);
Route::post('/register', [UserController::class, 'registeruser']);
Route::post('/login', [UserController::class, 'login']);

// Driver authentication routes
Route::post('/driver/login', [AuthController::class, 'driverLogin']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/driver/logout', [AuthController::class, 'logout']);
});

// User routes
Route::middleware('auth:sanctum')->group(function () {
    // طلبات التأشيرة
    Route::post('/visa/request', [UserController::class, 'requestVisa']);
    // طلبات التذاكر
    Route::post('/ticket/request', [UserController::class, 'requestTicket']);
    // طلبات جواز السفر
    Route::post('/passport/request', [UserController::class, 'requestPassport']);
    // طلبات الحج
    Route::post('/haj/request', [UserController::class, 'requestHaj']);
    // الإشعارات
    Route::get('/notifications', [UserController::class, 'getNotifications']);
    Route::patch('/notifications/{id}/mark-as-read', [UserController::class, 'markNotificationAsRead']);
    Route::delete('/notifications/{id}', [UserController::class, 'deleteNotification']);
    Route::get('/requests/status', [UserController::class, 'getRequestsStatus']);
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/bookings', [UserController::class, 'getBookings']);
    Route::delete('/bookings/{id}', [UserController::class, 'cancelBooking']);
   
    // Taxi routes
    Route::post('/taxi/request', [TaxiController::class, 'requestTaxi']);
    Route::get('/taxi/requests', [TaxiController::class, 'getUserRequests']);
    Route::delete('/taxi/requests/{id}', [TaxiController::class, 'cancelRequest']);
    Route::get('/taxi/drivers/nearby', [TaxiController::class, 'getNearbyDrivers']);
    Route::get('/taxi/request/active', [TaxiController::class, 'getUserActiveRequest']);
    Route::post('rate/{taxi_request}', [TaxiController::class, 'rateTrip']);
});

// Admin login
Route::post('/admin/login', [AdminController::class, 'adminLogin']);

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // User management
    Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
    Route::get('/admin/users/{id}', [AdminController::class, 'getUserDetails']);
    
    // Booking management
    Route::get('/admin/bookings', [AdminController::class, 'viewAllBookings']);
    Route::get('/admin/bookings/{id}', [AdminController::class, 'getBookingDetails']);
    Route::put('/admin/bookings/{id}/status', [AdminController::class, 'updateBookingStatus']);
    
    // Visa management
    Route::get('/admin/visa-bookings', [AdminController::class, 'getAllVisaBookings']);
    Route::post('/admin/visa-booking/update', [AdminController::class, 'updateVisaBooking']);
    
    // Passport management
    Route::get('/admin/passport-requests', [AdminController::class, 'getAllPassportRequests']);
    Route::post('/admin/passport-request/handle', [AdminController::class, 'handlePassportRequest']);
    
    // Ticket management
    Route::get('/admin/ticket-requests', [AdminController::class, 'getAllTicketRequests']);
    Route::post('/admin/ticket-request/handle', [AdminController::class, 'handleTicketRequest']);
    
    // Haj booking management
    Route::get('/admin/haj-bookings', [AdminController::class, 'getAllHajBookings']);
    Route::post('/admin/haj-booking/handle', [AdminController::class, 'handleHajBookingRequest']);
    
    // Reports
    Route::get('/admin/reports/bookings', [AdminController::class, 'getBookingsReport']);
    Route::get('/admin/reports/revenue', [AdminController::class, 'getRevenueReport']);
    // Haj management
    Route::post('/admin/haj', [HajController::class, 'addHajTrip']);
    Route::put('/admin/haj/{id}', [HajController::class, 'updateHajTrip']);
    Route::delete('/admin/haj/{id}', [HajController::class, 'deleteHajTrip']);
    Route::get('/admin/haj', [HajController::class, 'getAllHajTrips']);
    Route::get('/admin/haj/{id}', [HajController::class, 'getHajTripById']);
    // Visa management
    Route::post('/admin/visa', [VisaController::class, 'addVisa']);
    Route::put('/admin/visa/{id}', [VisaController::class, 'updateVisa']);
    Route::delete('/admin/visa/{id}', [VisaController::class, 'deleteVisa']);
    Route::get('/admin/visa', [VisaController::class, 'getAllVisas']);
    Route::get('/admin/visa/{id}', [VisaController::class, 'getVisaById']);
    // Flight management
    Route::post('/admin/flight', [FlightController::class, 'addFlight']);
    Route::put('/admin/flight/{id}', [FlightController::class, 'updateFlight']);
    Route::delete('/admin/flight/{id}', [FlightController::class, 'deleteFlight']);
    Route::get('/admin/flight', [FlightController::class, 'getAllFlights']);
    Route::get('/admin/flight/{id}', [FlightController::class, 'getFlightById']);
    // Notification management
    Route::get('/admin/notifications', [AdminController::class, 'getNotifications']);
    Route::patch('/admin/notifications/{id}/mark-as-read', [AdminController::class, 'markNotificationAsRead']);
    Route::delete('/admin/notifications/{id}', [AdminController::class, 'deleteNotification']);
    Route::get('/admin/passports/{id}', [AdminController::class, 'getPassportDetails']);
});

// Taxi routes
Route::middleware('auth:sanctum')->group(function () {
    // User taxi routes
    Route::post('/taxi/request', [TaxiController::class, 'requestTaxi']);
    Route::get('/taxi/requests', [TaxiController::class, 'getUserRequests']);
    Route::delete('/taxi/requests/{id}', [TaxiController::class, 'cancelRequest']);
    Route::get('/taxi/drivers/nearby', [TaxiController::class, 'getNearbyDrivers']);
    Route::get('/taxi/request/active', [TaxiController::class, 'getUserActiveRequest']);
    Route::post('/taxi/requests/{id}/rate', [TaxiController::class, 'rateTrip']);

    // Driver routes
    Route::middleware('taxi.driver')->group(function () {
        Route::post('/taxi/location', [TaxiController::class, 'updateLocation']);
        Route::post('/taxi/requests/{id}/accept', [TaxiController::class, 'acceptRequest']);
        Route::post('/taxi/requests/{id}/complete', [TaxiController::class, 'completeTrip']);
        Route::get('/taxi/active-request', [TaxiController::class, 'getActiveRequest']);
        Route::post('/taxi/status', [TaxiController::class, 'updateStatus']);
        Route::get('/taxi/trips', [TaxiController::class, 'getDriverTrips']);
    });

    // Admin taxi management routes
    Route::middleware('admin')->prefix('admin/taxi')->group(function () {
        // Driver management
        Route::get('/drivers', [TaxiController::class, 'getAllDrivers']);
        Route::get('/drivers/{id}', [TaxiController::class, 'getDriverDetails']);
        Route::post('/drivers', [TaxiController::class, 'addDriver']);
        Route::put('/drivers/{id}', [TaxiController::class, 'updateDriver']);
        Route::delete('/drivers/{id}', [TaxiController::class, 'deleteDriver']);
        
        // Request management
        Route::get('/requests', [TaxiController::class, 'getAllRequests']);
        Route::get('/drivers/{id}/trips', [TaxiController::class, 'getDriverTrips']);
        
        // Statistics
        Route::get('/statistics', [TaxiController::class, 'getStatistics']);
    });
});


