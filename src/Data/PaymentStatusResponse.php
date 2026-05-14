<?php

namespace Zakhir\LaravelZakhir\Data;

class PaymentStatusResponse
{
    public function __construct(
        public readonly string $referenceId,
        public readonly string $status,
        public readonly string $id,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            referenceId: (string) data_get($data, 'referenceId', ''),
            status: strtoupper((string) data_get($data, 'status', '')),
            id: (string) data_get($data, 'id', ''),
            raw: $data,
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }

    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }
}
