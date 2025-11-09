<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, return null to send JSON 401 response
        // For web requests, redirect to frontend login
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }
        
        // Redirect to frontend login page
        return env('FRONTEND_URL', 'http://localhost:3000');
    }
}

