<?php

namespace Zakhir\LaravelZakhir\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Zakhir\LaravelZakhir\Data\WebhookPayload;

class ZakhirPaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly WebhookPayload $payload,
        public readonly int $localPayableId,
        public readonly string $localPayableType,
    ) {}
}
