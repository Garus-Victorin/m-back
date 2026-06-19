<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureActiveSeller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([App\Providers\EventServiceProvider::class])
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        apiPrefix: "api",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            "seller.active" => EnsureActiveSeller::class,
            "seller.rate_limit" => SellerRateLimitMiddleware::class,
        ]);

        $middleware->appendToGroup("api", [AssignRequestId::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is("api/*"),
        );

        $exceptions->render(function (
            ValidationException $exception,
            Request $request,
        ): ?JsonResponse {
            if (!$request->is("api/*")) {
                return null;
            }

            return response()->json(
                [
                    "success" => false,
                    "message" => $exception->getMessage(),
                    "code" => "VALIDATION_ERROR",
                    "errors" => $exception->errors(),
                    "meta" => [
                        "request_id" => $request->attributes->get("request_id"),
                    ],
                ],
                422,
            );
        });

        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request,
        ): ?JsonResponse {
            if (!$request->is("api/*")) {
                return null;
            }

            return response()->json(
                [
                    "success" => false,
                    "message" => $exception->getMessage() ?: "Unauthenticated.",
                    "code" => "UNAUTHENTICATED",
                    "meta" => [
                        "request_id" => $request->attributes->get("request_id"),
                    ],
                ],
                401,
            );
        });

        $exceptions->render(function (
            AuthorizationException $exception,
            Request $request,
        ): ?JsonResponse {
            if (!$request->is("api/*")) {
                return null;
            }

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        $exception->getMessage() ?:
                        "This action is unauthorized.",
                    "code" => "FORBIDDEN",
                    "meta" => [
                        "request_id" => $request->attributes->get("request_id"),
                    ],
                ],
                403,
            );
        });

        $exceptions->render(function (
            HttpExceptionInterface $exception,
            Request $request,
        ): ?JsonResponse {
            if (!$request->is("api/*")) {
                return null;
            }

            $status = $exception->getStatusCode();

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        $exception->getMessage() !== ""
                            ? $exception->getMessage()
                            : "Request failed.",
                    "code" => match ($status) {
                        401 => "UNAUTHENTICATED",
                        403 => "FORBIDDEN",
                        404 => "NOT_FOUND",
                        409 => "CONFLICT",
                        422 => "UNPROCESSABLE_ENTITY",
                        429 => "RATE_LIMITED",
                        default => "HTTP_ERROR",
                    },
                    "meta" => [
                        "request_id" => $request->attributes->get("request_id"),
                    ],
                ],
                $status,
            );
        });

        $exceptions->render(function (
            \Throwable $exception,
            Request $request,
        ): ?JsonResponse {
            if (!$request->is("api/*")) {
                return null;
            }

            report($exception);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Server error.",
                    "code" => "SERVER_ERROR",
                    "meta" => [
                        "request_id" => $request->attributes->get("request_id"),
                    ],
                ],
                500,
            );
        });
    })
    ->create();
