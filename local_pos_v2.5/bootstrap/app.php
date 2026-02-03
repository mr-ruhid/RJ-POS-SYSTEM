<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // [VACİB] API route-larını burada tanıtmaq lazımdır ki, server onları görsün
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // API və Telegram webhook sorğuları üçün CSRF qorumasını söndürürük
        // Çünki bu sorğularda CSRF token olmur
        $middleware->validateCsrfTokens(except: [
            'api/*',            // Bütün API sorğuları
            'telegram/webhook', // Telegram webhook
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
