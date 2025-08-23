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
        Schema::create('incoming_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_request_id')->constrained('incoming_requests')->onDelete('cascade');
            $table->unsignedBigInteger('original_item_id')->nullable();

            $table->dateTime('date')->nullable();
            $table->string('supplier')->nullable();
            $table->string('manufacturer')->nullable();
            $table->unsignedBigInteger('storage_id')->nullable();
            $table->integer('position')->nullable();
            $table->string('model')->nullable();
            $table->string('colour')->nullable();
            $table->integer('battery')->nullable();
            $table->string('grade')->nullable();
            $table->text('issues')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('imei')->nullable();
            $table->decimal('selling_price', 12, 2)->nullable();
            $table->string('customer')->nullable();
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->string('type')->nullable();

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
        Schema::dropIfExists('incoming_request_items');
    }
};
