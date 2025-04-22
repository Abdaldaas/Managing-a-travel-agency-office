<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        if (auth()->user()->role !== 'user') {
            return response()->json([
                'status' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return $next($request);
    }
}