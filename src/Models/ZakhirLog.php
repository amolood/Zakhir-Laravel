<?php

namespace Zakhir\LaravelZakhir\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int         $id
 * @property string      $direction      'incoming' | 'outgoing'
 * @property string      $method
 * @property string      $url
 * @property string|null $ip
 * @property int         $status_code
 * @property array|null  $request_body
 * @property array|null  $response_body
 * @property int         $duration_ms
 */
class ZakhirLog extends Model
{
    protected $table = 'zakhir_logs';

    public $timestamps = false;

    protected $fillable = [
        'direction',
        'method',
        'url',
        'ip',
        'status_code',
        'request_body',
        'response_body',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'request_body'  => 'array',
        'response_body' => 'array',
        'duration_ms'   => 'integer',
        'status_code'   => 'integer',
        'created_at'    => 'datetime',
    ];
}
