<?php

namespace Zakhir\LaravelZakhir\Facades;

use Illuminate\Support\Facades\Facade;
use Zakhir\LaravelZakhir\Data\PaymentResponse;
use Zakhir\LaravelZakhir\Data\PaymentStatusResponse;
use Zakhir\LaravelZakhir\ZakhirPaymentService;

/**
 * @method static PaymentResponse      createPayment(float $amount, string $currency, string $note, ?string $returnUrl = null, ?string $notifyUrl = null, ?string $referenceId = null)
 * @method static PaymentStatusResponse getPaymentStatus(string $referenceId)
 * @method static array                 cancelPayment(string $referenceId)
 * @method static string                environment()
 *
 * @see ZakhirPaymentService
 */
class Zakhir extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ZakhirPaymentService::class;
    }
}
