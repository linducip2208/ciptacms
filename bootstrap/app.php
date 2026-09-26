<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['permission'=>App\Http\Middleware\CheckPermission::class,'tenant'=>App\Http\Middleware\ResolveTenant::class,'installed'=>App\Http\Middleware\CheckInstalled::class,'pair'=>App\Http\Middleware\RequirePair::class]);
        $middleware->appendToGroup('web', [App\Http\Middleware\ResolveTenant::class]);
        $middleware->appendToGroup('api', [App\Http\Middleware\ResolveTenant::class]);

        // Licence pairing gate. Always bypassed in the `local` and `testing`
        // environments (see RequirePair::shouldBypass) so the suite and local
        // development are never locked; it bites only on a real deployment.
        $middleware->appendToGroup('web', [App\Http\Middleware\RequirePair::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
