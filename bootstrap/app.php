<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\AdminMidddleware;
use App\Http\Middleware\ManagerMiddleware;
use App\Http\Middleware\ShopperMiddleware;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\BranchMiddleware;
use App\Http\Middleware\LevelMiddleware;
use App\Http\Middleware\SurvayuserMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'superadmin' => SuperAdminMiddleware::class,
            'admin' => AdminMidddleware::class,
         

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
