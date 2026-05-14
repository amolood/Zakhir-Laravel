<?php

namespace Zakhir\LaravelZakhir\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Passthrough middleware — Zakhir webhooks do not use signature verification.
 * Kept for structural consistency; does nothing.
 */
class VerifyZakhirWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
