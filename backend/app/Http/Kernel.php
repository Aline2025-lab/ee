<?php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;


class Kernel
{
    protected $middlewareGroups = [
        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
         \Illuminate\Http\Middleware\HandleCors::class,
         \App\Http\Middleware\CustomCors::class,
    ];
    protected $routeMiddleware = [
        'role' => \App\Http\Middleware\CheckRole::class,
        'active' => \App\Http\Middleware\CheckUserIsActive::class,
    ];
}
