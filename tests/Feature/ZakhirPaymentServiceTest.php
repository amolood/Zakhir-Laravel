<?php

namespace Zakhir\LaravelZakhir\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Zakhir\LaravelZakhir\Data\PaymentResponse;
use Zakhir\LaravelZakhir\Data\PaymentStatusResponse;
use Zakhir\LaravelZakhir\Exceptions\ZakhirApiException;
use Zakhir\LaravelZakhir\Facades\Zakhir;
use Zakhir\LaravelZakhir\Tests\TestCase;

class ZakhirPaymentServiceTest extends TestCase
{
    private function fakeCreatePaymentResponse(): array
    {
        return [
            'id'          => 'zakhir-pay-id-001',
            'referenceId' => 'test-ref-uuid',
            'status'      => 'PENDING',
            'paymentToken' => [
                'value'     => 'tok_abc123',
                'expiresAt' => '2026-05-15T10:00:00Z',
            ],
            'checkoutPage' => [
                'url'          => 'https://checkout.zakhir.cloud/pay/tok_abc123',
                'mobileAppUrl' => 'zakhir://pay/tok_abc123',
            ],
        ];
    }

    public function test_create_payment_returns_payment_response(): void
    {
        Http::fake([
            '*/payments' => Http::response($this->fakeCreatePaymentResponse(), 200),
        ]);

        $response = Zakhir::createPayment(
            amount: 150.0,
            currency: 'SDG',
            note: 'Invoice #1001',
        );

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertSame('zakhir-pay-id-001', $response->id);
        $this->assertSame('PENDING', $response->status);
        $this->assertSame('https://checkout.zakhir.cloud/pay/tok_abc123', $response->checkoutUrl);
        $this->assertTrue($response->isPending());
        $this->assertFalse($response->isCompleted());
    }

    public function test_create_payment_sends_correct_headers(): void
    {
        Http::fake([
            '*/payments' => Http::response($this->fakeCreatePaymentResponse(), 200),
        ]);

        Zakhir::createPayment(
            amount: 50.0,
            currency: 'SDG',
            note: 'Test',
        );

        Http::assertSent(function ($request) {
            return $request->hasHeader('v-tenant', 'test-tenant')
                && $request->hasHeader('v-profile', 'test-profile')
                && $request->hasHeader('v-api-key', 'test-api-key');
        });
    }

    public function test_get_payment_status_returns_status_response(): void
    {
        Http::fake([
            '*/payments/test-ref-uuid/status' => Http::response([
                'referenceId' => 'test-ref-uuid',
                'status'      => 'COMPLETED',
                'id'          => 'zakhir-pay-id-001',
            ], 200),
        ]);

        $status = Zakhir::getPaymentStatus('test-ref-uuid');

        $this->assertInstanceOf(PaymentStatusResponse::class, $status);
        $this->assertSame('COMPLETED', $status->status);
        $this->assertTrue($status->isCompleted());
        $this->assertFalse($status->isPending());
    }

    public function test_create_payment_throws_on_gateway_error(): void
    {
        Http::fake([
            '*/payments' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->expectException(ZakhirApiException::class);

        Zakhir::createPayment(50.0, 'SDG', 'Test');
    }

    public function test_cancel_payment_sends_delete_request(): void
    {
        Http::fake([
            '*/payments/ref-123' => Http::response(['cancelled' => true], 200),
        ]);

        $result = Zakhir::cancelPayment('ref-123');

        $this->assertIsArray($result);

        Http::assertSent(function ($request) {
            return $request->method() === 'DELETE'
                && str_contains($request->url(), 'ref-123');
        });
    }
}
