<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('services.shop.maintenance', false)) {
            return response()->view('errors.shop-maintenance', [], 503);
        }

        return $next($request);
    }
}
