<?php

namespace Zakhir\LaravelZakhir\Support;

use Zakhir\LaravelZakhir\Models\ZakhirLog;

class ZakhirLogger
{
    public function __construct(private readonly bool $enabled) {}

    public function logOutgoing(
        string $method,
        string $url,
        array $requestBody,
        int $statusCode,
        array $responseBody,
        int $durationMs,
    ): void {
        if (! $this->enabled) {
            return;
        }

        try {
            ZakhirLog::create([
                'direction'     => 'outgoing',
                'method'        => strtoupper($method),
                'url'           => $url,
                'status_code'   => $statusCode,
                'request_body'  => $requestBody,
                'response_body' => $responseBody,
                'duration_ms'   => $durationMs,
            ]);
        } catch (\Throwable) {
            // Never let logging failures break the payment flow.
        }
    }

    public function logIncoming(
        string $method,
        string $url,
        string $ip,
        array $requestBody,
        int $statusCode,
        int $durationMs,
    ): void {
        if (! $this->enabled) {
            return;
        }

        try {
            ZakhirLog::create([
                'direction'    => 'incoming',
                'method'       => strtoupper($method),
                'url'          => $url,
                'ip'           => $ip,
                'status_code'  => $statusCode,
                'request_body' => $requestBody,
                'duration_ms'  => $durationMs,
            ]);
        } catch (\Throwable) {
            // Never let logging failures break the payment flow.
        }
    }
}
