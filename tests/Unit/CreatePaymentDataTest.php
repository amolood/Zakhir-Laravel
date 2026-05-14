<?php

namespace Zakhir\LaravelZakhir\Tests\Unit;

use Zakhir\LaravelZakhir\Data\CreatePaymentData;
use Zakhir\LaravelZakhir\Tests\TestCase;

class CreatePaymentDataTest extends TestCase
{
    public function test_generates_uuid_when_no_reference_id_given(): void
    {
        $data = new CreatePaymentData(
            amount: 100.0,
            currency: 'SDG',
            note: 'Test note',
            returnUrl: 'https://example.com/return',
            notifyUrl: 'https://example.com/webhook',
        );

        $this->assertNotEmpty($data->referenceId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $data->referenceId,
        );
    }

    public function test_uses_provided_reference_id(): void
    {
        $data = new CreatePaymentData(
            amount: 100.0,
            currency: 'SDG',
            note: 'Test',
            returnUrl: 'https://example.com/return',
            notifyUrl: 'https://example.com/webhook',
            referenceId: 'custom-ref-id',
        );

        $this->assertSame('custom-ref-id', $data->referenceId);
    }

    public function test_to_array_structure(): void
    {
        $data = new CreatePaymentData(
            amount: 50.55,
            currency: 'SDG',
            note: 'Invoice #123',
            returnUrl: 'https://example.com/return',
            notifyUrl: 'https://example.com/webhook',
            referenceId: 'test-uuid',
        );

        $array = $data->toArray();

        $this->assertSame('test-uuid', $array['referenceId']);
        $this->assertSame(50.55, $array['amount']['value']);
        $this->assertSame('SDG', $array['amount']['currency']);
        $this->assertSame('Invoice #123', $array['note']);
        $this->assertSame('https://example.com/return', $array['checkoutPage']['returnUrl']);
        $this->assertSame('https://example.com/webhook', $array['notifyUrl']);
    }

    public function test_amount_is_rounded_to_two_decimals(): void
    {
        $data = new CreatePaymentData(
            amount: 99.999,
            currency: 'SDG',
            note: 'Test',
            returnUrl: 'https://example.com/return',
            notifyUrl: 'https://example.com/webhook',
        );

        $this->assertSame(100.0, $data->toArray()['amount']['value']);
    }
}
