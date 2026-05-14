<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zakhir_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 10);   // 'incoming' | 'outgoing'
            $table->string('method', 10);
            $table->string('url', 1000);
            $table->string('ip', 45)->nullable();
            $table->unsignedSmallInteger('status_code')->default(0);
            $table->json('request_body')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zakhir_logs');
    }
};
