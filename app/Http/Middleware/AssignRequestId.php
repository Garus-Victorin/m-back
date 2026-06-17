<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = trim((string) $request->header('X-Request-Id'));
        $requestId = $this->isValidRequestId($incoming)
            ? $incoming
            : 'req_'.Str::lower((string) Str::uuid());

        $request->attributes->set('request_id', $requestId);
        $request->headers->set('X-Request-Id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    protected function isValidRequestId(string $value): bool
    {
        return $value !== '' && Str::isAscii($value) && strlen($value) <= 100;
    }
}
