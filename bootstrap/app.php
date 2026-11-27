<?php

use App\Http\Middleware\AlihkanJalurLama;
use App\Http\Middleware\CatatKunjunganPublik;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HeaderKeamanan;
use App\Http\Middleware\PastikanAdminAktif;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(prepend: [
            HeaderKeamanan::class,
        ], append: [
            AlihkanJalurLama::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CatatKunjunganPublik::class,
        ]);

        $middleware->alias([
            'admin.aktif' => PastikanAdminAktif::class,
        ]);

        $middleware->redirectGuestsTo('/admin/masuk');
        $middleware->redirectUsersTo('/admin');

        // Proxy tepercaya diatur lewat env TRUSTED_PROXIES saat di belakang Nginx/load balancer.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Halaman galat Inertia berbahasa Indonesia (status asli dipertahankan, bukan soft-404).
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();
            if (! app()->hasDebugModeEnabled() || in_array($status, [403, 404, 419], true)) {
                if (in_array($status, [403, 404, 500, 503], true) && ! $request->expectsJson() && $request->method() === 'GET' && ! str_starts_with((string) $response->headers->get('Content-Type'), 'application/pdf')) {
                    return Inertia::render('galat', ['status' => $status])
                        ->toResponse($request)
                        ->setStatusCode($status);
                }
                if ($status === 419) {
                    return back()->with('galat', 'Sesi telah berakhir. Silakan muat ulang halaman lalu coba lagi.');
                }
            }

            return $response;
        });
    })->create();
