<?php

namespace Zakhir\LaravelZakhir\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Zakhir\LaravelZakhir\Data\WebhookPayload;

/**
 * Fired for every incoming Zakhir webhook regardless of status.
 * Useful for raw auditing or custom handling.
 */
class ZakhirWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly WebhookPayload $payload,
    ) {}
}
