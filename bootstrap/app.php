<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\PreventResponseCaching;
use App\Http\Middleware\SetUtf8ContentType;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Set timezone immediately when application boots
date_default_timezone_set('Asia/Manila');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(PreventResponseCaching::class);
        $middleware->append(SetUtf8ContentType::class);

        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);

        $middleware->alias([
            'role.cashier' => \App\Http\Middleware\RoleMiddleware::class.':cashier',
            'role.admin' => \App\Http\Middleware\RoleMiddleware::class.':admin',
            'inventory.write' => \App\Http\Middleware\EnsureInventoryWriteAccess::class,
            'report.access' => \App\Http\Middleware\EnsureReportAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
