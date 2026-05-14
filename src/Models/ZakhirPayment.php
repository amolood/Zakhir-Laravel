<?php

namespace Zakhir\LaravelZakhir\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int         $id
 * @property string      $transaction_id      Unique internal ID (zakhir-{seed})
 * @property string      $gateway_reference   Zakhir's payment ID
 * @property string      $reference_id        UUID sent as referenceId in API request
 * @property int         $payable_id
 * @property string      $payable_type
 * @property int         $amount              Amount in smallest currency unit (piasters/cents)
 * @property string      $currency
 * @property string      $status
 * @property array|null  $raw_payload
 * @property string|null $paid_at
 */
class ZakhirPayment extends Model
{
    protected $table = 'zakhir_payments';

    protected $fillable = [
        'transaction_id',
        'gateway_reference',
        'reference_id',
        'payable_id',
        'payable_type',
        'amount',
        'currency',
        'status',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'amount'      => 'integer',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'COMPLETED';
    }
}
