<?php

namespace Zakhir\LaravelZakhir\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Zakhir\LaravelZakhir\Exceptions\ZakhirWebhookException;
use Zakhir\LaravelZakhir\Http\ZakhirConfig;

/**
 * Optional middleware — only active when a webhook_secret is configured.
 * Verifies the X-Zakhir-Signature header using HMAC-SHA256.
 */
class VerifyZakhirWebhookSignature
{
    public function __construct(private readonly ZakhirConfig $config) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = $this->config->webhookSecret();

        if ($secret === '') {
            // No secret configured — skip verification
            return $next($request);
        }

        $signature = $request->header('X-Zakhir-Signature', '');

        if ($signature === '') {
            throw ZakhirWebhookException::invalidSignature();
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        $provided = preg_replace('/^sha256=/i', '', $signature);

        if (! hash_equals($expected, strtolower($provided))) {
            throw ZakhirWebhookException::invalidSignature();
        }

        return $next($request);
    }
}
