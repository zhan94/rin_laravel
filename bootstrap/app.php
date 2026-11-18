<?php

declare(strict_types=1);

use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureCurrentStaffAccount;
use App\Http\Middleware\EnsureRoleAccess;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectLoopbackHost;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetRequestedLocale;
use App\Models\User;
use App\Support\Api\ApiResponseFactory;
use App\Support\Auth\StaffAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->append(SecurityHeaders::class);
        $middleware->statefulApi();

        $middleware->web(append: [
            RedirectLoopbackHost::class,
            EnsureCurrentStaffAccount::class,
            SetRequestedLocale::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            SetRequestedLocale::class,
        ]);

        $middleware->alias([
            'role' => EnsureRoleAccess::class,
            'staff' => EnsureCurrentStaffAccount::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->render(function (ApiException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                code: $exception->errorCode(),
                message: $exception->getMessage(),
                status: $exception->statusCode(),
                details: $exception->details(),
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                code: 'unauthenticated',
                message: 'Unauthenticated.',
                status: 401,
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $message = trim($exception->getMessage());

            return ApiResponseFactory::error(
                code: 'forbidden',
                message: $message !== '' ? $message : 'Forbidden.',
                status: 403,
            );
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            $status = $exception->getStatusCode();
            if ($status === 403) {
                $user = $request->user();
                StaffAudit::record('staff.authorization.denied', $user instanceof User ? $user->id : null);
            }

            if (! $request->is('api/*')) {
                return null;
            }

            $code = match ($status) {
                403 => 'forbidden',
                404 => 'not_found',
                419 => 'csrf_mismatch',
                423 => 'password_confirmation_required',
                429 => 'too_many_requests',
                default => 'http_error',
            };

            return ApiResponseFactory::error(
                code: $code,
                message: $status === 403 ? 'Access denied.' : (\Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? 'Request could not be completed.'),
                status: $status,
            )->withHeaders($exception->getHeaders());
        });

        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::validation($exception->errors());
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($exception);

            return ApiResponseFactory::error(
                code: 'internal_error',
                message: 'Unexpected server error.',
                status: 500,
            );
        });
    })->create();
