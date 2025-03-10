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
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->string('item');
            $table->string('customer');
            $table->timestamps();
            $table->string('sale')->nullable();
            $table->decimal('credit')->default(0);
            $table->string('imei')->default(NULL);
            $table->string('model')->default(NULL);
            $table->boolean('requested')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
