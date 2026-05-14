<?php

namespace Zakhir\LaravelZakhir\Exceptions;

class ZakhirWebhookException extends ZakhirException
{
    public static function missingField(string $field): self
    {
        return new self("Zakhir webhook payload missing required field: {$field}");
    }

    public static function invalidSignature(): self
    {
        return new self('Zakhir webhook signature verification failed.');
    }
}
