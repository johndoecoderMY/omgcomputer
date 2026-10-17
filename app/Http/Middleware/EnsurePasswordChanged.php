<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $allowedRoutes = [
            'password.first-login',
            'password.first-login.update',
            'logout',
        ];

        if ($user?->force_password_change
            && ! in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return redirect()->route('password.first-login');
        }

        return $next($request);
    }
}
