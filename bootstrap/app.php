<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RemoveStaleViteHotFile;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->replace(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, VerifyCsrfToken::class);

        $middleware->web(prepend: [
            RemoveStaleViteHotFile::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
