<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_request_append_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('incoming_request_id');
            $table->unsignedBigInteger('sale_id');
            $table->string('idempotency_key', 191);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('state_transition', 32)->nullable();
            $table->unsignedInteger('appended_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->json('response_payload');
            $table->timestamps();

            $table->unique(['incoming_request_id', 'sale_id', 'idempotency_key'], 'iraa_unique_request_sale_idempotency');
            $table->index(['incoming_request_id', 'sale_id'], 'iraa_request_sale_idx');

            $table->foreign('incoming_request_id')
                ->references('id')
                ->on('incoming_requests')
                ->cascadeOnDelete();

            $table->foreign('sale_id')
                ->references('id')
                ->on('sales')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_request_append_attempts');
    }
};
