<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait ApiResponder
{
    protected function successResponse(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function errorResponse(string $message, int $status, string $code = null, array $errors = [], array $meta = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($code) {
            $response['code'] = $code;
        }

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }

    protected function validationError(array $errors, string $message = 'Validation error'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            422,
            'VALIDATION_ERROR',
            $errors
        );
    }

    protected function notFoundError(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            404,
            'NOT_FOUND'
        );
    }

    protected function unauthorizedError(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            401,
            'UNAUTHENTICATED'
        );
    }

    protected function forbiddenError(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            403,
            'FORBIDDEN'
        );
    }

    protected function conflictError(string $message, string $code = 'CONFLICT'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            409,
            $code
        );
    }

    protected function serverError(string $message = 'Server error'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            500,
            'SERVER_ERROR'
        );
    }

    protected function rateLimitError(int $retryAfter, string $message = 'Too many requests'): JsonResponse
    {
        return $this->errorResponse(
            $message,
            429,
            'RATE_LIMIT_EXCEEDED',
            [],
            [
                'retry_after' => $retryAfter,
            ]
        );
    }

    protected function handleException(\Throwable $exception): JsonResponse
    {
        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'Request failed.';

            return match ($status) {
                401 => $this->unauthorizedError($message),
                403 => $this->forbiddenError($message),
                404 => $this->notFoundError($message),
                409 => $this->conflictError($message),
                422 => $this->validationError([$message]),
                429 => $this->rateLimitError(30, $message),
                default => $this->errorResponse($message, $status),
            };
        }

        return $this->serverError($exception->getMessage());
    }
}
