<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->boolean("status")->nullable();
            $table->date("date")->nullable();
            $table->string("vendor")->nullable();
            $table->string("total")->nullable();
            $table->string("amount_paid")->nullable();
            $table->string("balance_remaining")->nullable();
            $table->foreignId("user_id")->nullable()->constrained();
            $table->foreignId("bill_id")->constrained();
            $table->string("payment_method");
            $table->string("payment_account");
            $table->date("payment_date");
            $table->string("vendor_id")->nullable();
            $table->string("subtotal")->nullable();
            $table->string("tax")->nullable();
            $table->string("flat_tax")->nullable();
            $table->string("invoice")->nullable();
            $table->foreignId('tax_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bills');
    }
};