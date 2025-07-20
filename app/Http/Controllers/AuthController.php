<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TaxiDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function driverLogin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required_without:phone|email',
                'phone' => 'required_without:email|string',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }


            $user = User::where('role', 'driver')
                ->where(function($query) use ($request) {
                    if ($request->has('email')) {
                        $query->where('email', $request->email);
                    }
                    if ($request->has('phone')) {
                        $query->orWhere('phone', $request->phone);
                    }
                })
                ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'incorrect credentials'
                ], 401);
            }

            $driver = TaxiDriver::where('user_id', $user->id)->first();
            if (!$driver) {
                return response()->json([
                    'status' => false,
                    'message' => 'user not found'
                ], 404);
            }

       
            if ($driver->status === 'suspended') {
                return response()->json([
                    'status' => false,
                    'message' => 'تم تعليق حسابك. يرجى التواصل مع الإدارة'
                ], 403);
            }

       
            $token = $user->createToken('driver_auth_token')->plainTextToken;

      
            $driver->status = 'available';
            $driver->save();

            return response()->json([
                'status' => true,
                'message' => 'logged in successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone
                    ],
                    'driver' => [
                        'id' => $driver->id,
                        'car_model' => $driver->car_model,
                        'car_plate_number' => $driver->car_plate_number,
                        'status' => $driver->status,
                        'rating' => $driver->rating
                    ],
                    'token' => [
                        'access_token' => $token,
                        'token_type' => 'Bearer'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'error while logging in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
       
            $driver = TaxiDriver::where('user_id', $request->user()->id)->first();
            if ($driver) {
                $driver->status = 'offline';
                $driver->save();
            }

            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'logged out successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'error while logging out',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 