<?php

namespace Zakhir\LaravelZakhir\Exceptions;

use Illuminate\Http\Client\Response;

class ZakhirApiException extends ZakhirException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly array $responseBody = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json() ?? [];
        $message = data_get($body, 'message')
            ?? data_get($body, 'error')
            ?? "Zakhir API returned HTTP {$response->status()}";

        return new self(
            message: $message,
            statusCode: $response->status(),
            responseBody: $body,
        );
    }
}
