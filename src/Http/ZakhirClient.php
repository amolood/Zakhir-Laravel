<?php

namespace Zakhir\LaravelZakhir\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Zakhir\LaravelZakhir\Contracts\ZakhirClientInterface;
use Zakhir\LaravelZakhir\Data\CreatePaymentData;
use Zakhir\LaravelZakhir\Data\PaymentResponse;
use Zakhir\LaravelZakhir\Data\PaymentStatusResponse;
use Zakhir\LaravelZakhir\Exceptions\ZakhirApiException;
use Zakhir\LaravelZakhir\Exceptions\ZakhirException;
use Zakhir\LaravelZakhir\Support\ZakhirLogger;

class ZakhirClient implements ZakhirClientInterface
{
    public function __construct(
        private readonly ZakhirConfig $config,
        private readonly ZakhirLogger $logger,
    ) {}

    public function createPayment(CreatePaymentData $data): PaymentResponse
    {
        $response = $this->call('POST', 'payments', $data->toArray());

        if (! is_array($response)) {
            throw ZakhirException::invalidResponse('createPayment');
        }

        return PaymentResponse::fromArray($response);
    }

    public function getPaymentStatus(string $referenceId): PaymentStatusResponse
    {
        $response = $this->call('GET', 'payments/'.rawurlencode($referenceId).'/status');

        if (! is_array($response)) {
            throw ZakhirException::invalidResponse('getPaymentStatus');
        }

        return PaymentStatusResponse::fromArray($response);
    }

    public function cancelPayment(string $referenceId): array
    {
        $response = $this->call('DELETE', 'payments/'.rawurlencode($referenceId));

        if (! is_array($response)) {
            throw ZakhirException::invalidResponse('cancelPayment');
        }

        return $response;
    }

    protected function call(string $method, string $path, array $body = []): mixed
    {
        $url = $this->config->baseUrl().$path;
        $start = microtime(true);
        $httpResponse = null;
        $statusCode = 0;

        try {
            $httpResponse = $this->executeRequest($method, $path, $body);
            $statusCode = $httpResponse->status();

            if ($httpResponse->failed()) {
                throw ZakhirApiException::fromResponse($httpResponse);
            }

            return $httpResponse->json();
        } catch (ZakhirApiException $e) {
            $statusCode = $e->statusCode;
            throw $e;
        } catch (\Throwable $e) {
            $statusCode = $httpResponse?->status() ?? 0;
            throw $e;
        } finally {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            $responseBody = null;
            if ($httpResponse !== null) {
                $responseBody = $httpResponse->json() ?? ['raw' => $httpResponse->body()];
            }

            $this->logger->logOutgoing(
                method: strtoupper($method),
                url: $url,
                requestBody: $body,
                statusCode: $statusCode,
                responseBody: $responseBody ?? [],
                durationMs: $durationMs,
            );
        }
    }

    protected function executeRequest(string $method, string $path, array $body): Response
    {
        $pending = $this->buildRequest();

        return match (strtoupper($method)) {
            'GET'    => $pending->get($path),
            'DELETE' => $pending->delete($path),
            default  => $pending->post($path, $body),
        };
    }

    protected function buildRequest(): PendingRequest
    {
        return Http::baseUrl($this->config->baseUrl())
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'v-tenant'  => $this->config->tenant(),
                'v-profile' => $this->config->profile(),
                'v-api-key' => $this->config->apiKey(),
            ])
            ->timeout($this->config->timeout());
    }
}
