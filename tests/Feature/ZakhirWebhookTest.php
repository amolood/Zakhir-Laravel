<?php

namespace Zakhir\LaravelZakhir\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Zakhir\LaravelZakhir\Events\ZakhirPaymentCompleted;
use Zakhir\LaravelZakhir\Events\ZakhirPaymentFailed;
use Zakhir\LaravelZakhir\Events\ZakhirWebhookReceived;
use Zakhir\LaravelZakhir\Models\ZakhirPayment;
use Zakhir\LaravelZakhir\Tests\TestCase;

class ZakhirWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('zakhir.routes.enabled', true);
    }

    private function createPendingPayment(string $referenceId): ZakhirPayment
    {
        return ZakhirPayment::create([
            'transaction_id'    => 'zakhir-pending-'.$referenceId,
            'gateway_reference' => null,
            'reference_id'      => $referenceId,
            'payable_id'        => 1,
            'payable_type'      => 'App\\Models\\Invoice',
            'amount'            => 5000,
            'currency'          => 'SDG',
            'status'            => 'PENDING',
        ]);
    }

    public function test_webhook_missing_reference_id_returns_received(): void
    {
        $response = $this->postJson('/api/zakhir/webhook', ['status' => 'COMPLETED']);

        $response->assertOk()->assertJson(['status' => 'received']);
    }

    public function test_completed_webhook_marks_payment_completed(): void
    {
        Event::fake();

        $this->createPendingPayment('ref-abc-123');

        $response = $this->postJson('/api/zakhir/webhook', [
            'id'          => 'zakhir-gateway-id-001',
            'referenceId' => 'ref-abc-123',
            'status'      => 'COMPLETED',
        ]);

        $response->assertOk()->assertJson(['status' => 'received']);

        $this->assertDatabaseHas('zakhir_payments', [
            'reference_id' => 'ref-abc-123',
            'status'       => 'COMPLETED',
        ]);

        Event::assertDispatched(ZakhirPaymentCompleted::class);
        Event::assertDispatched(ZakhirWebhookReceived::class);
    }

    public function test_completed_webhook_is_idempotent(): void
    {
        Event::fake();

        $this->createPendingPayment('ref-idem-456');

        // First call
        $this->postJson('/api/zakhir/webhook', [
            'id'          => 'gw-id-456',
            'referenceId' => 'ref-idem-456',
            'status'      => 'COMPLETED',
        ]);

        // Second call — should not double-fire
        $this->postJson('/api/zakhir/webhook', [
            'id'          => 'gw-id-456',
            'referenceId' => 'ref-idem-456',
            'status'      => 'COMPLETED',
        ]);

        Event::assertDispatchedTimes(ZakhirPaymentCompleted::class, 1);
    }

    public function test_rejected_webhook_marks_payment_failed(): void
    {
        Event::fake();

        $this->createPendingPayment('ref-rejected-789');

        $response = $this->postJson('/api/zakhir/webhook', [
            'id'          => 'gw-id-789',
            'referenceId' => 'ref-rejected-789',
            'status'      => 'REJECTED',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('zakhir_payments', [
            'reference_id' => 'ref-rejected-789',
            'status'       => 'FAILED',
        ]);

        Event::assertDispatched(ZakhirPaymentFailed::class);
    }

    public function test_unknown_status_returns_received_without_error(): void
    {
        Event::fake();

        $this->createPendingPayment('ref-unknown-999');

        $response = $this->postJson('/api/zakhir/webhook', [
            'id'          => 'gw-id-999',
            'referenceId' => 'ref-unknown-999',
            'status'      => 'PROCESSING',
        ]);

        $response->assertOk()->assertJson(['status' => 'received']);

        $this->assertDatabaseHas('zakhir_payments', [
            'reference_id' => 'ref-unknown-999',
            'status'       => 'PENDING',
        ]);
    }
}
