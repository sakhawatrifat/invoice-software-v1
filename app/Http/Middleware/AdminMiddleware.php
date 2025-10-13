<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (Auth::user()->user_type == 'superadmin' || Auth::user()->user_type == 'admin')) {
            if(Auth::user()->status != 'Active'){
                Auth::logout();
                return redirect('/login');
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
        
        return redirect('/login'); // or any unauthorized route
    }
}
