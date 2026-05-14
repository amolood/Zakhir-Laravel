<?php

use Illuminate\Support\Facades\Route;
use Zakhir\LaravelZakhir\Http\Controllers\ZakhirWebhookController;
use Zakhir\LaravelZakhir\Http\Middleware\VerifyZakhirWebhookSignature;

Route::post('webhook', ZakhirWebhookController::class)
    ->middleware(VerifyZakhirWebhookSignature::class)
    ->name('zakhir.webhook');
