<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            require __DIR__ . '/../routes/mobile.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\SetLocale::class,
        \App\Http\Middleware\CheckMobileRoleRedirect::class,
    ]);

    $middleware->alias([
        'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        'setLocale' => \App\Http\Middleware\SetLocale::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'mobile.role.redirect' => \App\Http\Middleware\CheckMobileRoleRedirect::class,
    ]);
})
    //->withMiddleware(function (Middleware $middleware): void {
        //
    //})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
