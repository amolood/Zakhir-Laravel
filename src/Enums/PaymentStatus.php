<?php

namespace Zakhir\LaravelZakhir\Enums;

enum PaymentStatus: string
{
    case Pending   = 'PENDING';
    case Completed = 'COMPLETED';
    case Rejected  = 'REJECTED';
    case Unknown   = 'UNKNOWN';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtoupper($value)) ?? self::Unknown;
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Rejected => true,
            default => false,
        };
    }
}
