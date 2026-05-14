<?php

namespace Zakhir\LaravelZakhir\Contracts;

use Zakhir\LaravelZakhir\Data\CreatePaymentData;
use Zakhir\LaravelZakhir\Data\PaymentResponse;
use Zakhir\LaravelZakhir\Data\PaymentStatusResponse;

interface ZakhirClientInterface
{
    public function createPayment(CreatePaymentData $data): PaymentResponse;

    public function getPaymentStatus(string $referenceId): PaymentStatusResponse;

    public function cancelPayment(string $referenceId): array;
}
