<?php

namespace Zakhir\LaravelZakhir\Exceptions;

use RuntimeException;

class ZakhirException extends RuntimeException
{
    public static function notConfigured(string $field): self
    {
        return new self("Zakhir is not configured: {$field} is missing.");
    }

    public static function disabled(): self
    {
        return new self('Zakhir payment gateway is disabled.');
    }

    public static function invalidResponse(string $context = ''): self
    {
        $message = 'Invalid response from Zakhir API.';

        if ($context !== '') {
            $message .= " Context: {$context}";
        }

        return new self($message);
    }
}
