<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if ($user && $user->needs_password_change) {
            // Allow change password API calls
            if ($request->is('api/auth/change-password') || $request->is('api/auth/logout')) {
                return $next($request);
            }
            
            // Block all other API calls
            return response()->json([
                'error' => 'Password change required',
                'message' => 'You must change your password before accessing other features.',
                'needs_password_change' => true
            ], 403);
        }

        return $next($request);
    }
}
