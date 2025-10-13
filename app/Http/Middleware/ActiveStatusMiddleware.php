<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class ActiveStatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() != null) {
            if($request->user()->status != 'Active'){
                Auth::logout();
                return redirect('/login')->withErrors(['error' => 'Your account is currently restricted!']);
            }
            
            return $next($request);
        }

        // if (Auth::check()) {
        //     $request->user()->token()->revoke();
        // }
        
        if ($request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        
        Auth::logout();
        return redirect('/login')->withErrors(['error' => 'Your account is currently restricted!']);
    }
}
