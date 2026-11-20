<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('public_locale', config('app.locale'));

        app()->setLocale(in_array($locale, ['en', 'ms', 'zh_CN'], true) ? $locale : config('app.locale'));

        return $next($request);
    }
}
