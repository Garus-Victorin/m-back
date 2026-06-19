<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SellerRateLimitMiddleware
{
    protected array $limits = [
        'bootstrap' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'key' => 'seller_bootstrap',
        ],
        'products_read' => [
            'max_attempts' => 120,
            'decay_minutes' => 1,
            'key' => 'seller_products_read',
        ],
        'products_write' => [
            'max_attempts' => 30,
            'decay_minutes' => 1,
            'key' => 'seller_products_write',
        ],
        'orders' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'key' => 'seller_orders',
        ],
        'withdrawals' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
            'key' => 'seller_withdrawals',
        ],
        'uploads' => [
            'max_attempts' => 20,
            'decay_minutes' => 1,
            'key' => 'seller_uploads',
        ],
        'chat_messages' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
            'key' => 'seller_chat_messages',
        ],
    ];

    public function handle(Request $request, Closure $next, string $limitKey): Response
    {
        if (!array_key_exists($limitKey, $this->limits)) {
            return $next($request);
        }

        $limitConfig = $this->limits[$limitKey];

        $key = $this->resolveRequestSignature($request, $limitConfig['key']);

        if (RateLimiter::tooManyAttempts($key, $limitConfig['max_attempts'])) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'meta' => [
                    'retry_after' => $retryAfter,
                    'limit' => $limitConfig['max_attempts'],
                    'window' => $limitConfig['decay_minutes'],
                ],
            ], 429);
        }

        RateLimiter::hit($key, $limitConfig['decay_minutes'] * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $limitConfig['max_attempts'],
            RateLimiter::attempts($key)
        );
    }

    protected function resolveRequestSignature(Request $request, string $keyPrefix): string
    {
        if ($user = $request->user()) {
            return $keyPrefix . '|' . $user->id . '|' . $request->ip();
        }

        return $keyPrefix . '|' . $request->ip();
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }
}
