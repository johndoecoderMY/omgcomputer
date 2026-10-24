<?php

use App\Http\Middleware\EnsureNotCustomer;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureWorkspacePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware' => ['web', 'auth']])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/meta/whatsapp',
            'webhooks/chip/collect/*',
            'webhooks/chip/shop/*',
        ]);

        $middleware->alias([
            'password.changed' => EnsurePasswordChanged::class,
            'not-customer' => EnsureNotCustomer::class,
            'workspace.access' => EnsureWorkspacePermission::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
