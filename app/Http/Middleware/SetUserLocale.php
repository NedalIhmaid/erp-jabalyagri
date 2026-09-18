<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = auth()->user()) {
            app()->setLocale($user->locale ?? config('app.locale'));
        }

        return $next($request);
    }
}
