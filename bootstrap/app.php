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
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['message' => 'Session expired. Please refresh the page.'], 419);
                }
                return redirect()->back()->withInput()->with('error', 'Session expired. Please refresh the page.');
            }
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Session expired. Please refresh the page.'], 419);
            }
            return redirect()->back()->withInput()->with('error', 'Session expired. Please refresh the page.');
        });
    })->create();
