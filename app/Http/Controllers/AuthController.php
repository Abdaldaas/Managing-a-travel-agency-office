<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TaxiDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * تسجيل دخول السائق
     */
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
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // البحث عن المستخدم إما بالبريد الإلكتروني أو رقم الهاتف
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
                    'message' => 'بيانات الدخول غير صحيحة'
                ], 401);
            }

            // التحقق من وجود بيانات السائق
            $driver = TaxiDriver::where('user_id', $user->id)->first();
            if (!$driver) {
                return response()->json([
                    'status' => false,
                    'message' => 'لم يتم العثور على بيانات السائق'
                ], 404);
            }

            // التحقق من حالة السائق
            if ($driver->status === 'suspended') {
                return response()->json([
                    'status' => false,
                    'message' => 'تم تعليق حسابك. يرجى التواصل مع الإدارة'
                ], 403);
            }

            // إنشاء رمز التوثيق
            $token = $user->createToken('driver_auth_token')->plainTextToken;

            // تحديث حالة السائق إلى متاح
            $driver->status = 'available';
            $driver->save();

            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
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
                'message' => 'حدث خطأ أثناء تسجيل الدخول',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تسجيل خروج السائق
     */
    public function logout(Request $request)
    {
        try {
            // تحديث حالة السائق إلى غير متصل
            $driver = TaxiDriver::where('user_id', $request->user()->id)->first();
            if ($driver) {
                $driver->status = 'offline';
                $driver->save();
            }

            // حذف رمز التوثيق الحالي
            $request->user()->currentAccessToken()->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'تم تسجيل الخروج بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 