<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakhir_payments', function (Blueprint $table): void {
            $table->id();

            // Unique internal transaction ID: "zakhir-{seed}"
            $table->string('transaction_id')->unique();

            // Zakhir's own payment ID returned in the API response
            $table->string('gateway_reference')->nullable()->index();

            // The UUID we sent as referenceId in the create-payment request
            $table->string('reference_id')->index();

            // The local model this payment belongs to (polymorphic)
            $table->morphs('payable');

            // Amount in smallest unit (piasters for SDG, etc.)
            $table->unsignedBigInteger('amount');
            $table->string('currency', 3)->default('SDG');

            $table->string('status', 20)->default('PENDING')->index();

            // Full raw webhook/API payload for audit
            $table->json('raw_payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakhir_payments');
    }
};
