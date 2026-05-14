<?php

namespace Zakhir\LaravelZakhir\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Zakhir\LaravelZakhir\Data\WebhookPayload;
use Zakhir\LaravelZakhir\Enums\PaymentStatus;
use Zakhir\LaravelZakhir\Events\ZakhirPaymentCompleted;
use Zakhir\LaravelZakhir\Events\ZakhirPaymentFailed;
use Zakhir\LaravelZakhir\Events\ZakhirWebhookReceived;
use Zakhir\LaravelZakhir\Models\ZakhirPayment;

class ZakhirWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->all();

        // Zakhir may send fields in query-string or body or both
        $id          = (string) ($request->query('id') ?? data_get($raw, 'id', ''));
        $referenceId = (string) ($request->query('referenceId') ?? data_get($raw, 'referenceId', ''));
        $status      = (string) ($request->query('status') ?? data_get($raw, 'status', ''));

        if ($referenceId === '') {
            Log::warning('[Zakhir] Webhook received without referenceId.');

            return response()->json(['status' => 'received']);
        }

        $payload = WebhookPayload::fromArray(array_merge($raw, [
            'id'          => $id,
            'referenceId' => $referenceId,
            'status'      => $status,
        ]));

        ZakhirWebhookReceived::dispatch($payload);

        match ($payload->status) {
            PaymentStatus::Completed => $this->handleCompleted($payload),
            PaymentStatus::Rejected  => $this->handleRejected($payload),
            default => Log::info('[Zakhir] Webhook unhandled status.', [
                'status'      => $payload->status->value,
                'referenceId' => $referenceId,
            ]),
        };

        return response()->json(['status' => 'received']);
    }

    protected function handleCompleted(WebhookPayload $payload): void
    {
        DB::transaction(function () use ($payload): void {
            // Find a pending payment record by referenceId
            $payment = ZakhirPayment::query()
                ->where('reference_id', $payload->referenceId)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('[Zakhir] Webhook COMPLETED: no ZakhirPayment found.', [
                    'referenceId' => $payload->referenceId,
                    'id'          => $payload->id,
                ]);

                return;
            }

            if ($payment->isCompleted()) {
                // Already processed — idempotent
                return;
            }

            $transactionId = $this->buildTransactionId($payload->id, $payload->referenceId);

            // Extra idempotency guard: ensure no duplicate transaction
            $duplicate = ZakhirPayment::query()
                ->where('transaction_id', $transactionId)
                ->where('id', '!=', $payment->id)
                ->exists();

            if ($duplicate) {
                return;
            }

            $payment->update([
                'status'            => 'COMPLETED',
                'transaction_id'    => $transactionId,
                'gateway_reference' => $payload->id !== '' ? $payload->id : $payment->gateway_reference,
                'raw_payload'       => $payload->raw,
                'paid_at'           => now()->toDateTimeString(),
            ]);

            ZakhirPaymentCompleted::dispatch($payment->fresh());
        });
    }

    protected function handleRejected(WebhookPayload $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $payment = ZakhirPayment::query()
                ->where('reference_id', $payload->referenceId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->isCompleted()) {
                return;
            }

            if ($payment->status === 'PENDING') {
                $payment->update([
                    'status'      => 'FAILED',
                    'raw_payload' => $payload->raw,
                ]);

                ZakhirPaymentFailed::dispatch(
                    $payload,
                    $payment->payable_id,
                    $payment->payable_type,
                );
            }
        });
    }

    protected function buildTransactionId(string $zakhirId, string $referenceId): string
    {
        $seed = $zakhirId !== '' ? $zakhirId : $referenceId;
        $normalized = preg_replace('/[^A-Za-z0-9_.-]/', '-', $seed) ?: (string) Str::uuid();

        return 'zakhir-'.$normalized;
    }
}
