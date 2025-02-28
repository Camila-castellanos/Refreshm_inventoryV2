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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->foreignId('user_id')->constrained();
            $table->text('first_name')->nullable();
            $table->text('last_name')->nullable();
            $table->string('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('phone_optional')->nullable();
            $table->string('account_number')->nullable();
            $table->string('website')->nullable();
            $table->string('notes')->nullable();
            $table->string('currency')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('billing_address_optional')->nullable();
            $table->text('billing_address_country')->nullable();
            $table->text('billing_address_state')->nullable();
            $table->text('billing_address_city')->nullable();
            $table->text('billing_address_postal')->nullable();
            $table->string('ship_name')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('shipping_address_optional')->nullable();
            $table->text('shipping_address_country')->nullable();
            $table->text('shipping_address_state')->nullable();
            $table->text('shipping_address_city')->nullable();
            $table->text('shipping_address_postal')->nullable();
            $table->text('shipping_phone')->nullable();
            $table->text('delivery_instructions')->nullable();
            $table->decimal('credit')->nullable();
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
        Schema::dropIfExists('customers');
    }
};