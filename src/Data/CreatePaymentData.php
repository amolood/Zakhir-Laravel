<?php

namespace Zakhir\LaravelZakhir\Data;

use Illuminate\Support\Str;

class CreatePaymentData
{
    public readonly string $referenceId;

    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $note,
        public readonly string $returnUrl,
        public readonly string $notifyUrl,
        ?string $referenceId = null,
    ) {
        $this->referenceId = $referenceId ?? (string) Str::uuid();
    }

    public function toArray(): array
    {
        return [
            'referenceId' => $this->referenceId,
            'amount' => [
                'value'    => round($this->amount, 2),
                'currency' => $this->currency,
            ],
            'note'         => $this->note,
            'checkoutPage' => [
                'returnUrl' => $this->returnUrl,
            ],
            'notifyUrl' => $this->notifyUrl,
        ];
    }
}
