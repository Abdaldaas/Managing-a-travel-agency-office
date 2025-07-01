<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TaxiDriverMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user() || !auth()->user()->isTaxiDriver()) {
            return response()->json([
                'status' => false,
                'message' => 'This action requires taxi driver privileges'
            ], 403);
        }

        return $next($request);
    }
} 