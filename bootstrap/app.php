<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Meta and Twilio sign webhook POSTs instead of sending a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'whatsapp/webhook',
            'whatsapp/twilio/status',
            'whatsapp/twilio/inbound',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
