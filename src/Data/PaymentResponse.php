<?php

namespace Zakhir\LaravelZakhir\Data;

class PaymentResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $referenceId,
        public readonly string $status,
        public readonly ?string $paymentToken,
        public readonly ?string $paymentTokenExpiresAt,
        public readonly ?string $checkoutUrl,
        public readonly ?string $mobileAppUrl,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) data_get($data, 'id', ''),
            referenceId: (string) data_get($data, 'referenceId', ''),
            status: strtoupper((string) data_get($data, 'status', 'PENDING')),
            paymentToken: data_get($data, 'paymentToken.value'),
            paymentTokenExpiresAt: data_get($data, 'paymentToken.expiresAt'),
            checkoutUrl: data_get($data, 'checkoutPage.url'),
            mobileAppUrl: data_get($data, 'checkoutPage.mobileAppUrl'),
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
}
