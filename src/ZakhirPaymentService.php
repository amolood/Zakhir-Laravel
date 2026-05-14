<?php

namespace Zakhir\LaravelZakhir;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Zakhir\LaravelZakhir\Contracts\ZakhirClientInterface;
use Zakhir\LaravelZakhir\Data\CreatePaymentData;
use Zakhir\LaravelZakhir\Data\PaymentResponse;
use Zakhir\LaravelZakhir\Data\PaymentStatusResponse;
use Zakhir\LaravelZakhir\Http\ZakhirConfig;

class ZakhirPaymentService
{
    public function __construct(
        private readonly ZakhirClientInterface $client,
        private readonly ZakhirConfig $config,
    ) {}

    /**
     * Initiate a new payment and return checkout details.
     * A fresh UUID referenceId is generated per attempt.
     */
    public function createPayment(
        float $amount,
        string $currency,
        string $note,
        ?string $returnUrl = null,
        ?string $notifyUrl = null,
        ?string $referenceId = null,
    ): PaymentResponse {
        $this->config->assertConfigured();

        $data = new CreatePaymentData(
            amount: $amount,
            currency: $currency,
            note: $note,
            returnUrl: $returnUrl ?? $this->defaultReturnUrl(),
            notifyUrl: $notifyUrl ?? $this->defaultWebhookUrl(),
            referenceId: $referenceId,
        );

        return $this->client->createPayment($data);
    }

    /**
     * Poll for payment status by referenceId.
     */
    public function getPaymentStatus(string $referenceId): PaymentStatusResponse
    {
        $this->config->assertConfigured();

        return $this->client->getPaymentStatus($referenceId);
    }

    /**
     * Cancel a PENDING payment that has no transaction yet.
     */
    public function cancelPayment(string $referenceId): array
    {
        $this->config->assertConfigured();

        return $this->client->cancelPayment($referenceId);
    }

    /**
     * Return the active environment label.
     */
    public function environment(): string
    {
        return $this->config->environment();
    }

    protected function defaultWebhookUrl(): string
    {
        $configured = $this->config->webhookUrl();

        if ($configured !== '') {
            return $configured;
        }

        return URL::to('/api/zakhir/webhook');
    }

    protected function defaultReturnUrl(): string
    {
        $configured = $this->config->returnUrl();

        if ($configured !== '') {
            return $configured;
        }

        return URL::to('/');
    }
}
