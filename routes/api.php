<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

// Public routes
Route::post('/register', [UserController::class, 'registeruser']);
Route::post('/login', [UserController::class, 'login']);

// User routes
Route::middleware('auth:sanctum')->group(function () {
    // Request routes
    Route::post('/passport/request', [UserController::class, 'requestPassport']);
    Route::post('/visa/request', [UserController::class, 'requestVisa']);
    Route::post('/haj/request', [UserController::class, 'requestHaj']);
    Route::post('/ticket/request', [UserController::class, 'requestTicket']);
    
    // Notification routes
    Route::get('/notifications', [UserController::class, 'getNotifications']);
    Route::put('/notifications/{id}/read', [UserController::class, 'markNotificationAsRead']);
    Route::delete('/notifications/{id}', [UserController::class, 'deleteNotification']);
    
    // Payment routes
    Route::get('/payments', [UserController::class, 'getPayments']);
    Route::post('/payments/process', [UserController::class, 'processPayment']);
    
    // Status tracking
    Route::get('/requests/status', [UserController::class, 'getRequestsStatus']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // User management
    Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
    Route::get('/admin/users/{id}', [AdminController::class, 'getUserDetails']);
    Route::put('/admin/users/{id}/status', [AdminController::class, 'updateUserStatus']);
    
    // Notification management
    Route::post('/admin/notifications/send', [AdminController::class, 'sendNotification']);
    Route::get('/admin/notifications/stats', [AdminController::class, 'getNotificationStats']);
    
    // Payment management
    Route::get('/admin/payments', [AdminController::class, 'getAllPayments']);
    Route::get('/admin/payments/stats', [AdminController::class, 'getPaymentStats']);
    
    // Currency management
    Route::get('/admin/currencies', [AdminController::class, 'getCurrencies']);
    Route::post('/admin/currencies/rates', [AdminController::class, 'updateExchangeRates']);
    
    // Reports and statistics
    Route::get('/admin/reports/requests', [AdminController::class, 'getRequestsReport']);
    Route::get('/admin/reports/revenue', [AdminController::class, 'getRevenueReport']);
    Route::get('/admin/reports/users', [AdminController::class, 'getUsersReport']);

    
    // Passport management
    Route::get('/admin/passports', [AdminController::class, 'getPassportRequests']);
    Route::put('/admin/passport/{id}/status', [AdminController::class, 'updatePassportStatus']);
    
    // Visa management
    Route::get('/admin/visas', [AdminController::class, 'getVisaRequests']);
    Route::put('/admin/visa/{id}/status', [AdminController::class, 'updateVisaStatus']);
    
    // Haj management
    Route::get('/admin/haj-requests', [AdminController::class, 'getHajRequests']);
    Route::put('/admin/haj-request/{id}/status', [AdminController::class, 'updateHajStatus']);
    
    // Ticket management
    Route::get('/admin/ticket-requests', [AdminController::class, 'getTicketRequests']);
    Route::put('/admin/ticket-request/{id}/status', [AdminController::class, 'updateTicketStatus']);
});
