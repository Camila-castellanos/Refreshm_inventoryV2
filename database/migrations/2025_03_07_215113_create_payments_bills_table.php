<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId("bill_id")->constrained();
            $table->string("amount_paid");
            $table->string("balance_remaining");
            $table->string("payment_method");
            $table->string("payment_account");
            $table->date("payment_date");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_bills');
    }
};
