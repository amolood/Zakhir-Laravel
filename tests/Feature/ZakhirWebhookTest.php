<?php

namespace Zakhir\LaravelZakhir\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Zakhir\LaravelZakhir\Contracts\ZakhirClientInterface;
use Zakhir\LaravelZakhir\Data\PaymentStatusResponse;
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

    /** Zakhir's own answer when the controller asks for the payment's status. */
    private function zakhirReports(string $status, string $id = 'zakhir-gateway-id-001'): void
    {
        $this->app->instance(ZakhirClientInterface::class, new class($status, $id) implements ZakhirClientInterface {
            public function __construct(private string $status, private string $id) {}

            public function createPayment(\Zakhir\LaravelZakhir\Data\CreatePaymentData $data): \Zakhir\LaravelZakhir\Data\PaymentResponse { throw new \LogicException('not used'); }

            public function getPaymentStatus(string $referenceId): PaymentStatusResponse
            {
                return PaymentStatusResponse::fromArray(['referenceId' => $referenceId, 'status' => $this->status, 'id' => $this->id]);
            }

            public function cancelPayment(string $referenceId): array { return []; }
        });
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
        $this->zakhirReports('COMPLETED');

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
        $this->zakhirReports('COMPLETED', 'gw-id-456');

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
        $this->zakhirReports('REJECTED', 'gw-id-789');

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
        $this->zakhirReports('PROCESSING', 'gw-id-999');

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

    public function test_forged_completed_webhook_is_ignored_when_zakhir_says_pending(): void
    {
        Event::fake();
        $this->zakhirReports('PENDING');
        $this->createPendingPayment('ref-forged-1');

        $this->postJson('/api/zakhir/webhook', ['referenceId' => 'ref-forged-1', 'status' => 'COMPLETED'])->assertOk();

        $this->assertDatabaseHas('zakhir_payments', ['reference_id' => 'ref-forged-1', 'status' => 'PENDING']);
        Event::assertNotDispatched(ZakhirPaymentCompleted::class);
    }

    public function test_webhook_changes_nothing_when_zakhir_cannot_be_reached(): void
    {
        Event::fake();
        $this->app->instance(ZakhirClientInterface::class, \Mockery::mock(ZakhirClientInterface::class, fn ($m) => $m->shouldReceive('getPaymentStatus')->andThrow(new \RuntimeException('down'))));
        $this->createPendingPayment('ref-down-1');

        $this->postJson('/api/zakhir/webhook', ['referenceId' => 'ref-down-1', 'status' => 'COMPLETED'])->assertOk();

        $this->assertDatabaseHas('zakhir_payments', ['reference_id' => 'ref-down-1', 'status' => 'PENDING']);
    }
}
