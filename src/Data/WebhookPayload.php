<?php

namespace Zakhir\LaravelZakhir\Data;

use Zakhir\LaravelZakhir\Enums\PaymentStatus;

class WebhookPayload
{
    public function __construct(
        public readonly string $id,
        public readonly string $referenceId,
        public readonly PaymentStatus $status,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) data_get($data, 'id', ''),
            referenceId: (string) data_get($data, 'referenceId', ''),
            status: PaymentStatus::fromString((string) data_get($data, 'status', '')),
            raw: $data,
        );
    }
}
