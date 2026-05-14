<?php

namespace Zakhir\LaravelZakhir\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Zakhir\LaravelZakhir\Models\ZakhirPayment;

class ZakhirPaymentCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly ZakhirPayment $payment,
    ) {}
}
