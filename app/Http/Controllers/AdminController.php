<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Passport;
use App\Models\RejectionReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
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

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'البريد الإلكتروني غير مسجل'
            ], 404);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => false,
                'message' => 'كلمة المرور غير صحيحة'
            ], 401);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'admin' => $admin,
            'token' => $token
        ], 200);
    }

    public function handlePassportRequest(Request $request)
    {
        if (!auth()->user()->tokenCan('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بهذه العملية'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'passport_id' => 'required|exists:passports,id',
            'status' => 'required|in:approved,rejected',
            'rejection_reason_id' => 'required_if:status,rejected|exists:rejection_reasons,id',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $passport = Passport::with('user')->findOrFail($request->passport_id);
            
            if ($passport->status !== 'pending') {
                return response()->json([
                    'status' => false,
                    'message' => 'لا يمكن تعديل حالة جواز السفر بعد معالجته'
                ], 400);
            }
            
            $passport->status = $request->status;
        
        if ($request->status === 'rejected') {
            $rejectionReason = RejectionReason::findOrFail($request->rejection_reason_id);
            $passport->rejection_reason = $rejectionReason->reason;
        }

        $passport->save();

        // إرسال إشعار للمستخدم
        $passport->user->notify(new \App\Notifications\PassportStatusUpdated($passport));

        return response()->json([
            'status' => true,
            'message' => $request->status === 'approved' ? 'Passport request approved' : 'Passport request rejected',
            'passport' => $passport
        ], 200);
    }

    public function manageFlight(Request $request)
    {
        if (!auth()->user()->tokenCan('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized operation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'flight_number' => 'required|string',
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

        $flight = new Flight();
        $flight->fill($request->all());
        $flight->save();

        return response()->json([
            'status' => true,
            'message' => 'Flight added successfully',
            'flight' => $flight
        ], 201);
    }

    public function manageVisaRequest(Request $request)
    {
        if (!auth()->user()->tokenCan('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized operation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'visa_id' => 'required|exists:visas,id',
            'status' => 'required|in:approved,rejected',
            'rejection_reason_id' => 'required_if:status,rejected|exists:rejection_reasons,id',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $visa = Visa::with('user')->findOrFail($request->visa_id);
        
        if ($visa->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Visa request cannot be modified after processing'
            ], 400);
        }
        
        $visa->status = $request->status;
        $visa->admin_notes = $request->admin_notes;

        if ($request->status === 'rejected') {
            $rejectionReason = RejectionReason::findOrFail($request->rejection_reason_id);
            $visa->rejection_reason = $rejectionReason->reason;
        }

        $visa->save();

        $visa->user->notify(new \App\Notifications\VisaStatusUpdated($visa));

        return response()->json([
            'status' => true,
            'message' => $request->status === 'approved' ? 'Visa request approved' : 'Visa request rejected',
            'visa' => $visa
        ], 200);
    }

    public function manageHajRequest(Request $request)
    {
        if (!auth()->user()->tokenCan('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized operation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'haj_id' => 'required|exists:haj,id',
            'status' => 'required|in:approved,rejected',
            'rejection_reason_id' => 'required_if:status,rejected|exists:rejection_reasons,id',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $haj = Haj::with('user')->findOrFail($request->haj_id);
        
        if ($haj->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Haj request cannot be modified after processing'
            ], 400);
        }
        
        $haj->status = $request->status;
        $haj->admin_notes = $request->admin_notes;

        if ($request->status === 'rejected') {
            $rejectionReason = RejectionReason::findOrFail($request->rejection_reason_id);
            $haj->rejection_reason = $rejectionReason->reason;
        }

        $haj->save();

        $haj->user->notify(new \App\Notifications\HajStatusUpdated($haj));

        return response()->json([
            'status' => true,
            'message' => $request->status === 'approved' ? 'Haj request approved' : 'Haj request rejected',
            'haj' => $haj
        ], 200);
    }

    public function manageTicketRequest(Request $request)
    {
        if (!auth()->user()->tokenCan('admin')) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized operation'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'ticket_request_id' => 'required|exists:ticket_requests,id',
            'status' => 'required|in:approved,rejected',
            'rejection_reason_id' => 'required_if:status,rejected|exists:rejection_reasons,id',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid data',
                'errors' => $validator->errors()
            ], 400);
        }

        $ticketRequest = TicketRequest::with(['user', 'flight'])->findOrFail($request->ticket_request_id);
        
        if ($ticketRequest->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Ticket request cannot be modified after processing'
            ], 400);
        }
        
        if ($request->status === 'approved' && $ticketRequest->flight->available_seats < 1) {
            return response()->json([
                'status' => false,
                'message' => 'No available seats on this flight'
            ], 400);
        }

        $ticketRequest->status = $request->status;
        $ticketRequest->admin_notes = $request->admin_notes;

        if ($request->status === 'rejected') {
            $rejectionReason = RejectionReason::findOrFail($request->rejection_reason_id);
            $ticketRequest->rejection_reason = $rejectionReason->reason;
        } else {
            $ticketRequest->flight->available_seats--;
            $ticketRequest->flight->save();
        }

        $ticketRequest->save();

        $ticketRequest->user->notify(new \App\Notifications\TicketRequestStatusUpdated($ticketRequest));

        return response()->json([
            'status' => true,
            'message' => $request->status === 'approved' ? 'Ticket request approved' : 'Ticket request rejected',
            'ticket_request' => $ticketRequest
        ], 200);
    }
}