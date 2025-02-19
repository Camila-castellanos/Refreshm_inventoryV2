<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->date("date")->nullable();
            $table->string("supplier")->nullable();
            $table->string("manufacturer")->nullable();
            $table->string("model")->nullable();
            $table->string("colour")->nullable();
            $table->string("battery")->nullable();
            $table->string("grade")->nullable();
            $table->string("issues")->nullable();
            $table->unsignedFloat("cost")->nullable();
            $table->string("imei")->nullable();
            $table->float("selling_price")->nullable();
            $table->dateTime("sold")->nullable();
            $table->timestamp("hold")->nullable();
            $table->foreignId("sale_id")->nullable()->constrained();
            $table->string("customer")->nullable();
            $table->unsignedFloat("discount")->nullable();
            $table->unsignedFloat("tax")->nullable();
            $table->unsignedFloat("subtotal")->nullable();
            $table->unsignedFloat("profit")->nullable();
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
        Schema::dropIfExists('items');
    }
}
